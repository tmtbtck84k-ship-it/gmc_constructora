<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'core/MY_Model.php';

class ProveedorRepo extends MY_Model
{
    protected $table = 'gmc_proveedores';
    protected $useSoftDelete = true;
    protected $useTimestamps = true;
    protected $useAudit = true;
    protected $fillable = [
        'rut','razon_social','nombre_fantasia','giro','direccion','comuna_id',
        'email','telefono','contacto_nombre','contacto_email','contacto_telefono',
        'es_subcontratista','categoria','activo',
    ];

    public function listAll(array $filters = []): array
    {
        $q = $this->db
            ->select('p.*, com.nombre AS comuna_nombre')
            ->from($this->table . ' p')
            ->join('gmc_comunas com', 'com.id = p.comuna_id', 'left')
            ->where('p.deleted_at IS NULL', null, false)
            ->order_by('p.razon_social', 'ASC');

        if (!empty($filters['q'])) {
            $term = '%' . $filters['q'] . '%';
            $q->group_start()
              ->like('p.rut', $term)
              ->or_like('p.razon_social', $term)
              ->or_like('p.email', $term)
              ->group_end();
        }
        if (isset($filters['activo']) && $filters['activo'] !== '') {
            $q->where('p.activo', (int)$filters['activo']);
        }
        if (isset($filters['subc']) && $filters['subc'] !== '') {
            $q->where('p.es_subcontratista', (int)$filters['subc']);
        }
        return $q->get()->result_array();
    }

    public function checkUnique(string $rut, ?int $exceptId = null): ?string
    {
        $this->db->where('rut', $rut)->where('deleted_at IS NULL', null, false);
        if ($exceptId) $this->db->where('id !=', $exceptId);
        if ($this->db->count_all_results($this->table) > 0) return 'El RUT ya está registrado.';
        return null;
    }

    /** Para selects en SDP/Compra/Rinde */
    public function activos(): array
    {
        return $this->findBy(['activo' => 1], 'razon_social ASC');
    }
}
