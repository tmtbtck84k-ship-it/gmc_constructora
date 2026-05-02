<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'core/MY_Model.php';

class UsuarioRepo extends MY_Model
{
    protected $table = 'gmc_usuarios';
    protected $useSoftDelete = true;
    protected $useTimestamps = true;
    protected $useAudit = true;
    protected $fillable = [
        'rut','nombres','apellidos','email','telefono',
        'password_hash','password_changed_at','force_password_change',
        'activo','ultimo_login_at','ultimo_login_ip',
    ];

    /**
     * Listado para grilla (con filtros y joins de roles concatenados).
     */
    public function listAll(array $filters = []): array
    {
        $q = $this->db->select('u.*, GROUP_CONCAT(r.nombre ORDER BY r.codigo SEPARATOR ", ") AS roles')
                      ->from('gmc_usuarios u')
                      ->join('gmc_usuarios_roles ur', 'ur.usuario_id = u.id', 'left')
                      ->join('gmc_roles r', 'r.id = ur.rol_id', 'left')
                      ->where('u.deleted_at IS NULL', null, false)
                      ->group_by('u.id')
                      ->order_by('u.id', 'DESC');

        if (!empty($filters['q'])) {
            $term = '%' . $filters['q'] . '%';
            $q->group_start()
              ->like('u.rut', $term)
              ->or_like('u.nombres', $term)
              ->or_like('u.apellidos', $term)
              ->or_like('u.email', $term)
              ->group_end();
        }
        if (isset($filters['activo']) && $filters['activo'] !== '') {
            $q->where('u.activo', (int)$filters['activo']);
        }

        return $q->get()->result_array();
    }

    public function findByRut(string $rut): ?array
    {
        return $this->firstBy(['rut' => $rut]);
    }

    public function findByEmail(string $email): ?array
    {
        return $this->firstBy(['email' => $email]);
    }

    /**
     * Verifica unicidad de RUT y email considerando soft delete.
     *
     * @return string|null  null si OK, mensaje de error si falla
     */
    public function checkUnique(string $rut, string $email, ?int $exceptId = null): ?string
    {
        $this->db->where('rut', $rut)->where('deleted_at IS NULL', null, false);
        if ($exceptId) $this->db->where('id !=', $exceptId);
        if ($this->db->count_all_results($this->table) > 0) return 'El RUT ya está registrado.';

        $this->db->where('email', $email)->where('deleted_at IS NULL', null, false);
        if ($exceptId) $this->db->where('id !=', $exceptId);
        if ($this->db->count_all_results($this->table) > 0) return 'El email ya está registrado.';

        return null;
    }

    /**
     * Reemplaza los roles del usuario por la lista dada.
     */
    public function syncRoles(int $userId, array $rolIds): void
    {
        $this->db->trans_start();
        $this->db->where('usuario_id', $userId)->delete('gmc_usuarios_roles');
        foreach (array_unique($rolIds) as $rid) {
            if ((int)$rid <= 0) continue;
            $this->db->insert('gmc_usuarios_roles', [
                'usuario_id' => $userId,
                'rol_id'     => (int)$rid,
            ]);
        }
        $this->db->trans_complete();
    }

    public function getRoleIds(int $userId): array
    {
        return array_map(
            fn($r) => (int)$r['rol_id'],
            $this->db->select('rol_id')
                     ->where('usuario_id', $userId)
                     ->get('gmc_usuarios_roles')->result_array()
        );
    }

    public function setActivo(int $userId, bool $activo): void
    {
        $this->update($userId, ['activo' => $activo ? 1 : 0]);
    }

    public function setPassword(int $userId, string $hash, bool $forceChange = false): void
    {
        $this->update($userId, [
            'password_hash'         => $hash,
            'password_changed_at'   => $forceChange ? null : date('Y-m-d H:i:s'),
            'force_password_change' => $forceChange ? 1 : 0,
        ]);
    }
}
