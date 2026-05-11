<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'core/MY_Model.php';

class HitoRepo extends MY_Model
{
    protected $table = 'gmc_hitos';
    protected $useSoftDelete = true;
    protected $useTimestamps = true;
    protected $useAudit = true;
    protected $fillable = [
        'proyecto_id','codigo','nombre','descripcion',
        'fecha_objetivo','fecha_real','completado','porcentaje_avance','orden',
    ];

    public function listByProyecto(int $proyectoId): array
    {
        return $this->findBy(['proyecto_id' => $proyectoId], 'orden ASC, id ASC');
    }

    public function findFull(int $id): ?array
    {
        return $this->db
            ->select('h.*, p.codigo AS proyecto_codigo, p.nombre AS proyecto_nombre')
            ->from($this->table . ' h')
            ->join('gmc_proyectos p', 'p.id = h.proyecto_id', 'left')
            ->where('h.id', $id)
            ->where('h.deleted_at IS NULL', null, false)
            ->get()->row_array() ?: null;
    }

    /** Recalcula % avance del hito como promedio ponderado por duración de sus actividades. */
    public function recalcularAvance(int $hitoId): void
    {
        $row = $this->db->query("
            SELECT
                COALESCE(SUM(porcentaje_avance * duracion_dias) / NULLIF(SUM(duracion_dias),0), 0) AS avance,
                COUNT(*) AS total,
                SUM(CASE WHEN porcentaje_avance >= 100 THEN 1 ELSE 0 END) AS completas
            FROM gmc_actividades
            WHERE hito_id = ? AND deleted_at IS NULL
        ", [$hitoId])->row();

        $avance = round((float)$row->avance, 2);
        $completado = ($row->total > 0 && (int)$row->total === (int)$row->completas) ? 1 : 0;
        $this->update($hitoId, ['porcentaje_avance' => $avance, 'completado' => $completado]);
    }

    public function siguienteOrden(int $proyectoId): int
    {
        $r = $this->db->select_max('orden')
                      ->where('proyecto_id', $proyectoId)
                      ->where('deleted_at IS NULL', null, false)
                      ->get($this->table)->row();
        return $r && $r->orden !== null ? (int)$r->orden + 10 : 10;
    }
}
