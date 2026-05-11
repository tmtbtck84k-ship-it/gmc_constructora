<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'core/MY_Model.php';

class SolicitudPagoRepo extends MY_Model
{
    protected $table = 'gmc_solicitudes_pago';
    protected $useSoftDelete = true;
    protected $useTimestamps = true;
    protected $useAudit = true;
    protected $fillable = [
        'numero','proyecto_id','centro_costo_id','proveedor_id','tipo_gasto_id','moneda_id',
        'monto_neto','monto_iva','monto_total','tipo_cambio_clp','monto_total_clp',
        'fecha_emision','fecha_vencimiento','fecha_programada','fecha_pago',
        'documento_tipo','documento_numero','forma_pago',
        'descripcion','comentarios','motivo_rechazo','estado_id',
        'validada_por','validada_at','programada_por','programada_at',
        'pagada_por','pagada_at','rechazada_por','rechazada_at',
    ];

    /**
     * Bandeja con filtros + joins.
     */
    public function bandeja(array $filters = [], int $limit = 50, int $offset = 0): array
    {
        $q = $this->db
            ->select('s.*, '
                . 'p.codigo AS proyecto_codigo, p.nombre AS proyecto_nombre, '
                . 'cc.codigo AS cc_codigo, cc.nombre AS cc_nombre, '
                . 'pr.razon_social AS proveedor, pr.rut AS proveedor_rut, '
                . 'tg.codigo AS tg_codigo, tg.nombre AS tg_nombre, '
                . 'm.codigo AS moneda, m.simbolo AS moneda_simbolo, m.decimales AS moneda_decimales, '
                . 'e.codigo AS estado_codigo, e.nombre AS estado_nombre, e.color AS estado_color, e.es_final AS estado_es_final')
            ->from($this->table . ' s')
            ->join('gmc_proyectos p', 'p.id = s.proyecto_id', 'left')
            ->join('gmc_centros_costo cc', 'cc.id = s.centro_costo_id', 'left')
            ->join('gmc_proveedores pr', 'pr.id = s.proveedor_id', 'left')
            ->join('gmc_tipos_gasto tg', 'tg.id = s.tipo_gasto_id', 'left')
            ->join('gmc_monedas m', 'm.id = s.moneda_id', 'left')
            ->join('gmc_estados e', 'e.id = s.estado_id', 'left')
            ->where('s.deleted_at IS NULL', null, false)
            ->order_by('s.id', 'DESC');

        if (!empty($filters['q'])) {
            $term = '%' . $filters['q'] . '%';
            $q->group_start()
              ->like('s.numero', $term)
              ->or_like('s.documento_numero', $term)
              ->or_like('pr.razon_social', $term)
              ->group_end();
        }
        if (!empty($filters['proyecto_id'])) $q->where('s.proyecto_id', (int)$filters['proyecto_id']);
        if (!empty($filters['proveedor_id'])) $q->where('s.proveedor_id', (int)$filters['proveedor_id']);
        if (!empty($filters['estado_id']))   $q->where('s.estado_id', (int)$filters['estado_id']);
        if (!empty($filters['cc_id']))       $q->where('s.centro_costo_id', (int)$filters['cc_id']);
        if (!empty($filters['desde']))       $q->where('s.fecha_emision >=', $filters['desde']);
        if (!empty($filters['hasta']))       $q->where('s.fecha_emision <=', $filters['hasta']);

        // Total para paginación (clone antes de aplicar limit)
        $countQ = clone $q;
        $total = (int)$countQ->count_all_results('', false);

        $q->limit($limit, $offset);
        $rows = $q->get()->result_array();

        return ['rows' => $rows, 'total' => $total, 'limit' => $limit, 'offset' => $offset];
    }

    /**
     * Detalle completo con joins, para la pantalla "ver".
     */
    public function findFull(int $id): ?array
    {
        return $this->db
            ->select('s.*, '
                . 'p.codigo AS proyecto_codigo, p.nombre AS proyecto_nombre, '
                . 'cc.codigo AS cc_codigo, cc.nombre AS cc_nombre, cc.es_administracion AS cc_es_admin, '
                . 'pr.rut AS proveedor_rut, pr.razon_social AS proveedor, pr.email AS proveedor_email, '
                . 'tg.codigo AS tg_codigo, tg.nombre AS tg_nombre, '
                . 'm.codigo AS moneda, m.simbolo AS moneda_simbolo, m.decimales AS moneda_decimales, '
                . 'e.codigo AS estado_codigo, e.nombre AS estado_nombre, e.color AS estado_color, e.es_final AS estado_es_final')
            ->from($this->table . ' s')
            ->join('gmc_proyectos p', 'p.id = s.proyecto_id', 'left')
            ->join('gmc_centros_costo cc', 'cc.id = s.centro_costo_id', 'left')
            ->join('gmc_proveedores pr', 'pr.id = s.proveedor_id', 'left')
            ->join('gmc_tipos_gasto tg', 'tg.id = s.tipo_gasto_id', 'left')
            ->join('gmc_monedas m', 'm.id = s.moneda_id', 'left')
            ->join('gmc_estados e', 'e.id = s.estado_id', 'left')
            ->where('s.id', $id)
            ->where('s.deleted_at IS NULL', null, false)
            ->get()->row_array() ?: null;
    }

    /** KPIs del dashboard (Sprint 5 los consume). */
    public function countByEstadoCodigo(string $codigo): int
    {
        return (int)$this->db
            ->from($this->table . ' s')
            ->join('gmc_estados e', 'e.id = s.estado_id')
            ->where('e.dominio', 'solicitud_pago')
            ->where('e.codigo', $codigo)
            ->where('s.deleted_at IS NULL', null, false)
            ->count_all_results();
    }
}
