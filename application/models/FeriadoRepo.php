<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'core/MY_Model.php';

class FeriadoRepo extends MY_Model
{
    protected $table = 'gmc_feriados';
    protected $useSoftDelete = false;
    protected $useTimestamps = false;
    protected $useAudit = false;
    protected $fillable = ['fecha','nombre','irrenunciable','tipo'];

    public function listAll(?int $anio = null): array
    {
        $q = $this->db->order_by('fecha', 'ASC');
        if ($anio) {
            $q->where('YEAR(fecha)', $anio);
        }
        return $q->get($this->table)->result_array();
    }

    public function isFeriado(string $fecha): bool
    {
        return $this->db->where('fecha', $fecha)->count_all_results($this->table) > 0;
    }

    /** Devuelve set de fechas feriadas dentro de un rango (para CalendarioService). */
    public function rangoSet(string $desde, string $hasta): array
    {
        $rows = $this->db->select('fecha')
                         ->where('fecha >=', $desde)
                         ->where('fecha <=', $hasta)
                         ->get($this->table)->result_array();
        $set = [];
        foreach ($rows as $r) $set[$r['fecha']] = true;
        return $set;
    }

    public function checkFechaUnique(string $fecha, ?int $exceptId = null): bool
    {
        $this->db->where('fecha', $fecha);
        if ($exceptId) $this->db->where('id !=', $exceptId);
        return $this->db->count_all_results($this->table) === 0;
    }
}
