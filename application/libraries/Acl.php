<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Acl — Control de acceso por permisos.
 *
 * Carga 1 vez por sesión los permisos del usuario y los cachea en
 * sesión (session->set_userdata('acl_permisos', [...])).
 *
 * Cualquier cambio en gmc_usuarios_roles o gmc_roles_permisos debe llamar
 * a Acl::flush($userId) para invalidar el cache (eso lo hace el service
 * que modifica esas tablas).
 */
class Acl
{
    /** @var CI_Controller */
    protected $CI;
    /** Cache en memoria (request-scoped) */
    protected $cache = [];

    public function __construct()
    {
        $this->CI =& get_instance();
    }

    /**
     * @return string[]  códigos de permisos del usuario.
     */
    public function permissionsOf(int $userId): array
    {
        if (isset($this->cache[$userId])) return $this->cache[$userId];

        // Cache en sesión (usuario actual)
        if ($userId === (int) $this->CI->session->userdata('user_id')) {
            $cached = $this->CI->session->userdata('acl_permisos');
            if (is_array($cached)) {
                return $this->cache[$userId] = $cached;
            }
        }

        $sql = "SELECT DISTINCT p.codigo
                FROM gmc_usuarios_roles ur
                JOIN gmc_roles_permisos rp ON rp.rol_id = ur.rol_id
                JOIN gmc_permisos p ON p.id = rp.permiso_id
                JOIN gmc_roles r ON r.id = ur.rol_id AND r.activo = 1
                WHERE ur.usuario_id = ?";
        $rows = $this->CI->db->query($sql, [$userId])->result_array();
        $perms = array_map(fn($r) => $r['codigo'], $rows);

        if ($userId === (int) $this->CI->session->userdata('user_id')) {
            $this->CI->session->set_userdata('acl_permisos', $perms);
        }

        return $this->cache[$userId] = $perms;
    }

    public function rolesOf(int $userId): array
    {
        $sql = "SELECT r.codigo
                FROM gmc_usuarios_roles ur
                JOIN gmc_roles r ON r.id = ur.rol_id AND r.activo = 1
                WHERE ur.usuario_id = ?";
        return array_map(fn($r) => $r['codigo'],
            $this->CI->db->query($sql, [$userId])->result_array()
        );
    }

    public function can(string $codigo, int $userId): bool
    {
        $perms = $this->permissionsOf($userId);
        return in_array($codigo, $perms, true);
    }

    public function hasAnyOfModule(string $modulo, int $userId): bool
    {
        $perms = $this->permissionsOf($userId);
        $prefix = $modulo . '.';
        foreach ($perms as $p) {
            if (strpos($p, $prefix) === 0) return true;
        }
        return false;
    }

    public function isAdmin(int $userId): bool
    {
        return in_array('admin', $this->rolesOf($userId), true);
    }

    /**
     * Invalida el cache para un usuario.
     */
    public function flush(int $userId): void
    {
        unset($this->cache[$userId]);
        if ($userId === (int) $this->CI->session->userdata('user_id')) {
            $this->CI->session->unset_userdata('acl_permisos');
        }
    }
}
