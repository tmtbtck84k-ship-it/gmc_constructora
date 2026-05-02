<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Password extends MY_Controller
{
    // ================== CAMBIO DE CLAVE (autenticado) ==================

    public function change()
    {
        $this->require_login();
        $this->load->view('auth/password_change', [
            'force' => (int) ($this->user()['force_password_change'] ?? 0),
            'error' => $this->session->flashdata('flash_error'),
            'ok'    => $this->session->flashdata('flash_success'),
        ]);
    }

    public function change_submit()
    {
        $this->require_login();
        $current = (string) $this->input->post('current_password');
        $new     = (string) $this->input->post('new_password');
        $confirm = (string) $this->input->post('new_password_confirm');

        $u = $this->user();

        if (!password_verify($current, $u['password_hash'])) {
            $this->flash('error', 'La contraseña actual es incorrecta.');
            redirect(base_url('password/change'));
        }
        if ($new !== $confirm) {
            $this->flash('error', 'Las contraseñas nuevas no coinciden.');
            redirect(base_url('password/change'));
        }
        if ($err = $this->auth->validatePasswordStrength($new)) {
            $this->flash('error', $err);
            redirect(base_url('password/change'));
        }
        if (password_verify($new, $u['password_hash'])) {
            $this->flash('error', 'La nueva contraseña no puede ser igual a la actual.');
            redirect(base_url('password/change'));
        }

        $this->auth->changePassword((int)$u['id'], $new);
        $this->audit->log('auth.password.changed', 'gmc_usuarios', (int)$u['id']);
        $this->flash('success', 'Contraseña actualizada correctamente.');
        redirect(base_url('dashboard'));
    }

    // ================== RECUPERACIÓN POR EMAIL (anónimo) ==================

    public function forgot()
    {
        if ($this->user_id()) redirect(base_url('dashboard'));
        $this->load->view('auth/password_forgot', [
            'ok'    => $this->session->flashdata('flash_success'),
            'error' => $this->session->flashdata('flash_error'),
        ]);
    }

    public function forgot_submit()
    {
        $email = trim((string) $this->input->post('email'));
        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->flash('error', 'Email inválido.');
            redirect(base_url('password/forgot'));
        }

        $user = $this->db->where('email', $email)
                         ->where('activo', 1)
                         ->where('deleted_at IS NULL', null, false)
                         ->get('gmc_usuarios')->row_array();

        // Mensaje genérico (no revelamos si el email existe o no)
        $this->flash('success', 'Si el email existe, recibirás un correo con instrucciones.');

        if ($user) {
            $ttl = (int) $this->config->item('app_password_reset_ttl');
            $token = $this->auth->createPasswordResetToken((int)$user['id'], $ttl);
            $link = base_url('password/reset?token=' . $token);

            // Encolar correo (lo procesa cli/mailer)
            $this->load->library('Notifier');
            $this->notifier->encolar(
                'password.reset',
                $user['email'],
                'Recuperación de contraseña — ERP GMC',
                "Hola {$user['nombres']},\n\n" .
                "Recibimos una solicitud para restablecer tu contraseña en el ERP GMC. " .
                "Si fuiste tú, abre el siguiente enlace (válido por " . ($ttl/60) . " minutos):\n\n{$link}\n\n" .
                "Si no fuiste tú, ignora este mensaje.",
                ['user_id' => (int)$user['id']]
            );

            $this->audit->log('auth.password.reset_requested', 'gmc_usuarios', (int)$user['id']);
        }

        redirect(base_url('password/forgot'));
    }

    public function reset()
    {
        if ($this->user_id()) redirect(base_url('dashboard'));
        $token = (string) $this->input->get('token');
        $userId = $token ? $this->auth->validatePasswordResetToken($token) : null;

        $this->load->view('auth/password_reset', [
            'token'   => $token,
            'valid'   => (bool) $userId,
            'error'   => $this->session->flashdata('flash_error'),
        ]);
    }

    public function reset_submit()
    {
        $token   = (string) $this->input->post('token');
        $new     = (string) $this->input->post('new_password');
        $confirm = (string) $this->input->post('new_password_confirm');

        $userId = $this->auth->validatePasswordResetToken($token);
        if (!$userId) {
            $this->flash('error', 'El enlace expiró o no es válido. Solicita uno nuevo.');
            redirect(base_url('password/forgot'));
        }
        if ($new !== $confirm) {
            $this->flash('error', 'Las contraseñas no coinciden.');
            redirect(base_url('password/reset?token=' . $token));
        }
        if ($err = $this->auth->validatePasswordStrength($new)) {
            $this->flash('error', $err);
            redirect(base_url('password/reset?token=' . $token));
        }

        $this->auth->changePassword($userId, $new);
        $this->audit->log('auth.password.reset_completed', 'gmc_usuarios', $userId);
        $this->flash('success', 'Contraseña restablecida. Ya puedes iniciar sesión.');
        redirect(base_url('login'));
    }
}
