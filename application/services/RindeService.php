<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'services/CorrelativoService.php';
require_once APPPATH . 'services/MonedaService.php';
require_once APPPATH . 'services/SolicitudPagoService.php';

/**
 * RindeService — Rinde de Gastos.
 *
 * Estados: borrador → enviada → aprobada → pagada (final)
 *                    └→ rechazada (final con motivo)
 *
 * Reglas de aprobación:
 *  - Si el rinde tiene proyecto: lo aprueba el JP del proyecto, o gerencia/admin.
 *  - Si NO tiene proyecto (admin general): lo aprueba Finanzas, gerencia o admin.
 */
class RindeService
{
    /** @var CI_Controller */
    protected $CI;
    protected $correlativo;
    protected $moneda;

    protected $transiciones = [
        'borrador'  => ['enviada'],
        'enviada'   => ['aprobada', 'rechazada'],
        'aprobada'  => ['pagada'],
        'pagada'    => [],
        'rechazada' => [],
    ];

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->model(['RindeRepo','RindeItemRepo']);
        $this->CI->load->library(['Audit','Notifier','Acl']);
        $this->correlativo = new CorrelativoService();
        $this->moneda      = new MonedaService();
    }

    public function crear(array $input, array $items, int $userId): int
    {
        $estadoBorrador = $this->_estado('borrador');
        $monedaId = (int)$input['moneda_id'];
        $fechaRef = $input['fecha_rendicion'] ?? date('Y-m-d');

        $this->CI->db->trans_start();
        $numero = $this->correlativo->next('rinde');

        $rindeId = $this->CI->RindeRepo->create([
            'numero'           => $numero,
            'proyecto_id'      => !empty($input['proyecto_id']) ? (int)$input['proyecto_id'] : null,
            'centro_costo_id'  => (int)$input['centro_costo_id'],
            'usuario_id'       => $userId,
            'moneda_id'        => $monedaId,
            'fecha_rendicion'  => $fechaRef,
            'monto_total'      => 0,
            'observaciones'    => $input['observaciones'] ?? null,
            'estado_id'        => (int)$estadoBorrador['id'],
            'created_by'       => $userId,
            'updated_by'       => $userId,
        ]);

        $total = $this->CI->RindeItemRepo->syncItems($rindeId, $items);
        $snap  = $this->moneda->snapshot($monedaId, $total, $fechaRef);

        $this->CI->RindeRepo->update($rindeId, [
            'monto_total'     => $total,
            'tipo_cambio_clp' => $snap['tipo_cambio_clp'],
            'monto_total_clp' => $snap['monto_total_clp'],
        ]);

        $this->CI->audit->log('rinde.crear', 'gmc_rindes_gastos', $rindeId, null, [
            'numero' => $numero, 'monto_total' => $total, 'monto_total_clp' => $snap['monto_total_clp'],
        ]);

        $this->CI->db->trans_complete();
        return $rindeId;
    }

    public function editar(int $rindeId, array $input, array $items, int $userId): void
    {
        $rinde = $this->CI->RindeRepo->find($rindeId);
        if (!$rinde) throw new RuntimeException('Rinde no encontrado.');
        $estado = $this->_estadoById((int)$rinde['estado_id']);
        if ($estado['codigo'] !== 'borrador') {
            throw new RuntimeException('Sólo se puede editar un rinde en Borrador.');
        }

        $monedaId = (int)($input['moneda_id'] ?? $rinde['moneda_id']);
        $fechaRef = $input['fecha_rendicion'] ?? $rinde['fecha_rendicion'];

        $this->CI->db->trans_start();
        $total = $this->CI->RindeItemRepo->syncItems($rindeId, $items);
        $snap  = $this->moneda->snapshot($monedaId, $total, $fechaRef);

        $payload = [
            'proyecto_id'      => !empty($input['proyecto_id']) ? (int)$input['proyecto_id'] : null,
            'centro_costo_id'  => (int)$input['centro_costo_id'],
            'moneda_id'        => $monedaId,
            'fecha_rendicion'  => $fechaRef,
            'observaciones'    => $input['observaciones'] ?? null,
            'monto_total'      => $total,
            'tipo_cambio_clp'  => $snap['tipo_cambio_clp'],
            'monto_total_clp'  => $snap['monto_total_clp'],
            'updated_by'       => $userId,
        ];
        $this->CI->RindeRepo->update($rindeId, $payload);
        $this->CI->audit->logChanges('rinde.editar', 'gmc_rindes_gastos', $rindeId, $rinde, $payload);
        $this->CI->db->trans_complete();
    }

    public function enviar(int $rindeId, int $userId): void
    {
        $rinde = $this->CI->RindeRepo->find($rindeId);
        if (!$rinde) throw new RuntimeException('Rinde no encontrado.');
        $items = $this->CI->RindeItemRepo->listByRinde($rindeId);
        if (!$items)                          throw new RuntimeException('Agrega al menos un ítem antes de enviar.');
        if ((float)$rinde['monto_total'] <= 0) throw new RuntimeException('El monto total debe ser mayor a 0.');

        $this->_transicion($rindeId, 'enviada', $userId);

        // Notificar al aprobador correspondiente
        $rindeFull = $this->CI->RindeRepo->findFull($rindeId);
        $emails = $this->_aprobadores($rindeFull);
        foreach ($emails as $em) {
            $this->CI->notifier->encolar(
                'rinde.enviada',
                $em,
                "Rinde {$rinde['numero']} requiere aprobación",
                "El rinde {$rinde['numero']} fue enviado para aprobación.\n\nVer: " . base_url("compras/rindes/{$rindeId}"),
                ['rinde_id' => $rindeId]
            );
        }
    }

    public function aprobar(int $rindeId, int $userId): void
    {
        $rinde = $this->CI->RindeRepo->findFull($rindeId);
        if (!$rinde) throw new RuntimeException('Rinde no encontrado.');
        if (!$this->puedeAprobar($rinde, $userId)) {
            throw new RuntimeException('No tienes permiso para aprobar este rinde. ' .
                ($rinde['proyecto_id']
                    ? 'Sólo el Jefe del Proyecto, Gerencia o Administrador pueden aprobarlo.'
                    : 'Sólo Finanzas, Gerencia o Administrador pueden aprobar rindes de Administración.'));
        }
        $this->_transicion($rindeId, 'aprobada', $userId, [
            'aprobada_por' => $userId,
            'aprobada_at'  => date('Y-m-d H:i:s'),
        ]);

        // Notificar al solicitante
        if (!empty($rinde['usuario_email'])) {
            $this->CI->notifier->encolar('rinde.aprobada', $rinde['usuario_email'],
                "Rinde {$rinde['numero']} aprobado",
                "Tu rinde {$rinde['numero']} fue aprobado.\n\nVer: " . base_url("compras/rindes/{$rindeId}"),
                ['rinde_id' => $rindeId]);
        }
    }

    public function rechazar(int $rindeId, int $userId, string $motivo): void
    {
        $rinde = $this->CI->RindeRepo->findFull($rindeId);
        if (!$rinde) throw new RuntimeException('Rinde no encontrado.');
        if (!$this->puedeAprobar($rinde, $userId)) {
            throw new RuntimeException('No tienes permiso para rechazar este rinde.');
        }
        $motivo = trim($motivo);
        if (strlen($motivo) < 5) throw new RuntimeException('El motivo de rechazo debe tener al menos 5 caracteres.');

        $this->_transicion($rindeId, 'rechazada', $userId, [
            'rechazada_por'  => $userId,
            'rechazada_at'   => date('Y-m-d H:i:s'),
            'motivo_rechazo' => substr($motivo, 0, 500),
        ]);

        if (!empty($rinde['usuario_email'])) {
            $this->CI->notifier->encolar('rinde.rechazada', $rinde['usuario_email'],
                "Rinde {$rinde['numero']} rechazado",
                "Tu rinde {$rinde['numero']} fue rechazado.\n\nMotivo: {$motivo}\n\nVer: " . base_url("compras/rindes/{$rindeId}"),
                ['rinde_id' => $rindeId]);
        }
    }

    /**
     * Genera una SDP a partir de un rinde aprobado y la enlaza.
     * El usuario que rinde se usa como "proveedor" lógico — pero como las SDPs requieren
     * un proveedor formal, esta integración sólo es válida si existe un proveedor con
     * el mismo RUT del usuario (típicamente el caso de "honorarios reembolsables").
     * Si no existe ese proveedor, lanza error claro.
     */
    public function generarSdpDePago(int $rindeId, int $proveedorId, int $tipoGastoId, int $userId): int
    {
        $rinde = $this->CI->RindeRepo->findFull($rindeId);
        if (!$rinde) throw new RuntimeException('Rinde no encontrado.');
        if ($rinde['estado_codigo'] !== 'aprobada') {
            throw new RuntimeException('Sólo se puede generar SDP de un rinde Aprobado.');
        }
        if ($rinde['solicitud_pago_id']) {
            throw new RuntimeException('Este rinde ya tiene una SDP enlazada.');
        }

        $sdpService = new SolicitudPagoService();
        $sdpId = $sdpService->crear([
            'proyecto_id'      => $rinde['proyecto_id'],
            'centro_costo_id'  => $rinde['centro_costo_id'],
            'proveedor_id'     => $proveedorId,
            'tipo_gasto_id'    => $tipoGastoId,
            'moneda_id'        => $rinde['moneda_id'],
            'monto_neto'       => (float)$rinde['monto_total'],
            'monto_iva'        => 0,
            'monto_total'      => (float)$rinde['monto_total'],
            'fecha_emision'    => date('Y-m-d'),
            'descripcion'      => "Pago rinde de gastos {$rinde['numero']} (usuario: {$rinde['usuario_nombres']} {$rinde['usuario_apellidos']})",
        ], $userId);

        // Enlazar
        $this->CI->RindeRepo->update($rindeId, ['solicitud_pago_id' => $sdpId]);
        $this->CI->audit->log('rinde.sdp_generada', 'gmc_rindes_gastos', $rindeId, null, ['sdp_id' => $sdpId]);

        return $sdpId;
    }

    /** Marca el rinde como pagado (típicamente al pagarse la SDP enlazada). */
    public function marcarPagada(int $rindeId, int $userId): void
    {
        $this->_transicion($rindeId, 'pagada', $userId);
    }

    public function puedeAprobar(array $rindeFull, int $userId): bool
    {
        // Admin y gerencia siempre pueden
        if ($this->CI->acl->isAdmin($userId)) return true;
        $roles = $this->CI->acl->rolesOf($userId);
        if (in_array('gerencia', $roles, true)) return true;

        if ($rindeFull['proyecto_id']) {
            // Rinde con proyecto: aprueba el JP del proyecto
            return ((int)$rindeFull['jefe_proyecto_id']) === $userId;
        }
        // Rinde sin proyecto (admin general): aprueba Finanzas
        return in_array('finanzas', $roles, true);
    }

    private function _transicion(int $id, string $destinoCodigo, int $userId, array $extra = []): void
    {
        $rinde = $this->CI->RindeRepo->find($id);
        if (!$rinde) throw new RuntimeException('Rinde no encontrado.');
        $actual  = $this->_estadoById((int)$rinde['estado_id']);
        $destino = $this->_estado($destinoCodigo);
        if (!in_array($destinoCodigo, $this->transiciones[$actual['codigo']] ?? [], true)) {
            throw new RuntimeException("No se puede pasar de {$actual['nombre']} a {$destino['nombre']}.");
        }
        $payload = array_merge($extra, ['estado_id' => (int)$destino['id'], 'updated_by' => $userId]);
        $this->CI->RindeRepo->update($id, $payload);
        $this->CI->audit->log("rinde.{$destinoCodigo}", 'gmc_rindes_gastos', $id,
            ['estado' => $actual['codigo']],
            array_merge(['estado' => $destino['codigo']], $extra)
        );
    }

    private function _aprobadores(array $rindeFull): array
    {
        $emails = [];
        if ($rindeFull['proyecto_id'] && !empty($rindeFull['jefe_proyecto_id'])) {
            $jp = $this->CI->db->where('id', (int)$rindeFull['jefe_proyecto_id'])->get('gmc_usuarios')->row_array();
            if ($jp && !empty($jp['email'])) $emails[] = $jp['email'];
        } else {
            // Sin proyecto: a Finanzas
            $rs = $this->CI->db->select('u.email')
                ->from('gmc_usuarios u')
                ->join('gmc_usuarios_roles ur', 'ur.usuario_id = u.id')
                ->join('gmc_roles r', 'r.id = ur.rol_id')
                ->where_in('r.codigo', ['finanzas','gerencia','admin'])
                ->where('u.activo', 1)->where('u.deleted_at IS NULL', null, false)
                ->get()->result_array();
            foreach ($rs as $row) $emails[] = $row['email'];
        }
        return array_unique($emails);
    }

    private function _estado(string $codigo): array
    {
        $row = $this->CI->db->where(['dominio' => 'rinde', 'codigo' => $codigo])->get('gmc_estados')->row_array();
        if (!$row) throw new RuntimeException("Estado '{$codigo}' no existe en dominio 'rinde'.");
        return $row;
    }

    private function _estadoById(int $id): array
    {
        $row = $this->CI->db->where('id', $id)->get('gmc_estados')->row_array();
        if (!$row) throw new RuntimeException("Estado id={$id} no existe.");
        return $row;
    }
}
