<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Hook: agrega headers de seguridad en cada respuesta HTML.
 * Se carga en post_controller_constructor (config/hooks.php).
 */
class SecurityHeaders
{
    public function send()
    {
        $CI =& get_instance();
        $env = $CI->config->item('app_env');

        $headers = [
            'X-Frame-Options'        => 'DENY',
            'X-Content-Type-Options' => 'nosniff',
            'Referrer-Policy'        => 'same-origin',
            'X-XSS-Protection'       => '1; mode=block',
        ];

        // En producción, además: HSTS y CSP
        if ($env === 'production') {
            $headers['Strict-Transport-Security'] = 'max-age=31536000; includeSubDomains';
            $headers['Content-Security-Policy']   = "default-src 'self'; "
                . "style-src 'self' 'unsafe-inline'; "
                . "script-src 'self' 'unsafe-inline'; "
                . "img-src 'self' data:; "
                . "font-src 'self' data:; "
                . "connect-src 'self'; "
                . "frame-ancestors 'none'";
        }

        foreach ($headers as $k => $v) {
            if (!headers_sent()) header("{$k}: {$v}");
        }

        // Forzar HTTPS en producción
        if ($env === 'production' && empty($_SERVER['HTTPS'])) {
            $url = 'https://' . ($_SERVER['HTTP_HOST'] ?? '') . ($_SERVER['REQUEST_URI'] ?? '');
            header("Location: {$url}", true, 301);
            exit;
        }
    }
}
