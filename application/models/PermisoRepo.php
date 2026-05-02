<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'core/MY_Model.php';

class PermisoRepo extends MY_Model
{
    protected $table = 'gmc_permisos';
    protected $useSoftDelete = false;
    protected $useTimestamps = false;
    protected $useAudit = false;
    protected $fillable = ['codigo','descripcion','modulo'];

    /**
     * Devuelve los permisos agrupados por módulo:
     *   ['admin' => [ ['id'=>..,'codigo'=>..,'descripcion'=>..], ... ], 'maestros' => [...] ]
     */
    public function porModulo(): array
    {
        $rows = $this->db->order_by('modulo, codigo', 'ASC')->get($this->table)->result_array();
        $out = [];
        foreach ($rows as $r) {
            $out[$r['modulo']][] = $r;
        }
        return $out;
    }

    public function findByCodigo(string $codigo): ?array
    {
        return $this->firstBy(['codigo' => $codigo]);
    }
}
