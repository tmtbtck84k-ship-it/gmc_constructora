<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
| -------------------------------------------------------------------------
| Hooks - GMC ERP
| -------------------------------------------------------------------------
| Headers de seguridad y forzado de HTTPS en producción.
*/

$hook['post_controller_constructor'][] = [
    'class'    => 'SecurityHeaders',
    'function' => 'send',
    'filename' => 'SecurityHeaders.php',
    'filepath' => 'hooks',
];
