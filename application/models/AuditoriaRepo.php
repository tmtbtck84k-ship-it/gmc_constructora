<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'core/MY_Model.php';

class AuditoriaRepo extends MY_Model
{
    protected $table = 'gmc_auditoria_logs';
    protected $useSoftDelete = false;
    protected $useTimestamps = false;
    protected $useAudit = false;

    /**
     * Búsqueda paginada con filtros.
     */
    public function search(array $filters, int $limit = 50, int $offset = 0): array
    {
        $q = $this->db
            ->select('al.*, u.nombres, u.apellidos, u.email AS usuario_email')
            ->from($this->table . ' al')
            ->join('gmc_usuarios u', 'u.id = al.usuario_id', 'left')
            ->order_by('al.id', 'DESC');

        if (!empty($filters['usuario_id'])) $q->where('al.usuario_id', (int)$filters['usuario_id']);
        if (!empty($filters['accion']))     $q->like('al.accion', $filters['accion']);
        if (!empty($filters['entidad']))    $q->where('al.entidad', $filters['entidad']);
        if (!empty($filters['desde']))      $q->where('al.created_at >=', $filters['desde'] . ' 00:00:00');
        if (!empty($filters['hasta']))      $q->where('al.created_at <=', $filters['hasta'] . ' 23:59:59');

        $count = clone $q;
        $total = (int) $count->count_all_results('', false);

        $q->limit($limit, $offset);
        $rows = $q->get()->result_array();

        return ['rows' => $rows, 'total' => $total, 'limit' => $limit, 'offset' => $offset];
    }
}
