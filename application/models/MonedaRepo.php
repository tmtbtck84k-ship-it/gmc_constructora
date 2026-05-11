<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'core/MY_Model.php';

class MonedaRepo extends MY_Model
{
    protected $table = 'gmc_monedas';
    protected $useSoftDelete = false;
    protected $useTimestamps = false;
    protected $useAudit = false;

    public function activas(): array
    {
        return $this->findBy(['activa' => 1], 'codigo ASC');
    }

    public function findByCodigo(string $codigo): ?array
    {
        return $this->firstBy(['codigo' => $codigo]);
    }

    /** Devuelve la moneda funcional CLP. */
    public function clp(): array
    {
        $clp = $this->findByCodigo('CLP');
        if (!$clp) throw new RuntimeException('Moneda CLP no existe en gmc_monedas.');
        return $clp;
    }
}
