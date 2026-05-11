<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'core/MY_Model.php';

class PresupuestoObraRepo extends MY_Model
{
    protected $table = 'gmc_presupuestos_obra';
    protected $useSoftDelete = true;
    protected $useTimestamps = true;
    protected $useAudit = true;
    protected $fillable = ['proyecto_id','version','moneda_id','monto_total','vigente'];

    public function vigentePorProyecto(int $proyectoId): ?array
    {
        return $this->firstBy(['proyecto_id' => $proyectoId, 'vigente' => 1]);
    }

    public function listByProyecto(int $proyectoId): array
    {
        return $this->db
            ->select('pr.*, m.codigo AS moneda_codigo, m.simbolo AS moneda_simbolo, m.decimales AS moneda_decimales')
            ->from($this->table . ' pr')
            ->join('gmc_monedas m', 'm.id = pr.moneda_id', 'left')
            ->where('pr.proyecto_id', $proyectoId)
            ->where('pr.deleted_at IS NULL', null, false)
            ->order_by('pr.version', 'DESC')
            ->get()->result_array();
    }

    public function findFull(int $id): ?array
    {
        return $this->db
            ->select('pr.*, p.codigo AS proyecto_codigo, p.nombre AS proyecto_nombre, '
                . 'm.codigo AS moneda_codigo, m.simbolo AS moneda_simbolo, m.decimales AS moneda_decimales')
            ->from($this->table . ' pr')
            ->join('gmc_proyectos p', 'p.id = pr.proyecto_id', 'left')
            ->join('gmc_monedas m', 'm.id = pr.moneda_id', 'left')
            ->where('pr.id', $id)
            ->where('pr.deleted_at IS NULL', null, false)
            ->get()->row_array() ?: null;
    }

    public function siguienteVersion(int $proyectoId): int
    {
        $row = $this->db->select_max('version')
                        ->where('proyecto_id', $proyectoId)
                        ->where('deleted_at IS NULL', null, false)
                        ->get($this->table)->row();
        return $row && $row->version ? (int)$row->version + 1 : 1;
    }

    public function marcarUnicaVigente(int $proyectoId, int $vigenteId): void
    {
        $this->db->where('proyecto_id', $proyectoId)
                 ->where('id !=', $vigenteId)
                 ->update($this->table, ['vigente' => 0]);
        $this->db->where('id', $vigenteId)->update($this->table, ['vigente' => 1]);
    }
}
