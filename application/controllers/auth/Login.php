<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Login extends MY_Controller
{
    public function index()
    {
        // Si ya está logueado, ir al dashboard
        if ($this->user_id()) {
            redirect(base_url('dashboard'));
        }

        $data = [
            'rut'   => $this->input->post('rut') ?: '',
            'error' => $this->session->flashdata('flash_error'),
        ];
        $this->load->view('auth/login', $data);
    }

    public function submit()
    {
        // CSRF está activo globalmente; CI rechaza si falta el token.
        $rut      = (string) $this->input->post('rut', true);
        $password = (string) $this->input->post('password');

        if (!$rut || !$password) {
            $this->flash('error', 'Debes ingresar RUT y contraseña.');
            redirect(base_url('login'));
        }

        $ip = $this->input->ip_address();
        $result = $this->auth->attempt($rut, $password, $ip);

        if (!$result['ok']) {
            $this->audit->log('auth.login.failed', 'gmc_usuarios', null, ['rut' => normalizar_rut($rut)]);
            $this->flash('error', $result['error']);
            redirect(base_url('login'));
        }

        $user = $result['user'];
        $this->auth->startSession($user);
        $this->audit->log('auth.login.ok', 'gmc_usuarios', (int)$user['id']);

        // Si force_password_change → MY_Controller redirige automático
        redirect(base_url('dashboard'));
    }
}
