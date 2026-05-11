<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'core/MY_Model.php';

class DependenciaRepo extends MY_Model
{
    protected $table = 'gmc_actividad_dependencias';
    protected $useSoftDelete = false;
    protected $useTimestamps = false;
    protected $useAudit = false;
    protected $fillable = ['actividad_id','predecesor_id','tipo','lag_dias'];

    /** Predecesoras directas de una actividad. */
    public function predecesoras(int $actividadId): array
    {
        return $this->db
            ->select('d.*, a.codigo AS pred_codigo, a.nombre AS pred_nombre')
            ->from($this->table . ' d')
            ->join('gmc_actividades a', 'a.id = d.predecesor_id', 'left')
            ->where('d.actividad_id', $actividadId)
            ->get()->result_array();
    }

    /** Sucesoras directas. */
    public function sucesoras(int $predecesorId): array
    {
        return $this->db
            ->select('d.*, a.codigo AS suc_codigo, a.nombre AS suc_nombre')
            ->from($this->table . ' d')
            ->join('gmc_actividades a', 'a.id = d.actividad_id', 'left')
            ->where('d.predecesor_id', $predecesorId)
            ->get()->result_array();
    }

    /** Todas las dependencias de un proyecto (para el Gantt). */
    public function listByProyecto(int $proyectoId): array
    {
        return $this->db
            ->select('d.*')
            ->from($this->table . ' d')
            ->join('gmc_actividades a', 'a.id = d.actividad_id')
            ->where('a.proyecto_id', $proyectoId)
            ->where('a.deleted_at IS NULL', null, false)
            ->get()->result_array();
    }

    /**
     * Detecta ciclo: ¿agregar dep "actividadId depende de predecesorId" generaría
     * un ciclo? Hace DFS desde predecesorId siguiendo predecesoras y verifica
     * si llega a actividadId.
     */
    public function generariaCiclo(int $actividadId, int $predecesorId): bool
    {
        if ($actividadId === $predecesorId) return true;
        $visitados = [];
        $stack = [$predecesorId];
        while ($stack) {
            $current = array_pop($stack);
            if (isset($visitados[$current])) continue;
            $visitados[$current] = true;

            $rows = $this->db->select('predecesor_id')
                             ->where('actividad_id', $current)
                             ->get($this->table)->result_array();
            foreach ($rows as $r) {
                $p = (int)$r['predecesor_id'];
                if ($p === $actividadId) return true;
                if (!isset($visitados[$p])) $stack[] = $p;
            }
        }
        return false;
    }

    /**
     * Elimina TODAS las dependencias en las que participe la actividad
     * (sea como sucesora o como predecesora). Hard delete.
     */
    public function eliminarPorActividad(int $actividadId): int
    {
        $this->db->where('actividad_id', $actividadId)
                 ->or_where('predecesor_id', $actividadId)
                 ->delete($this->table);
        return $this->db->affected_rows();
    }
}
