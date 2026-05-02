<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * MY_Controller — Controlador base GMC ERP.
 *
 * Tres variantes:
 *   - MY_Controller         → controlador público (sin sesión exigida).
 *   - MY_AuthController     → exige sesión válida y redirige a /login si no hay.
 *   - MY_AdminController    → AuthController + verifica que el usuario tenga rol admin.
 *
 * En todos los casos expone helpers consistentes:
 *   - $this->user()                 array con datos del usuario o null
 *   - $this->user_id()              int|null
 *   - $this->require_permission(c)  fail con 403 si no lo tiene
 *   - $this->validate(rules)        $this->form_validation con redirect en error
 *   - $this->flash(type, msg)       mensaje flash para próximo request
 *   - $this->json($data, $code=200) salida JSON estandarizada
 *   - $this->view($view, $data, $layout='layout/master')
 */
class MY_Controller extends CI_Controller
{
    /** @var array|null caché del row de gmc_usuarios para esta request */
    protected $current_user = null;

    public function __construct()
    {
        parent::__construct();

        // Carga obligatoria
        $this->load->library(['Auth', 'Acl', 'Audit']);

        // Si force_password_change y no estamos en /password/change ni en logout, redirigir
        $this->_check_forced_password_change();
    }

    // ----------------- Identidad -----------------

    /**
     * Devuelve el usuario actual completo (consultando DB la primera vez).
     */
    public function user(): ?array
    {
        if ($this->current_user !== null) return $this->current_user;
        $id = $this->session->userdata('user_id');
        if (!$id) return null;
        $row = $this->db->where('id', (int)$id)
                        ->where('deleted_at IS NULL', null, false)
                        ->where('activo', 1)
                        ->get('gmc_usuarios')->row_array();
        return $this->current_user = $row ?: null;
    }

    public function user_id(): ?int
    {
        $u = $this->user();
        return $u ? (int)$u['id'] : null;
    }

    /**
     * Requiere autenticación. Si no hay sesión, redirige a /login.
     */
    protected function require_login(): void
    {
        if (!$this->user_id()) {
            redirect(base_url('login'));
        }
    }

    /**
     * Requiere un permiso específico. 403 si no lo tiene.
     */
    public function require_permission(string $codigo): void
    {
        $this->require_login();
        if (!$this->acl->can($codigo, $this->user_id())) {
            log_message('error', "Acceso denegado: usuario {$this->user_id()} intentó acceder a permiso {$codigo}");
            show_error('No tienes permiso para acceder a este recurso.', 403, 'Acceso denegado');
        }
    }

    // ----------------- Helpers de request -----------------

    /**
     * Aplica reglas de validación al form_validation.
     * En caso de fallo, lanza redirect con flash de errores y termina.
     */
    public function validate(array $rules, ?string $redirectOnFail = null): void
    {
        $this->load->library('form_validation');
        foreach ($rules as $field => $rule) {
            $this->form_validation->set_rules($field, $field, $rule);
        }
        if (!$this->form_validation->run()) {
            $errors = $this->form_validation->error_array();
            $this->flash('error', 'Hay errores en el formulario: ' . implode(' / ', array_values($errors)));
            $back = $redirectOnFail ?: ($this->input->server('HTTP_REFERER') ?: base_url());
            redirect($back);
        }
    }

    public function flash(string $type, string $msg): void
    {
        $this->session->set_flashdata("flash_{$type}", $msg);
    }

    public function json($data, int $code = 200): void
    {
        $this->output
            ->set_status_header($code)
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    /**
     * Render con layout maestro.
     */
    public function view(string $viewPath, array $data = [], string $layout = 'layout/master'): void
    {
        $data['_user']        = $this->user();
        $data['_acl']         = $this->acl;
        $data['_flash']       = [
            'success' => $this->session->flashdata('flash_success'),
            'error'   => $this->session->flashdata('flash_error'),
            'info'    => $this->session->flashdata('flash_info'),
            'warning' => $this->session->flashdata('flash_warning'),
        ];
        $data['_main_view']   = $viewPath;
        $data['_company']     = $this->config->item('app_company_name');
        $data['_env']         = $this->config->item('app_env');

        $this->load->view($layout, $data);
    }

    // ----------------- Internos -----------------

    private function _check_forced_password_change(): void
    {
        $u = $this->user();
        if (!$u) return;
        if (!(int)$u['force_password_change']) return;

        $allowed = ['auth/password/change', 'auth/password/change_submit', 'auth/logout'];
        $current = $this->router->fetch_directory() . $this->router->fetch_class() . '/' . $this->router->fetch_method();
        $current = trim($current, '/');

        $alt = $this->router->fetch_class() . '/' . $this->router->fetch_method();
        if (!in_array($current, $allowed, true) && !in_array($alt, $allowed, true)) {
            redirect(base_url('password/change'));
        }
    }
}


/**
 * Controlador autenticado.
 */
class MY_AuthController extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->require_login();
    }
}


/**
 * Controlador para sección admin.
 */
class MY_AdminController extends MY_AuthController
{
    public function __construct()
    {
        parent::__construct();
        // Bloquea si no tiene ningún permiso del módulo admin
        if (!$this->acl->hasAnyOfModule('admin', $this->user_id())) {
            show_error('Esta sección está reservada para administradores.', 403, 'Acceso denegado');
        }
    }
}
