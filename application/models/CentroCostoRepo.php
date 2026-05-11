<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'core/MY_Model.php';

class CentroCostoRepo extends MY_Model
{
    protected $table = 'gmc_centros_costo';
    protected $useSoftDelete = true;
    protected $useTimestamps = true;
    protected $useAudit = true;
    protected $fillable = ['proyecto_id','codigo','nombre','es_administracion','activo'];

    public function listByProyecto(?int $proyectoId): array
    {
        $q = $this->db->from($this->table)
                      ->where('deleted_at IS NULL', null, false)
                      ->order_by('es_administracion DESC, codigo ASC');
        if ($proyectoId === null) {
            $q->where('proyecto_id IS NULL', null, false);
        } else {
            $q->where('proyecto_id', $proyectoId);
        }
        return $q->get()->result_array();
    }

    public function adminGeneral(): ?array
    {
        return $this->firstBy([
            'proyecto_id IS NULL' => null,
            'es_administracion' => 1,
        ]);
    }

    public function checkCodigoUnique(?int $proyectoId, string $codigo, ?int $exceptId = null): bool
    {
        $this->db->where('codigo', $codigo)->where('deleted_at IS NULL', null, false);
        if ($proyectoId === null) $this->db->where('proyecto_id IS NULL', null, false);
        else                      $this->db->where('proyecto_id', $proyectoId);
        if ($exceptId) $this->db->where('id !=', $exceptId);
        return $this->db->count_all_results($this->table) === 0;
    }

    /** Crea el CC ADM-OBR automáticamente al crear un proyecto. */
    public function crearAdmObra(int $proyectoId, ?int $userId): int
    {
        return $this->create([
            'proyecto_id'       => $proyectoId,
            'codigo'            => 'ADM-OBR',
            'nombre'            => 'Administración de Obra',
            'es_administracion' => 1,
            'activo'            => 1,
            'created_by'        => $userId,
            'updated_by'        => $userId,
        ]);
    }
}
