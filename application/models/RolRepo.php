<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'core/MY_Model.php';

class RolRepo extends MY_Model
{
    protected $table = 'gmc_roles';
    protected $useSoftDelete = false;
    protected $useTimestamps = true;
    protected $useAudit = false;
    protected $fillable = ['codigo','nombre','descripcion','activo'];

    public function activos(): array
    {
        return $this->findBy(['activo' => 1], 'nombre ASC');
    }

    public function getPermisoIds(int $rolId): array
    {
        return array_map(
            fn($r) => (int)$r['permiso_id'],
            $this->db->select('permiso_id')
                     ->where('rol_id', $rolId)
                     ->get('gmc_roles_permisos')->result_array()
        );
    }

    /**
     * Reemplaza los permisos asignados al rol.
     */
    public function syncPermisos(int $rolId, array $permisoIds): void
    {
        $this->db->trans_start();
        $this->db->where('rol_id', $rolId)->delete('gmc_roles_permisos');
        foreach (array_unique($permisoIds) as $pid) {
            if ((int)$pid <= 0) continue;
            $this->db->insert('gmc_roles_permisos', [
                'rol_id'     => $rolId,
                'permiso_id' => (int)$pid,
            ]);
        }
        $this->db->trans_complete();
    }

    /**
     * Devuelve los IDs de usuarios con un rol dado (para invalidar cache ACL).
     */
    public function getUserIds(int $rolId): array
    {
        return array_map(
            fn($r) => (int)$r['usuario_id'],
            $this->db->select('usuario_id')
                     ->where('rol_id', $rolId)
                     ->get('gmc_usuarios_roles')->result_array()
        );
    }
}
