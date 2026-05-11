<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'core/MY_Model.php';

class CierreObraRepo extends MY_Model
{
    protected $table = 'gmc_cierres_obra';
    protected $useSoftDelete = true;
    protected $useTimestamps = true;
    protected $useAudit = true;
    protected $fillable = [
        'proyecto_id','fecha_termino_real','resumen','conformidades','observaciones',
        'cerrada_por','cerrada_at','estado_id',
    ];

    public function findByProyecto(int $proyectoId): ?array
    {
        return $this->firstBy(['proyecto_id' => $proyectoId]);
    }

    public function findFullByProyecto(int $proyectoId): ?array
    {
        return $this->db
            ->select('c.*, p.codigo AS proyecto_codigo, p.nombre AS proyecto_nombre, '
                . 'cl.razon_social AS cliente, '
                . 'e.codigo AS estado_codigo, e.nombre AS estado_nombre, e.color AS estado_color, '
                . 'u.nombres AS cerrada_por_nombres, u.apellidos AS cerrada_por_apellidos')
            ->from($this->table . ' c')
            ->join('gmc_proyectos p', 'p.id = c.proyecto_id', 'left')
            ->join('gmc_clientes cl', 'cl.id = p.cliente_id', 'left')
            ->join('gmc_estados e', 'e.id = c.estado_id', 'left')
            ->join('gmc_usuarios u', 'u.id = c.cerrada_por', 'left')
            ->where('c.proyecto_id', $proyectoId)
            ->where('c.deleted_at IS NULL', null, false)
            ->get()->row_array() ?: null;
    }

    public function listAll(array $filters = []): array
    {
        $q = $this->db
            ->select('c.*, p.codigo AS proyecto_codigo, p.nombre AS proyecto_nombre, '
                . 'e.nombre AS estado_nombre, e.color AS estado_color')
            ->from($this->table . ' c')
            ->join('gmc_proyectos p', 'p.id = c.proyecto_id', 'left')
            ->join('gmc_estados e', 'e.id = c.estado_id', 'left')
            ->where('c.deleted_at IS NULL', null, false)
            ->order_by('c.id', 'DESC');
        if (!empty($filters['estado_id'])) $q->where('c.estado_id', (int)$filters['estado_id']);
        return $q->get()->result_array();
    }
}
