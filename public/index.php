<?php
/*
 *---------------------------------------------------------------
 * GMC ERP — Front Controller
 *---------------------------------------------------------------
 * DocumentRoot apunta a public/. Esto deja application/, system/
 * y storage/ FUERA del alcance público.
 */

// Detección de entorno desde .env.php (cargado por config.php)
$envFile = __DIR__ . '/../application/config/.env.php';
$ENVIRONMENT = 'development';
if (is_file($envFile)) {
    $env = require $envFile;
    if (!empty($env['ENVIRONMENT'])) $ENVIRONMENT = $env['ENVIRONMENT'];
}
define('ENVIRONMENT', $ENVIRONMENT);

switch (ENVIRONMENT) {
    case 'development':
        // PHP 8.2 emite muchos warnings de "dynamic property" desde el core
        // de CI3 (no es código nuestro). Silenciamos sólo deprecations.
        error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);
        ini_set('display_errors', '1');
        break;
    case 'testing':
    case 'production':
        ini_set('display_errors', '0');
        error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT & ~E_USER_NOTICE & ~E_USER_DEPRECATED);
        break;
    default:
        header('HTTP/1.1 503 Service Unavailable.', TRUE, 503);
        echo 'The application environment is not set correctly.';
        exit(1);
}

$system_path     = '../system';
$application_folder = '../application';
$view_folder        = '';

// Resoluciones
if (defined('STDIN')) chdir(__DIR__);

if (($_temp = realpath($system_path)) !== FALSE) {
    $system_path = $_temp . DIRECTORY_SEPARATOR;
} else {
    $system_path = strtr(rtrim($system_path, '/\\'), '/\\', DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
}

define('SELF',           pathinfo(__FILE__, PATHINFO_BASENAME));
define('BASEPATH',       $system_path);
define('FCPATH',         __DIR__ . DIRECTORY_SEPARATOR);
define('SYSDIR',         basename(BASEPATH));

if (is_dir($application_folder)) {
    if (($_temp = realpath($application_folder)) !== FALSE) {
        $application_folder = $_temp;
    }
    define('APPPATH', $application_folder . DIRECTORY_SEPARATOR);
} else {
    if (!is_dir(BASEPATH . $application_folder . DIRECTORY_SEPARATOR)) {
        header('HTTP/1.1 503 Service Unavailable.', TRUE, 503);
        echo 'Your application folder path does not appear to be set correctly.';
        exit(3);
    }
    define('APPPATH', BASEPATH . $application_folder . DIRECTORY_SEPARATOR);
}

if (!isset($view_folder[0])) {
    if (is_dir(APPPATH . 'views')) $view_folder = APPPATH . 'views';
}
define('VIEWPATH', $view_folder . DIRECTORY_SEPARATOR);

require_once BASEPATH . 'core/CodeIgniter.php';
