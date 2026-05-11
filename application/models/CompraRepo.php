<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'core/MY_Model.php';

class CompraRepo extends MY_Model
{
    protected $table = 'gmc_compras';
    protected $useSoftDelete = true;
    protected $useTimestamps = true;
    protected $useAudit = true;
    protected $fillable = [
        'numero','proyecto_id','centro_costo_id','proveedor_id','moneda_id',
        'fecha_recepcion','documento_tipo','documento_numero',
        'monto_neto','monto_iva','monto_total','tipo_cambio_clp','monto_total_clp',
        'solicitud_pago_id','rinde_id','observaciones','estado_id',
    ];

    public function bandeja(array $filters = [], int $limit = 50, int $offset = 0): array
    {
        $q = $this->db
            ->select('c.*, '
                . 'p.codigo AS proyecto_codigo, p.nombre AS proyecto_nombre, '
                . 'cc.codigo AS cc_codigo, cc.nombre AS cc_nombre, '
                . 'pr.razon_social AS proveedor, pr.rut AS proveedor_rut, '
                . 'm.codigo AS moneda, m.simbolo AS moneda_simbolo, m.decimales AS moneda_decimales, '
                . 'e.codigo AS estado_codigo, e.nombre AS estado_nombre, e.color AS estado_color, '
                . 'sdp.numero AS sdp_numero, '
                . 'r.numero AS rinde_numero')
            ->from($this->table . ' c')
            ->join('gmc_proyectos p', 'p.id = c.proyecto_id', 'left')
            ->join('gmc_centros_costo cc', 'cc.id = c.centro_costo_id', 'left')
            ->join('gmc_proveedores pr', 'pr.id = c.proveedor_id', 'left')
            ->join('gmc_monedas m', 'm.id = c.moneda_id', 'left')
            ->join('gmc_estados e', 'e.id = c.estado_id', 'left')
            ->join('gmc_solicitudes_pago sdp', 'sdp.id = c.solicitud_pago_id', 'left')
            ->join('gmc_rindes_gastos r', 'r.id = c.rinde_id', 'left')
            ->where('c.deleted_at IS NULL', null, false)
            ->order_by('c.id', 'DESC');

        if (!empty($filters['q'])) {
            $term = '%' . $filters['q'] . '%';
            $q->group_start()
              ->like('c.numero', $term)
              ->or_like('c.documento_numero', $term)
              ->or_like('pr.razon_social', $term)
              ->group_end();
        }
        if (!empty($filters['proyecto_id'])) $q->where('c.proyecto_id', (int)$filters['proyecto_id']);
        if (!empty($filters['proveedor_id'])) $q->where('c.proveedor_id', (int)$filters['proveedor_id']);
        if (!empty($filters['estado_id']))   $q->where('c.estado_id', (int)$filters['estado_id']);
        if (!empty($filters['desde']))       $q->where('c.fecha_recepcion >=', $filters['desde']);
        if (!empty($filters['hasta']))       $q->where('c.fecha_recepcion <=', $filters['hasta']);

        $countQ = clone $q;
        $total = (int)$countQ->count_all_results('', false);
        $q->limit($limit, $offset);
        return ['rows' => $q->get()->result_array(), 'total' => $total, 'limit' => $limit, 'offset' => $offset];
    }

    public function findFull(int $id): ?array
    {
        return $this->db
            ->select('c.*, '
                . 'p.codigo AS proyecto_codigo, p.nombre AS proyecto_nombre, '
                . 'cc.codigo AS cc_codigo, cc.nombre AS cc_nombre, cc.es_administracion AS cc_es_admin, '
                . 'pr.rut AS proveedor_rut, pr.razon_social AS proveedor, '
                . 'm.codigo AS moneda, m.simbolo AS moneda_simbolo, m.decimales AS moneda_decimales, '
                . 'e.codigo AS estado_codigo, e.nombre AS estado_nombre, e.color AS estado_color, '
                . 'sdp.numero AS sdp_numero, r.numero AS rinde_numero')
            ->from($this->table . ' c')
            ->join('gmc_proyectos p', 'p.id = c.proyecto_id', 'left')
            ->join('gmc_centros_costo cc', 'cc.id = c.centro_costo_id', 'left')
            ->join('gmc_proveedores pr', 'pr.id = c.proveedor_id', 'left')
            ->join('gmc_monedas m', 'm.id = c.moneda_id', 'left')
            ->join('gmc_estados e', 'e.id = c.estado_id', 'left')
            ->join('gmc_solicitudes_pago sdp', 'sdp.id = c.solicitud_pago_id', 'left')
            ->join('gmc_rindes_gastos r', 'r.id = c.rinde_id', 'left')
            ->where('c.id', $id)
            ->where('c.deleted_at IS NULL', null, false)
            ->get()->row_array() ?: null;
    }
}
