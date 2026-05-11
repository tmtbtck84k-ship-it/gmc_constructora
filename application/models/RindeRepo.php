<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'core/MY_Model.php';

class RindeRepo extends MY_Model
{
    protected $table = 'gmc_rindes_gastos';
    protected $useSoftDelete = true;
    protected $useTimestamps = true;
    protected $useAudit = true;
    protected $fillable = [
        'numero','proyecto_id','centro_costo_id','usuario_id','moneda_id',
        'fecha_rendicion','monto_total','tipo_cambio_clp','monto_total_clp',
        'solicitud_pago_id','observaciones','motivo_rechazo','estado_id',
        'aprobada_por','aprobada_at','rechazada_por','rechazada_at',
    ];

    public function bandeja(array $filters = [], int $limit = 50, int $offset = 0): array
    {
        $q = $this->db
            ->select('r.*, '
                . 'p.codigo AS proyecto_codigo, p.nombre AS proyecto_nombre, p.jefe_proyecto_id, '
                . 'cc.codigo AS cc_codigo, cc.nombre AS cc_nombre, '
                . 'u.nombres AS usuario_nombres, u.apellidos AS usuario_apellidos, u.email AS usuario_email, '
                . 'm.codigo AS moneda, m.simbolo AS moneda_simbolo, m.decimales AS moneda_decimales, '
                . 'e.codigo AS estado_codigo, e.nombre AS estado_nombre, e.color AS estado_color, '
                . 'sdp.numero AS sdp_numero')
            ->from($this->table . ' r')
            ->join('gmc_proyectos p', 'p.id = r.proyecto_id', 'left')
            ->join('gmc_centros_costo cc', 'cc.id = r.centro_costo_id', 'left')
            ->join('gmc_usuarios u', 'u.id = r.usuario_id', 'left')
            ->join('gmc_monedas m', 'm.id = r.moneda_id', 'left')
            ->join('gmc_estados e', 'e.id = r.estado_id', 'left')
            ->join('gmc_solicitudes_pago sdp', 'sdp.id = r.solicitud_pago_id', 'left')
            ->where('r.deleted_at IS NULL', null, false)
            ->order_by('r.id', 'DESC');

        if (!empty($filters['q'])) {
            $term = '%' . $filters['q'] . '%';
            $q->group_start()->like('r.numero', $term)->or_like('u.nombres', $term)->or_like('u.apellidos', $term)->group_end();
        }
        if (!empty($filters['proyecto_id'])) $q->where('r.proyecto_id', (int)$filters['proyecto_id']);
        if (!empty($filters['estado_id']))   $q->where('r.estado_id', (int)$filters['estado_id']);
        if (!empty($filters['usuario_id']))  $q->where('r.usuario_id', (int)$filters['usuario_id']);
        if (!empty($filters['desde']))       $q->where('r.fecha_rendicion >=', $filters['desde']);
        if (!empty($filters['hasta']))       $q->where('r.fecha_rendicion <=', $filters['hasta']);

        $countQ = clone $q;
        $total = (int)$countQ->count_all_results('', false);
        $q->limit($limit, $offset);
        return ['rows' => $q->get()->result_array(), 'total' => $total];
    }

    public function findFull(int $id): ?array
    {
        return $this->db
            ->select('r.*, '
                . 'p.codigo AS proyecto_codigo, p.nombre AS proyecto_nombre, p.jefe_proyecto_id, '
                . 'cc.codigo AS cc_codigo, cc.nombre AS cc_nombre, '
                . 'u.nombres AS usuario_nombres, u.apellidos AS usuario_apellidos, u.email AS usuario_email, '
                . 'm.codigo AS moneda, m.simbolo AS moneda_simbolo, m.decimales AS moneda_decimales, '
                . 'e.codigo AS estado_codigo, e.nombre AS estado_nombre, e.color AS estado_color, '
                . 'sdp.numero AS sdp_numero')
            ->from($this->table . ' r')
            ->join('gmc_proyectos p', 'p.id = r.proyecto_id', 'left')
            ->join('gmc_centros_costo cc', 'cc.id = r.centro_costo_id', 'left')
            ->join('gmc_usuarios u', 'u.id = r.usuario_id', 'left')
            ->join('gmc_monedas m', 'm.id = r.moneda_id', 'left')
            ->join('gmc_estados e', 'e.id = r.estado_id', 'left')
            ->join('gmc_solicitudes_pago sdp', 'sdp.id = r.solicitud_pago_id', 'left')
            ->where('r.id', $id)
            ->where('r.deleted_at IS NULL', null, false)
            ->get()->row_array() ?: null;
    }
}
