<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| GMC ERP — config.php
|--------------------------------------------------------------------------
| Lee variables de application/config/.env.php (no versionado).
*/

$env = require __DIR__ . '/.env.php';

// Helper inline (no podemos cargar helpers aquí)
function gmc_env(array $env, string $k, $default = null) {
    return array_key_exists($k, $env) ? $env[$k] : $default;
}

$config['base_url']        = rtrim(gmc_env($env, 'BASE_URL', 'http://localhost:8000'), '/') . '/';
$config['index_page']      = '';
$config['uri_protocol']    = 'REQUEST_URI';

$config['url_suffix']      = '';
$config['language']        = 'spanish';
$config['charset']         = 'UTF-8';

$config['enable_hooks']    = TRUE;
$config['subclass_prefix'] = 'MY_';

$config['composer_autoload'] = realpath(__DIR__ . '/../../vendor/autoload.php') ?: FALSE;

$config['permitted_uri_chars'] = 'a-z 0-9~%.:_\-';

$config['allow_get_array']     = TRUE;
$config['enable_query_strings']= FALSE;
$config['controller_trigger']  = 'c';
$config['function_trigger']    = 'm';
$config['directory_trigger']   = 'd';

// Logs
$config['log_threshold']    = (gmc_env($env, 'ENVIRONMENT') === 'production') ? 1 : 4;
$config['log_path']         = '';
$config['log_file_extension'] = '';
$config['log_file_permissions']= 0644;
$config['log_date_format']  = 'Y-m-d H:i:s';

// Errors
$config['error_views_path'] = '';

// Cache
$config['cache_path']           = '';
$config['cache_query_string']   = FALSE;

// Encripción (lee del .env)
$config['encryption_key'] = gmc_env($env, 'ENCRYPTION_KEY', '');

// Sesiones — driver "database" para guardar en gmc_sesiones
$config['sess_driver']            = 'database';
$config['sess_cookie_name']       = gmc_env($env, 'SESSION_NAME', 'gmc_session');
$config['sess_expiration']        = (int) gmc_env($env, 'SESSION_EXPIRATION', 7200);
$config['sess_save_path']         = 'gmc_sesiones';
$config['sess_match_ip']          = FALSE;
$config['sess_time_to_update']    = 300;
$config['sess_regenerate_destroy']= TRUE;

// Cookies
$config['cookie_prefix']    = '';
$config['cookie_domain']    = '';
$config['cookie_path']      = '/';
$config['cookie_secure']    = (bool) gmc_env($env, 'SESSION_COOKIE_SECURE', FALSE);
$config['cookie_httponly']  = TRUE;
$config['cookie_samesite']  = gmc_env($env, 'SESSION_SAMESITE', 'Lax');

// Standardize Newlines
$config['standardize_newlines'] = FALSE;

// Cross Site Request Forgery
$config['csrf_protection']      = TRUE;
$config['csrf_token_name']      = 'csrf_gmc';
$config['csrf_cookie_name']     = 'csrf_gmc_cookie';
$config['csrf_expire']          = 7200;
$config['csrf_regenerate']      = TRUE;
$config['csrf_exclude_uris']    = [];

// Output Compression
$config['compress_output']      = FALSE;

// Master Time Reference (server | local)
$config['time_reference']       = 'local';

// Rewrite PHP Short Tags
$config['rewrite_short_tags']   = FALSE;

// Reverse proxy
$config['proxy_ips']            = '';

// Variables de aplicación (custom, accesibles vía $this->config->item('app_xxx'))
$config['app_env']                  = gmc_env($env, 'ENVIRONMENT', 'development');
$config['app_company_name']         = gmc_env($env, 'COMPANY_NAME', 'GMC');
$config['app_company_rut']          = gmc_env($env, 'COMPANY_RUT', '');
$config['app_upload_max_bytes']     = (int) gmc_env($env, 'UPLOAD_MAX_BYTES', 10485760);
$config['app_upload_base_path']     = gmc_env($env, 'UPLOAD_BASE_PATH', FCPATH . '../storage/uploads');
$config['app_upload_allowed_ext']   = gmc_env($env, 'UPLOAD_ALLOWED_EXT', 'pdf,jpg,jpeg,png,doc,docx,xls,xlsx,csv,txt');
$config['app_login_max_attempts']   = (int) gmc_env($env, 'LOGIN_MAX_ATTEMPTS', 5);
$config['app_login_window_seconds'] = (int) gmc_env($env, 'LOGIN_WINDOW_SECONDS', 900);
$config['app_login_lockout_seconds']= (int) gmc_env($env, 'LOGIN_LOCKOUT_SECONDS', 900);
$config['app_password_reset_ttl']   = (int) gmc_env($env, 'PASSWORD_RESET_TTL', 3600);
$config['app_smtp_host']            = gmc_env($env, 'SMTP_HOST');
$config['app_smtp_port']            = (int) gmc_env($env, 'SMTP_PORT', 587);
$config['app_smtp_user']            = gmc_env($env, 'SMTP_USERNAME');
$config['app_smtp_pass']            = gmc_env($env, 'SMTP_PASSWORD');
$config['app_smtp_encryption']      = gmc_env($env, 'SMTP_ENCRYPTION', 'tls');
$config['app_smtp_from_name']       = gmc_env($env, 'SMTP_FROM_NAME', 'ERP GMC');
$config['app_smtp_from_email']      = gmc_env($env, 'SMTP_FROM_EMAIL');
$config['app_backup_path']          = gmc_env($env, 'BACKUP_PATH', '/var/backups/gmc');
$config['app_backup_retention_days']= (int) gmc_env($env, 'BACKUP_RETENTION_DAYS', 14);
