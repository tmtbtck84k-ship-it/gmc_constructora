<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'core/MY_Model.php';

class TipoGastoRepo extends MY_Model
{
    protected $table = 'gmc_tipos_gasto';
    protected $useSoftDelete = true;
    protected $useTimestamps = true;
    protected $useAudit = false;
    protected $fillable = ['codigo','nombre','activo'];

    public function activos(): array
    {
        return $this->findBy(['activo' => 1], 'codigo ASC');
    }

    public function checkCodigoUnique(string $codigo, ?int $exceptId = null): bool
    {
        $this->db->where('codigo', $codigo)->where('deleted_at IS NULL', null, false);
        if ($exceptId) $this->db->where('id !=', $exceptId);
        return $this->db->count_all_results($this->table) === 0;
    }
}
