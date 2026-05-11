<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'core/MY_Model.php';

class ActividadRepo extends MY_Model
{
    protected $table = 'gmc_actividades';
    protected $useSoftDelete = true;
    protected $useTimestamps = true;
    protected $useAudit = true;
    protected $fillable = [
        'proyecto_id','hito_id','codigo','nombre','descripcion',
        'fecha_inicio_planificada','fecha_termino_planificada',
        'fecha_inicio_real','fecha_termino_real',
        'duracion_dias','porcentaje_avance','responsable_id','colaboradores_libres',
        'es_critica','holgura_dias','orden',
    ];

    public function listByProyecto(int $proyectoId, array $filters = []): array
    {
        $q = $this->db
            ->select("a.*, h.nombre AS hito_nombre, h.codigo AS hito_codigo, h.orden AS hito_orden, "
                . "u.nombres AS resp_nombres, u.apellidos AS resp_apellidos, "
                . "TRIM(CONCAT(COALESCE(u.nombres,''),' ',COALESCE(u.apellidos,''))) AS responsable_nombre", false)
            ->from($this->table . ' a')
            ->join('gmc_hitos h', 'h.id = a.hito_id', 'left')
            ->join('gmc_usuarios u', 'u.id = a.responsable_id', 'left')
            ->where('a.proyecto_id', $proyectoId)
            ->where('a.deleted_at IS NULL', null, false)
            ->order_by('h.orden ASC, a.orden ASC, a.id ASC');

        if (!empty($filters['hito_id']))       $q->where('a.hito_id', (int)$filters['hito_id']);
        if (!empty($filters['responsable_id'])) $q->where('a.responsable_id', (int)$filters['responsable_id']);
        if (!empty($filters['desde']))          $q->where('a.fecha_inicio_planificada >=', $filters['desde']);
        if (!empty($filters['hasta']))          $q->where('a.fecha_termino_planificada <=', $filters['hasta']);
        if (isset($filters['critica']) && $filters['critica'] !== '') {
            $q->where('a.es_critica', (int)$filters['critica']);
        }
        if (!empty($filters['q'])) {
            $term = '%' . $filters['q'] . '%';
            $q->group_start()->like('a.nombre', $term)->or_like('a.codigo', $term)->group_end();
        }
        return $q->get()->result_array();
    }

    public function listByHito(int $hitoId): array
    {
        return $this->findBy(['hito_id' => $hitoId], 'orden ASC, id ASC');
    }

    public function findFull(int $id): ?array
    {
        return $this->db
            ->select('a.*, p.codigo AS proyecto_codigo, p.nombre AS proyecto_nombre, '
                . 'h.codigo AS hito_codigo, h.nombre AS hito_nombre, '
                . 'u.nombres AS resp_nombres, u.apellidos AS resp_apellidos, u.email AS resp_email')
            ->from($this->table . ' a')
            ->join('gmc_proyectos p', 'p.id = a.proyecto_id', 'left')
            ->join('gmc_hitos h', 'h.id = a.hito_id', 'left')
            ->join('gmc_usuarios u', 'u.id = a.responsable_id', 'left')
            ->where('a.id', $id)
            ->where('a.deleted_at IS NULL', null, false)
            ->get()->row_array() ?: null;
    }

    public function siguienteOrden(int $hitoId): int
    {
        $r = $this->db->select_max('orden')
                      ->where('hito_id', $hitoId)
                      ->where('deleted_at IS NULL', null, false)
                      ->get($this->table)->row();
        return $r && $r->orden !== null ? (int)$r->orden + 10 : 10;
    }

    /** Para Sprint B (Gantt visual): devuelve estructura plana lista para Frappe Gantt. */
    public function paraGantt(int $proyectoId): array
    {
        return $this->db
            ->select('id, hito_id, codigo, nombre, '
                . 'fecha_inicio_planificada, fecha_termino_planificada, '
                . 'porcentaje_avance, es_critica')
            ->where('proyecto_id', $proyectoId)
            ->where('deleted_at IS NULL', null, false)
            ->order_by('orden', 'ASC')
            ->get($this->table)->result_array();
    }
}
