<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Roles extends MY_AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model(['RolRepo','PermisoRepo']);
    }

    public function index()
    {
        $this->require_permission('admin.rol.ver');

        $roles    = $this->RolRepo->findBy([], 'codigo ASC');
        $permisos = $this->PermisoRepo->porModulo();

        // Construir mapa rol_id => set(permiso_id) para pintar checkboxes
        $matriz = [];
        foreach ($roles as $r) {
            $matriz[(int)$r['id']] = array_flip($this->RolRepo->getPermisoIds((int)$r['id']));
        }

        // Conteo de permisos por rol
        $counts = [];
        foreach ($matriz as $rid => $set) $counts[$rid] = count($set);

        $this->view('admin/roles/index', [
            'roles'    => $roles,
            'permisos' => $permisos,
            'matriz'   => $matriz,
            'counts'   => $counts,
        ]);
    }

    /**
     * Pantalla de edición del set de permisos de UN rol (matriz por módulo).
     */
    public function permisos(int $rolId)
    {
        $this->require_permission('admin.rol.editar');

        $rol = $this->RolRepo->find($rolId);
        if (!$rol) show_404();

        if ($this->input->method() !== 'post') {
            return $this->view('admin/roles/permisos', [
                'rol'           => $rol,
                'permisos'      => $this->PermisoRepo->porModulo(),
                'permisosRol'   => array_flip($this->RolRepo->getPermisoIds($rolId)),
            ]);
        }

        // Submit: lista de permiso_id en checkboxes
        $ids = array_map('intval', (array) $this->input->post('permisos'));

        // Bloqueo: el rol "admin" siempre conserva todos los permisos
        if ($rol['codigo'] === 'admin') {
            $todos = $this->db->select('id')->get('gmc_permisos')->result_array();
            $ids = array_map(fn($r) => (int)$r['id'], $todos);
        }

        $before = $this->RolRepo->getPermisoIds($rolId);
        $this->RolRepo->syncPermisos($rolId, $ids);

        // Invalidar cache ACL para todos los usuarios con este rol
        foreach ($this->RolRepo->getUserIds($rolId) as $uid) {
            $this->acl->flush($uid);
        }

        $this->audit->log('admin.rol.permisos', 'gmc_roles', $rolId,
            ['permisos' => $before],
            ['permisos' => $ids]
        );

        $this->flash('success', 'Permisos del rol actualizados.');
        redirect(base_url('admin/roles'));
    }
}
