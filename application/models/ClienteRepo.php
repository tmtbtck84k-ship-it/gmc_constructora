<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'core/MY_Model.php';

class ClienteRepo extends MY_Model
{
    protected $table = 'gmc_clientes';
    protected $useSoftDelete = true;
    protected $useTimestamps = true;
    protected $useAudit = true;
    protected $fillable = [
        'rut','razon_social','nombre_fantasia','giro','direccion','comuna_id',
        'email','telefono','contacto_nombre','contacto_email','contacto_telefono',
        'activo',
    ];

    public function listAll(array $filters = []): array
    {
        $q = $this->db
            ->select('c.*, com.nombre AS comuna_nombre, reg.nombre AS region_nombre')
            ->from($this->table . ' c')
            ->join('gmc_comunas com', 'com.id = c.comuna_id', 'left')
            ->join('gmc_regiones reg', 'reg.id = com.region_id', 'left')
            ->where('c.deleted_at IS NULL', null, false)
            ->order_by('c.razon_social', 'ASC');

        if (!empty($filters['q'])) {
            $term = '%' . $filters['q'] . '%';
            $q->group_start()
              ->like('c.rut', $term)
              ->or_like('c.razon_social', $term)
              ->or_like('c.email', $term)
              ->group_end();
        }
        if (isset($filters['activo']) && $filters['activo'] !== '') {
            $q->where('c.activo', (int)$filters['activo']);
        }
        return $q->get()->result_array();
    }

    public function checkUnique(string $rut, ?string $email, ?int $exceptId = null): ?string
    {
        $this->db->where('rut', $rut)->where('deleted_at IS NULL', null, false);
        if ($exceptId) $this->db->where('id !=', $exceptId);
        if ($this->db->count_all_results($this->table) > 0) return 'El RUT ya está registrado.';

        if ($email) {
            $this->db->where('email', $email)->where('deleted_at IS NULL', null, false);
            if ($exceptId) $this->db->where('id !=', $exceptId);
            if ($this->db->count_all_results($this->table) > 0) return 'El email ya está registrado.';
        }
        return null;
    }
}
