<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * RateLimiter — control de intentos de login (IP + RUT).
 *
 * Configurable vía application/config/.env.php:
 *   LOGIN_MAX_ATTEMPTS, LOGIN_WINDOW_SECONDS, LOGIN_LOCKOUT_SECONDS.
 *
 * Reglas:
 *   - Si en la ventana hubo >= max_attempts fallos sin éxito intermedio → bloqueado.
 *   - Un éxito reciente (dentro de la ventana) "limpia" el contador.
 */
class RateLimiter
{
    /** @var CI_Controller */
    protected $CI;

    public function __construct()
    {
        $this->CI =& get_instance();
    }

    public function isBlocked(string $ip, ?string $rut): bool
    {
        $window  = (int) $this->CI->config->item('app_login_window_seconds');
        $maxFail = (int) $this->CI->config->item('app_login_max_attempts');

        // Cuenta fallos por IP en la ventana
        $sql = "SELECT COUNT(*) AS c FROM gmc_login_attempts
                WHERE ip = ? AND exitoso = 0
                  AND created_at >= (NOW() - INTERVAL ? SECOND)";
        $byIp = (int) $this->CI->db->query($sql, [$ip, $window])->row()->c;

        $byRut = 0;
        if ($rut) {
            $sql = "SELECT COUNT(*) AS c FROM gmc_login_attempts
                    WHERE rut = ? AND exitoso = 0
                      AND created_at >= (NOW() - INTERVAL ? SECOND)";
            $byRut = (int) $this->CI->db->query($sql, [$rut, $window])->row()->c;
        }

        return ($byIp >= $maxFail) || ($byRut >= $maxFail);
    }

    public function recordFailure(string $ip, ?string $rut): void
    {
        $this->CI->db->insert('gmc_login_attempts', [
            'ip'       => $ip,
            'rut'      => $rut,
            'exitoso'  => 0,
        ]);
    }

    public function recordSuccess(string $ip, ?string $rut): void
    {
        $this->CI->db->insert('gmc_login_attempts', [
            'ip'       => $ip,
            'rut'      => $rut,
            'exitoso'  => 1,
        ]);
    }

    /**
     * Limpieza de registros viejos (llamado por cli/cleanup).
     */
    public function purgeOlderThan(int $days = 30): int
    {
        return (int) $this->CI->db->query(
            'DELETE FROM gmc_login_attempts WHERE created_at < (NOW() - INTERVAL ? DAY)',
            [$days]
        );
    }
}
