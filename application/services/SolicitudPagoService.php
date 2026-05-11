<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'services/CorrelativoService.php';
require_once APPPATH . 'services/MonedaService.php';

/**
 * SolicitudPagoService — encapsula la lógica de SDP:
 *   - Generación de correlativo SDP-AAAA-NNNN.
 *   - Snapshot multimoneda (tipo_cambio_clp + monto_total_clp).
 *   - Máquina de estados con transiciones validadas.
 *   - Auditoría de cada cambio.
 *   - Notificaciones por correo.
 *   - Log granular en gmc_sdp_estados_log.
 *
 * Transiciones permitidas:
 *   pendiente   -> validada
 *   pendiente   -> rechazada (con motivo)
 *   validada    -> programada
 *   validada    -> rechazada (con motivo)
 *   programada  -> pagada
 *   programada  -> rechazada (con motivo, excepcional)
 *
 * Estados finales: pagada, rechazada (no admiten más transiciones).
 */
class SolicitudPagoService
{
    /** @var CI_Controller */
    protected $CI;
    /** @var CorrelativoService */
    protected $correlativo;
    /** @var MonedaService */
    protected $moneda;

    /** Transiciones permitidas (origen => [destinos válidos]) */
    protected $transiciones = [
        'pendiente'  => ['validada', 'rechazada'],
        'validada'   => ['programada', 'rechazada'],
        'programada' => ['pagada', 'rechazada'],
        'pagada'     => [],
        'rechazada'  => [],
    ];

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->model(['SolicitudPagoRepo','SdpEstadoLogRepo']);
        $this->CI->load->library(['Audit','Notifier']);
        $this->correlativo = new CorrelativoService();
        $this->moneda      = new MonedaService();
    }

    // ============================================================
    // CREAR
    // ============================================================
    public function crear(array $input, int $userId): int
    {
        // Estado inicial 'pendiente'
        $estadoPendiente = $this->_estado('pendiente');

        // Snapshot multimoneda (lanza excepción si no hay TC y la moneda no es CLP)
        $monedaId    = (int)$input['moneda_id'];
        $monto       = (float)$input['monto_total'];
        $fechaRef    = $input['fecha_emision'] ?? date('Y-m-d');
        $snap        = $this->moneda->snapshot($monedaId, $monto, $fechaRef);

        $this->CI->db->trans_start();

        $numero = $this->correlativo->next('solicitud_pago');

        $payload = [
            'numero'           => $numero,
            'proyecto_id'      => !empty($input['proyecto_id']) ? (int)$input['proyecto_id'] : null,
            'centro_costo_id'  => (int)$input['centro_costo_id'],
            'proveedor_id'     => (int)$input['proveedor_id'],
            'tipo_gasto_id'    => (int)$input['tipo_gasto_id'],
            'moneda_id'        => $monedaId,
            'monto_neto'       => (float)($input['monto_neto'] ?? 0),
            'monto_iva'        => (float)($input['monto_iva']  ?? 0),
            'monto_total'      => $monto,
            'tipo_cambio_clp'  => $snap['tipo_cambio_clp'],
            'monto_total_clp'  => $snap['monto_total_clp'],
            'fecha_emision'    => $input['fecha_emision'],
            'fecha_vencimiento'=> !empty($input['fecha_vencimiento']) ? $input['fecha_vencimiento'] : null,
            'documento_tipo'   => $input['documento_tipo']   ?? null,
            'documento_numero' => $input['documento_numero'] ?? null,
            'forma_pago'       => $input['forma_pago']       ?? null,
            'descripcion'      => $input['descripcion']      ?? null,
            'comentarios'      => $input['comentarios']      ?? null,
            'estado_id'        => (int)$estadoPendiente['id'],
            'created_by'       => $userId,
            'updated_by'       => $userId,
        ];

        $sdpId = $this->CI->SolicitudPagoRepo->create($payload);

        // Log granular de creación (estado_anterior=NULL)
        $this->CI->SdpEstadoLogRepo->logTransicion($sdpId, null, (int)$estadoPendiente['id'], $userId, 'Creación de SDP');

        // Auditoría
        $this->CI->audit->log('sdp.crear', 'gmc_solicitudes_pago', $sdpId, null, [
            'numero' => $numero, 'monto_total' => $monto, 'monto_total_clp' => $snap['monto_total_clp'],
        ]);

        $this->CI->db->trans_complete();

        // Notificar a Finanzas (correo a admin@gmc.cl como fallback; en Sprint 5 podemos
        // tener una lista de "notificar a todos los Finanzas")
        $this->_notificarFinanzas($sdpId, 'sdp.creada', "Nueva SDP {$numero} creada");

        return $sdpId;
    }

    // ============================================================
    // EDITAR (sólo Pendiente)
    // ============================================================
    public function editar(int $sdpId, array $input, int $userId): void
    {
        $sdp = $this->CI->SolicitudPagoRepo->find($sdpId);
        if (!$sdp) throw new RuntimeException('SDP no encontrada.');

        $estado = $this->_estadoById((int)$sdp['estado_id']);
        if ($estado['codigo'] !== 'pendiente') {
            throw new RuntimeException('Sólo se puede editar una SDP en estado Pendiente.');
        }

        // Re-snapshot si cambió monto, moneda o fecha
        $monedaId  = (int)($input['moneda_id'] ?? $sdp['moneda_id']);
        $monto     = (float)($input['monto_total'] ?? $sdp['monto_total']);
        $fechaRef  = $input['fecha_emision'] ?? $sdp['fecha_emision'];
        $snap = $this->moneda->snapshot($monedaId, $monto, $fechaRef);

        $payload = [
            'proyecto_id'      => !empty($input['proyecto_id']) ? (int)$input['proyecto_id'] : null,
            'centro_costo_id'  => (int)$input['centro_costo_id'],
            'proveedor_id'     => (int)$input['proveedor_id'],
            'tipo_gasto_id'    => (int)$input['tipo_gasto_id'],
            'moneda_id'        => $monedaId,
            'monto_neto'       => (float)($input['monto_neto'] ?? 0),
            'monto_iva'        => (float)($input['monto_iva']  ?? 0),
            'monto_total'      => $monto,
            'tipo_cambio_clp'  => $snap['tipo_cambio_clp'],
            'monto_total_clp'  => $snap['monto_total_clp'],
            'fecha_emision'    => $input['fecha_emision'],
            'fecha_vencimiento'=> !empty($input['fecha_vencimiento']) ? $input['fecha_vencimiento'] : null,
            'documento_tipo'   => $input['documento_tipo']   ?? null,
            'documento_numero' => $input['documento_numero'] ?? null,
            'forma_pago'       => $input['forma_pago']       ?? null,
            'descripcion'      => $input['descripcion']      ?? null,
            'comentarios'      => $input['comentarios']      ?? null,
            'updated_by'       => $userId,
        ];
        $this->CI->SolicitudPagoRepo->update($sdpId, $payload);
        $this->CI->audit->logChanges('sdp.editar', 'gmc_solicitudes_pago', $sdpId, $sdp, $payload);
    }

    // ============================================================
    // TRANSICIONES
    // ============================================================
    public function validar(int $sdpId, int $userId): void
    {
        $this->_transicionar($sdpId, 'validada', $userId, [
            'validada_por' => $userId,
            'validada_at'  => date('Y-m-d H:i:s'),
        ]);
    }

    public function programar(int $sdpId, int $userId, string $fechaProgramada, ?string $formaPago): void
    {
        if (!$fechaProgramada) {
            throw new RuntimeException('La fecha programada es obligatoria.');
        }
        $this->_transicionar($sdpId, 'programada', $userId, [
            'programada_por'   => $userId,
            'programada_at'    => date('Y-m-d H:i:s'),
            'fecha_programada' => $fechaProgramada,
            'forma_pago'       => $formaPago,
        ]);
    }

    public function pagar(int $sdpId, int $userId, string $fechaPago): void
    {
        if (!$fechaPago) {
            throw new RuntimeException('La fecha de pago es obligatoria.');
        }
        $this->_transicionar($sdpId, 'pagada', $userId, [
            'pagada_por' => $userId,
            'pagada_at'  => date('Y-m-d H:i:s'),
            'fecha_pago' => $fechaPago,
        ]);
    }

    public function rechazar(int $sdpId, int $userId, string $motivo): void
    {
        $motivo = trim($motivo);
        if (strlen($motivo) < 5) {
            throw new RuntimeException('El motivo de rechazo debe tener al menos 5 caracteres.');
        }
        $this->_transicionar($sdpId, 'rechazada', $userId, [
            'rechazada_por' => $userId,
            'rechazada_at'  => date('Y-m-d H:i:s'),
            'motivo_rechazo'=> substr($motivo, 0, 500),
        ], $motivo);
    }

    // ============================================================
    // BORRAR (soft) — sólo en Pendiente o Rechazada
    // ============================================================
    public function eliminar(int $sdpId, int $userId): void
    {
        $sdp = $this->CI->SolicitudPagoRepo->find($sdpId);
        if (!$sdp) throw new RuntimeException('SDP no encontrada.');
        $estado = $this->_estadoById((int)$sdp['estado_id']);
        if (!in_array($estado['codigo'], ['pendiente','rechazada'], true)) {
            throw new RuntimeException('Sólo se puede eliminar una SDP en estado Pendiente o Rechazada.');
        }
        $this->CI->SolicitudPagoRepo->softDelete($sdpId);
        $this->CI->audit->log('sdp.eliminar', 'gmc_solicitudes_pago', $sdpId, $sdp, null);
    }

    // ============================================================
    // INTERNOS
    // ============================================================
    private function _transicionar(int $sdpId, string $estadoDestinoCodigo, int $userId, array $extraPayload, ?string $comentario = null): void
    {
        $sdp = $this->CI->SolicitudPagoRepo->find($sdpId);
        if (!$sdp) throw new RuntimeException('SDP no encontrada.');

        $estadoActual  = $this->_estadoById((int)$sdp['estado_id']);
        $estadoDestino = $this->_estado($estadoDestinoCodigo);

        // Validar transición permitida
        $permitidos = $this->transiciones[$estadoActual['codigo']] ?? [];
        if (!in_array($estadoDestinoCodigo, $permitidos, true)) {
            throw new RuntimeException("No se puede pasar de {$estadoActual['nombre']} a {$estadoDestino['nombre']}.");
        }

        $this->CI->db->trans_start();

        $payload = array_merge($extraPayload, [
            'estado_id'  => (int)$estadoDestino['id'],
            'updated_by' => $userId,
        ]);
        $this->CI->SolicitudPagoRepo->update($sdpId, $payload);

        // Log granular
        $this->CI->SdpEstadoLogRepo->logTransicion(
            $sdpId,
            (int)$estadoActual['id'],
            (int)$estadoDestino['id'],
            $userId,
            $comentario
        );

        // Auditoría
        $this->CI->audit->log("sdp.{$estadoDestinoCodigo}", 'gmc_solicitudes_pago', $sdpId,
            ['estado' => $estadoActual['codigo']],
            array_merge(['estado' => $estadoDestino['codigo']], $extraPayload)
        );

        $this->CI->db->trans_complete();

        // Notificar al solicitante (created_by) y a Finanzas según el caso
        $this->_notificarPorTransicion($sdpId, $sdp, $estadoActual, $estadoDestino, $comentario);
    }

    private function _estado(string $codigo): array
    {
        $row = $this->CI->db->where(['dominio' => 'solicitud_pago', 'codigo' => $codigo])
                            ->get('gmc_estados')->row_array();
        if (!$row) throw new RuntimeException("Estado '{$codigo}' no existe en dominio 'solicitud_pago'.");
        return $row;
    }

    private function _estadoById(int $id): array
    {
        $row = $this->CI->db->where('id', $id)->get('gmc_estados')->row_array();
        if (!$row) throw new RuntimeException("Estado id={$id} no existe.");
        return $row;
    }

    private function _notificarFinanzas(int $sdpId, string $tipo, string $asunto): void
    {
        $emails = $this->CI->db->select('u.email')
            ->from('gmc_usuarios u')
            ->join('gmc_usuarios_roles ur', 'ur.usuario_id = u.id')
            ->join('gmc_roles r', 'r.id = ur.rol_id')
            ->where('r.codigo', 'finanzas')
            ->where('u.activo', 1)
            ->where('u.deleted_at IS NULL', null, false)
            ->get()->result_array();
        $url = base_url("finanzas/sdp/{$sdpId}");
        $cuerpo = "Hay una novedad en una Solicitud de Pago.\n\nVer detalle: {$url}";
        foreach ($emails as $e) {
            $this->CI->notifier->encolar($tipo, $e['email'], $asunto, $cuerpo, ['sdp_id' => $sdpId]);
        }
    }

    private function _notificarPorTransicion(int $sdpId, array $sdp, array $estadoActual, array $estadoNuevo, ?string $comentario): void
    {
        $url = base_url("finanzas/sdp/{$sdpId}");

        // Notificar al creador
        if (!empty($sdp['created_by'])) {
            $u = $this->CI->db->where('id', (int)$sdp['created_by'])->get('gmc_usuarios')->row_array();
            if ($u && !empty($u['email'])) {
                $asunto = "SDP {$sdp['numero']}: {$estadoNuevo['nombre']}";
                $cuerpo = "Tu Solicitud de Pago {$sdp['numero']} pasó de \"{$estadoActual['nombre']}\" a \"{$estadoNuevo['nombre']}\"."
                    . ($comentario ? "\n\nMotivo/comentario: {$comentario}" : '')
                    . "\n\nVer detalle: {$url}";
                $this->CI->notifier->encolar("sdp.{$estadoNuevo['codigo']}", $u['email'], $asunto, $cuerpo, ['sdp_id' => $sdpId]);
            }
        }

        // Notificar a Finanzas si fue creada/validada (acción los espera)
        if (in_array($estadoNuevo['codigo'], ['validada','programada'], true)) {
            $this->_notificarFinanzas($sdpId, "sdp.{$estadoNuevo['codigo']}", "SDP {$sdp['numero']}: {$estadoNuevo['nombre']}");
        }
    }
}
