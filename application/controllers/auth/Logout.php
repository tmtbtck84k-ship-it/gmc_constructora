<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Logout extends MY_Controller
{
    public function index()
    {
        $uid = $this->user_id();
        if ($uid) {
            $this->audit->log('auth.logout', 'gmc_usuarios', $uid);
        }
        $this->auth->logout();
        redirect(base_url('login'));
    }
}
