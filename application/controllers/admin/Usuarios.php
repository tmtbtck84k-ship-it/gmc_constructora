<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Usuarios extends MY_AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model(['UsuarioRepo','RolRepo']);
        $this->load->library('Notifier');
    }

    // ---------------- LIST ----------------
    public function index()
    {
        $this->require_permission('admin.usuario.ver');

        $filters = [
            'q'      => trim((string) $this->input->get('q')),
            'activo' => $this->input->get('activo'),
        ];
        $rows = $this->UsuarioRepo->listAll($filters);

        $this->view('admin/usuarios/index', [
            'usuarios' => $rows,
            'filters'  => $filters,
        ]);
    }

    // ---------------- CREATE ----------------
    public function crear()
    {
        $this->require_permission('admin.usuario.crear');

        if ($this->input->method() !== 'post') {
            return $this->view('admin/usuarios/form', [
                'usuario'    => null,
                'roles'      => $this->RolRepo->activos(),
                'rolesUser'  => [],
                'is_edit'    => false,
            ]);
        }

        // Submit
        $rut       = normalizar_rut((string) $this->input->post('rut'));
        $nombres   = trim((string) $this->input->post('nombres'));
        $apellidos = trim((string) $this->input->post('apellidos'));
        $email     = strtolower(trim((string) $this->input->post('email')));
        $telefono  = trim((string) $this->input->post('telefono')) ?: null;
        $rolIds    = (array) $this->input->post('roles');
        $activo    = (int) ($this->input->post('activo') ?? 1);

        // Validaciones
        $errors = [];
        if (!validar_rut($rut))                     $errors[] = 'RUT inválido.';
        if ($nombres === '' || strlen($nombres) > 80)   $errors[] = 'Nombres inválido.';
        if ($apellidos === '' || strlen($apellidos) > 80) $errors[] = 'Apellidos inválido.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email inválido.';
        if (!$rolIds)                               $errors[] = 'Asigna al menos un rol.';
        if (!$errors) {
            if ($e = $this->UsuarioRepo->checkUnique($rut, $email)) $errors[] = $e;
        }
        if ($errors) {
            $this->flash('error', implode(' ', $errors));
            redirect(base_url('admin/usuarios/crear'));
        }

        // Generar clave temporal segura
        $tempPassword = $this->_generarClaveTemporal();
        $hash = password_hash($tempPassword, PASSWORD_BCRYPT, ['cost' => 12]);

        // Insertar
        $id = $this->UsuarioRepo->create([
            'rut'                   => $rut,
            'nombres'               => $nombres,
            'apellidos'             => $apellidos,
            'email'                 => $email,
            'telefono'              => $telefono,
            'password_hash'         => $hash,
            'force_password_change' => 1,
            'activo'                => $activo ? 1 : 0,
        ]);

        // Roles
        $this->UsuarioRepo->syncRoles($id, array_map('intval', $rolIds));

        // Auditoría
        $this->audit->log('admin.usuario.crear', 'gmc_usuarios', $id, null, [
            'rut' => $rut, 'email' => $email, 'roles' => $rolIds,
        ]);

        // Notificar al usuario nuevo con la clave temporal
        $this->notifier->encolar(
            'usuario.creado',
            $email,
            'Cuenta creada en ERP GMC',
            "Hola {$nombres},\n\n".
            "Se ha creado tu cuenta en el ERP GMC.\n\n".
            "RUT: {$rut}\nClave temporal: {$tempPassword}\n\n".
            "Al ingresar por primera vez deberás cambiar la clave.\n\n".
            "Acceso: " . base_url('login'),
            ['user_id' => $id]
        );

        $this->flash('success', "Usuario creado. Clave temporal enviada por correo. (Mostrada también: {$tempPassword})");
        redirect(base_url("admin/usuarios/{$id}"));
    }

    // ---------------- VIEW ----------------
    public function ver(int $id)
    {
        $this->require_permission('admin.usuario.ver');
        $u = $this->UsuarioRepo->find($id);
        if (!$u) show_404();

        $rolesUser = $this->UsuarioRepo->getRoleIds($id);
        $rolesAll  = $this->RolRepo->activos();
        $rolesNombres = array_map(
            fn($r) => $r['nombre'],
            array_filter($rolesAll, fn($r) => in_array((int)$r['id'], $rolesUser, true))
        );

        $this->view('admin/usuarios/ver', [
            'usuario'      => $u,
            'roles'        => $rolesNombres,
        ]);
    }

    // ---------------- EDIT ----------------
    public function editar(int $id)
    {
        $this->require_permission('admin.usuario.editar');
        $u = $this->UsuarioRepo->find($id);
        if (!$u) show_404();

        if ($this->input->method() !== 'post') {
            return $this->view('admin/usuarios/form', [
                'usuario'    => $u,
                'roles'      => $this->RolRepo->activos(),
                'rolesUser'  => $this->UsuarioRepo->getRoleIds($id),
                'is_edit'    => true,
            ]);
        }

        // Submit
        $rut       = normalizar_rut((string) $this->input->post('rut'));
        $nombres   = trim((string) $this->input->post('nombres'));
        $apellidos = trim((string) $this->input->post('apellidos'));
        $email     = strtolower(trim((string) $this->input->post('email')));
        $telefono  = trim((string) $this->input->post('telefono')) ?: null;
        $rolIds    = array_map('intval', (array) $this->input->post('roles'));
        $activo    = (int) ($this->input->post('activo') ?? 1);

        $errors = [];
        if (!validar_rut($rut)) $errors[] = 'RUT inválido.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email inválido.';
        if (!$rolIds) $errors[] = 'Asigna al menos un rol.';
        if (!$errors) {
            if ($e = $this->UsuarioRepo->checkUnique($rut, $email, $id)) $errors[] = $e;
        }
        // Regla: el último admin no puede quitarse a sí mismo el rol admin
        if (!$errors && (int)$u['id'] === $this->user_id()) {
            $adminRolId = $this->_getAdminRolId();
            if ($adminRolId && !in_array($adminRolId, $rolIds, true)) {
                $errors[] = 'No puedes quitarte tu propio rol Administrador.';
            }
        }
        if ($errors) {
            $this->flash('error', implode(' ', $errors));
            redirect(base_url("admin/usuarios/{$id}/editar"));
        }

        $before = $u;
        $this->UsuarioRepo->update($id, [
            'rut'       => $rut,
            'nombres'   => $nombres,
            'apellidos' => $apellidos,
            'email'     => $email,
            'telefono'  => $telefono,
            'activo'    => $activo ? 1 : 0,
        ]);
        $this->UsuarioRepo->syncRoles($id, $rolIds);
        $this->acl->flush($id); // invalida cache ACL del usuario afectado

        $this->audit->logChanges('admin.usuario.editar', 'gmc_usuarios', $id, $before, [
            'rut' => $rut, 'email' => $email, 'activo' => $activo, 'roles' => $rolIds,
        ]);

        $this->flash('success', 'Usuario actualizado.');
        redirect(base_url("admin/usuarios/{$id}"));
    }

    // ---------------- DELETE (soft) ----------------
    public function eliminar(int $id)
    {
        $this->require_permission('admin.usuario.eliminar');

        if ($id === $this->user_id()) {
            $this->flash('error', 'No puedes eliminar tu propio usuario.');
            redirect(base_url('admin/usuarios'));
        }

        $u = $this->UsuarioRepo->find($id);
        if (!$u) show_404();

        $this->UsuarioRepo->softDelete($id);
        $this->acl->flush($id);
        $this->audit->log('admin.usuario.eliminar', 'gmc_usuarios', $id, $u, null);

        $this->flash('success', 'Usuario eliminado.');
        redirect(base_url('admin/usuarios'));
    }

    // ---------------- RESET PASSWORD ----------------
    public function reset_password(int $id)
    {
        $this->require_permission('admin.usuario.editar');
        $u = $this->UsuarioRepo->find($id);
        if (!$u) show_404();

        $temp = $this->_generarClaveTemporal();
        $hash = password_hash($temp, PASSWORD_BCRYPT, ['cost' => 12]);
        $this->UsuarioRepo->setPassword($id, $hash, /*forceChange*/ true);

        $this->audit->log('admin.usuario.reset_password', 'gmc_usuarios', $id);
        $this->notifier->encolar(
            'usuario.password_reset',
            $u['email'],
            'Tu contraseña fue restablecida — ERP GMC',
            "Hola {$u['nombres']},\n\nTu contraseña fue restablecida por un administrador.\n".
            "Clave temporal: {$temp}\n\nDeberás cambiarla al ingresar.\n\n".
            "Acceso: " . base_url('login'),
            ['user_id' => $id]
        );

        $this->flash('success', "Clave restablecida. Nueva clave temporal: {$temp}");
        redirect(base_url("admin/usuarios/{$id}"));
    }

    // ---------------- helpers ----------------
    private function _generarClaveTemporal(): string
    {
        $sets = [
            'abcdefghjkmnpqrstuvwxyz',
            'ABCDEFGHJKLMNPQRSTUVWXYZ',
            '23456789',
            '!@#%&*+-=?',
        ];
        $pwd = '';
        foreach ($sets as $s) $pwd .= $s[random_int(0, strlen($s) - 1)];
        $all = implode('', $sets);
        for ($i = strlen($pwd); $i < 14; $i++) $pwd .= $all[random_int(0, strlen($all) - 1)];
        return str_shuffle($pwd);
    }

    private function _getAdminRolId(): ?int
    {
        $r = $this->db->where('codigo', 'admin')->get('gmc_roles')->row_array();
        return $r ? (int)$r['id'] : null;
    }
}
