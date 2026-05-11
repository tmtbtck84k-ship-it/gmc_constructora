<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'services/CorrelativoService.php';
require_once APPPATH . 'services/MonedaService.php';

/**
 * CompraService — encapsula creación/edición/anulación de compras con sus items.
 *
 * Estados: borrador, recibida (final por defecto), anulada (final).
 * Transiciones: borrador → recibida, borrador|recibida → anulada.
 */
class CompraService
{
    /** @var CI_Controller */
    protected $CI;
    protected $correlativo;
    protected $moneda;

    protected $transiciones = [
        'borrador' => ['recibida', 'anulada'],
        'recibida' => ['anulada'],
        'anulada'  => [],
    ];

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->model(['CompraRepo','CompraItemRepo']);
        $this->CI->load->library('Audit');
        $this->correlativo = new CorrelativoService();
        $this->moneda      = new MonedaService();
    }

    public function crear(array $input, array $items, int $userId): int
    {
        $estadoBorrador = $this->_estado('borrador');

        $monedaId = (int)$input['moneda_id'];
        $iva      = (float)($input['monto_iva'] ?? 0);
        $fechaRef = $input['fecha_recepcion'] ?? date('Y-m-d');

        $this->CI->db->trans_start();

        $numero = $this->correlativo->next('compra');
        $compraId = $this->CI->CompraRepo->create([
            'numero'           => $numero,
            'proyecto_id'      => !empty($input['proyecto_id']) ? (int)$input['proyecto_id'] : null,
            'centro_costo_id'  => (int)$input['centro_costo_id'],
            'proveedor_id'     => (int)$input['proveedor_id'],
            'moneda_id'        => $monedaId,
            'fecha_recepcion'  => $fechaRef,
            'documento_tipo'   => $input['documento_tipo']   ?? null,
            'documento_numero' => $input['documento_numero'] ?? null,
            'monto_neto'       => 0,
            'monto_iva'        => $iva,
            'monto_total'      => 0,
            'tipo_cambio_clp'  => null,
            'monto_total_clp'  => 0,
            'solicitud_pago_id'=> !empty($input['solicitud_pago_id']) ? (int)$input['solicitud_pago_id'] : null,
            'rinde_id'         => !empty($input['rinde_id']) ? (int)$input['rinde_id'] : null,
            'observaciones'    => $input['observaciones'] ?? null,
            'estado_id'        => (int)$estadoBorrador['id'],
            'created_by'       => $userId,
            'updated_by'       => $userId,
        ]);

        // Sincronizar items (calcula neto)
        $totalNeto = $this->CI->CompraItemRepo->syncItems($compraId, $items);
        $totalConIva = round($totalNeto + $iva, 2);

        // Snapshot multimoneda
        $snap = $this->moneda->snapshot($monedaId, $totalConIva, $fechaRef);

        $this->CI->CompraRepo->update($compraId, [
            'monto_neto'      => $totalNeto,
            'monto_total'     => $totalConIva,
            'tipo_cambio_clp' => $snap['tipo_cambio_clp'],
            'monto_total_clp' => $snap['monto_total_clp'],
            'updated_by'      => $userId,
        ]);

        $this->CI->audit->log('compra.crear', 'gmc_compras', $compraId, null, [
            'numero' => $numero, 'monto_total' => $totalConIva, 'monto_total_clp' => $snap['monto_total_clp'],
        ]);

        $this->CI->db->trans_complete();
        return $compraId;
    }

    public function editar(int $compraId, array $input, array $items, int $userId): void
    {
        $compra = $this->CI->CompraRepo->find($compraId);
        if (!$compra) throw new RuntimeException('Compra no encontrada.');
        $estado = $this->_estadoById((int)$compra['estado_id']);
        if ($estado['codigo'] !== 'borrador') {
            throw new RuntimeException('Sólo se puede editar una compra en Borrador.');
        }

        $monedaId = (int)($input['moneda_id'] ?? $compra['moneda_id']);
        $iva      = (float)($input['monto_iva'] ?? $compra['monto_iva']);
        $fechaRef = $input['fecha_recepcion'] ?? $compra['fecha_recepcion'];

        $this->CI->db->trans_start();

        $totalNeto   = $this->CI->CompraItemRepo->syncItems($compraId, $items);
        $totalConIva = round($totalNeto + $iva, 2);
        $snap        = $this->moneda->snapshot($monedaId, $totalConIva, $fechaRef);

        $payload = [
            'proyecto_id'       => !empty($input['proyecto_id']) ? (int)$input['proyecto_id'] : null,
            'centro_costo_id'   => (int)$input['centro_costo_id'],
            'proveedor_id'      => (int)$input['proveedor_id'],
            'moneda_id'         => $monedaId,
            'fecha_recepcion'   => $fechaRef,
            'documento_tipo'    => $input['documento_tipo']   ?? null,
            'documento_numero'  => $input['documento_numero'] ?? null,
            'monto_neto'        => $totalNeto,
            'monto_iva'         => $iva,
            'monto_total'       => $totalConIva,
            'tipo_cambio_clp'   => $snap['tipo_cambio_clp'],
            'monto_total_clp'   => $snap['monto_total_clp'],
            'solicitud_pago_id' => !empty($input['solicitud_pago_id']) ? (int)$input['solicitud_pago_id'] : null,
            'rinde_id'          => !empty($input['rinde_id']) ? (int)$input['rinde_id'] : null,
            'observaciones'     => $input['observaciones'] ?? null,
            'updated_by'        => $userId,
        ];
        $this->CI->CompraRepo->update($compraId, $payload);
        $this->CI->audit->logChanges('compra.editar', 'gmc_compras', $compraId, $compra, $payload);

        $this->CI->db->trans_complete();
    }

    public function confirmar(int $compraId, int $userId): void
    {
        $this->_transicion($compraId, 'recibida', $userId);
    }

    public function anular(int $compraId, int $userId, string $motivo): void
    {
        $motivo = trim($motivo);
        if (strlen($motivo) < 5) throw new RuntimeException('Motivo de anulación demasiado corto.');
        $this->_transicion($compraId, 'anulada', $userId, ['observaciones' => $motivo]);
    }

    private function _transicion(int $id, string $destinoCodigo, int $userId, array $extra = []): void
    {
        $compra = $this->CI->CompraRepo->find($id);
        if (!$compra) throw new RuntimeException('Compra no encontrada.');
        $actual  = $this->_estadoById((int)$compra['estado_id']);
        $destino = $this->_estado($destinoCodigo);
        if (!in_array($destinoCodigo, $this->transiciones[$actual['codigo']] ?? [], true)) {
            throw new RuntimeException("No se puede pasar de {$actual['nombre']} a {$destino['nombre']}.");
        }
        $payload = array_merge($extra, ['estado_id' => (int)$destino['id'], 'updated_by' => $userId]);
        $this->CI->CompraRepo->update($id, $payload);
        $this->CI->audit->log("compra.{$destinoCodigo}", 'gmc_compras', $id,
            ['estado' => $actual['codigo']],
            array_merge(['estado' => $destino['codigo']], $extra)
        );
    }

    private function _estado(string $codigo): array
    {
        $row = $this->CI->db->where(['dominio' => 'compra', 'codigo' => $codigo])->get('gmc_estados')->row_array();
        if (!$row) throw new RuntimeException("Estado '{$codigo}' no existe en dominio 'compra'.");
        return $row;
    }

    private function _estadoById(int $id): array
    {
        $row = $this->CI->db->where('id', $id)->get('gmc_estados')->row_array();
        if (!$row) throw new RuntimeException("Estado id={$id} no existe.");
        return $row;
    }
}
