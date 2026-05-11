<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'core/MY_Model.php';

class ComunaRepo extends MY_Model
{
    protected $table = 'gmc_comunas';
    protected $useSoftDelete = false;
    protected $useTimestamps = false;
    protected $useAudit = false;

    public function byRegion(int $regionId): array
    {
        return $this->db
            ->select('id, nombre')
            ->where('region_id', $regionId)
            ->order_by('nombre', 'ASC')
            ->get($this->table)->result_array();
    }

    /**
     * Devuelve regiones agrupando comunas (para dropdown jerárquico).
     */
    public function todasConRegion(): array
    {
        return $this->db
            ->select('c.id, c.nombre AS comuna, r.id AS region_id, r.nombre AS region')
            ->from('gmc_comunas c')
            ->join('gmc_regiones r', 'r.id = c.region_id')
            ->order_by('r.id, c.nombre')
            ->get()->result_array();
    }
}
