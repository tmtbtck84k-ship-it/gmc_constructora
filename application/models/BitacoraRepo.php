<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'core/MY_Model.php';

class BitacoraRepo extends MY_Model
{
    protected $table = 'gmc_bitacoras';
    protected $useSoftDelete = true;
    protected $useTimestamps = true;
    protected $useAudit = false;
    protected $fillable = ['numero','proyecto_id','fecha_evento','tipo_evento','titulo','detalle','autor_id'];

    public function listByProyecto(int $proyectoId, array $filters = []): array
    {
        $q = $this->db
            ->select('b.*, u.nombres AS autor_nombres, u.apellidos AS autor_apellidos')
            ->from($this->table . ' b')
            ->join('gmc_usuarios u', 'u.id = b.autor_id', 'left')
            ->where('b.proyecto_id', $proyectoId)
            ->where('b.deleted_at IS NULL', null, false)
            ->order_by('b.fecha_evento', 'DESC')
            ->order_by('b.id', 'DESC');

        if (!empty($filters['tipo']))   $q->where('b.tipo_evento', $filters['tipo']);
        if (!empty($filters['autor']))  $q->where('b.autor_id', (int)$filters['autor']);
        if (!empty($filters['desde']))  $q->where('b.fecha_evento >=', $filters['desde']);
        if (!empty($filters['hasta']))  $q->where('b.fecha_evento <=', $filters['hasta']);
        if (!empty($filters['q'])) {
            $term = '%' . $filters['q'] . '%';
            $q->group_start()->like('b.titulo', $term)->or_like('b.detalle', $term)->group_end();
        }
        return $q->get()->result_array();
    }

    public function findFull(int $id): ?array
    {
        return $this->db
            ->select('b.*, '
                . 'p.codigo AS proyecto_codigo, p.nombre AS proyecto_nombre, '
                . 'u.nombres AS autor_nombres, u.apellidos AS autor_apellidos')
            ->from($this->table . ' b')
            ->join('gmc_proyectos p', 'p.id = b.proyecto_id', 'left')
            ->join('gmc_usuarios u', 'u.id = b.autor_id', 'left')
            ->where('b.id', $id)
            ->where('b.deleted_at IS NULL', null, false)
            ->get()->row_array() ?: null;
    }
}
