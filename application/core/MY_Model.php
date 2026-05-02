<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * MY_Model — Modelo base con CRUD genérico.
 *
 * Subclases deben declarar:
 *   protected $table       = 'gmc_xxx';
 *   protected $primaryKey  = 'id';            // opcional, default 'id'
 *   protected $useSoftDelete = true;          // opcional
 *   protected $useTimestamps = true;          // opcional
 *   protected $useAudit      = true;          // opcional, agrega created_by/updated_by
 *   protected $fillable      = ['col1','col2',...];
 *
 * Métodos disponibles:
 *   find($id)
 *   findBy(array $where)
 *   firstBy(array $where)
 *   paginate(array $where = [], int $limit = 25, int $offset = 0, string $orderBy = 'id DESC')
 *   create(array $data)
 *   update($id, array $data)
 *   softDelete($id)
 *   restore($id)
 *   destroy($id)            ← borrado físico, usar con cuidado
 *   count(array $where = [])
 */
abstract class MY_Model extends CI_Model
{
    /** @var string */
    protected $table;
    /** @var string */
    protected $primaryKey = 'id';
    /** @var bool */
    protected $useSoftDelete = true;
    /** @var bool */
    protected $useTimestamps = true;
    /** @var bool */
    protected $useAudit = true;
    /** @var string[] columnas permitidas en mass-assignment */
    protected $fillable = [];

    public function __construct()
    {
        parent::__construct();
        if (!$this->table) {
            throw new RuntimeException(static::class . ' debe declarar $table.');
        }
    }

    // ----------------- READ -----------------

    public function find($id)
    {
        $q = $this->db->where("{$this->primaryKey}", $id);
        $this->_applyNotDeleted($q);
        return $this->db->get($this->table)->row_array() ?: null;
    }

    public function findBy(array $where, ?string $orderBy = null, ?int $limit = null, int $offset = 0): array
    {
        if ($where) $this->db->where($where);
        $this->_applyNotDeleted($this->db);
        if ($orderBy) $this->db->order_by($orderBy);
        if ($limit !== null) $this->db->limit($limit, $offset);
        return $this->db->get($this->table)->result_array();
    }

    public function firstBy(array $where): ?array
    {
        if ($where) $this->db->where($where);
        $this->_applyNotDeleted($this->db);
        $this->db->limit(1);
        return $this->db->get($this->table)->row_array() ?: null;
    }

    public function paginate(array $where = [], int $limit = 25, int $offset = 0, string $orderBy = ''): array
    {
        $orderBy = $orderBy ?: "{$this->primaryKey} DESC";
        $rows  = $this->findBy($where, $orderBy, $limit, $offset);
        $total = $this->count($where);
        return ['rows' => $rows, 'total' => $total, 'limit' => $limit, 'offset' => $offset];
    }

    public function count(array $where = []): int
    {
        if ($where) $this->db->where($where);
        $this->_applyNotDeleted($this->db);
        return (int) $this->db->count_all_results($this->table);
    }

    // ----------------- WRITE -----------------

    public function create(array $data): int
    {
        $data = $this->_filterFillable($data);
        if ($this->useTimestamps) {
            $data['created_at'] = $data['created_at'] ?? date('Y-m-d H:i:s');
            $data['updated_at'] = $data['updated_at'] ?? date('Y-m-d H:i:s');
        }
        if ($this->useAudit) {
            $uid = $this->_currentUserId();
            $data['created_by'] = $data['created_by'] ?? $uid;
            $data['updated_by'] = $data['updated_by'] ?? $uid;
        }
        $this->db->insert($this->table, $data);
        return (int) $this->db->insert_id();
    }

    public function update($id, array $data): bool
    {
        $data = $this->_filterFillable($data);
        if ($this->useTimestamps) {
            $data['updated_at'] = date('Y-m-d H:i:s');
        }
        if ($this->useAudit) {
            $data['updated_by'] = $this->_currentUserId();
        }
        $this->db->where($this->primaryKey, $id);
        return (bool) $this->db->update($this->table, $data);
    }

    public function softDelete($id): bool
    {
        if (!$this->useSoftDelete) {
            return $this->destroy($id);
        }
        $data = ['deleted_at' => date('Y-m-d H:i:s')];
        if ($this->useAudit) $data['updated_by'] = $this->_currentUserId();
        $this->db->where($this->primaryKey, $id);
        return (bool) $this->db->update($this->table, $data);
    }

    public function restore($id): bool
    {
        $data = ['deleted_at' => null];
        if ($this->useAudit) $data['updated_by'] = $this->_currentUserId();
        $this->db->where($this->primaryKey, $id);
        return (bool) $this->db->update($this->table, $data);
    }

    public function destroy($id): bool
    {
        $this->db->where($this->primaryKey, $id);
        return (bool) $this->db->delete($this->table);
    }

    // ----------------- Internos -----------------

    protected function _applyNotDeleted($q): void
    {
        if ($this->useSoftDelete) {
            $q->where("{$this->table}.deleted_at IS NULL", null, false);
        }
    }

    protected function _filterFillable(array $data): array
    {
        if (!$this->fillable) return $data;
        return array_intersect_key($data, array_flip(array_merge(
            $this->fillable,
            // siempre permitidos para framework:
            ['created_at','updated_at','deleted_at','created_by','updated_by']
        )));
    }

    protected function _currentUserId(): ?int
    {
        $CI =& get_instance();
        if (isset($CI->session) && $CI->session->userdata('user_id')) {
            return (int) $CI->session->userdata('user_id');
        }
        return null;
    }
}
