<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Auth — autenticación, password hashing, recuperación.
 */
class Auth
{
    /** @var CI_Controller */
    protected $CI;

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->library('RateLimiter');
        $this->CI->load->helper('rut');
    }

    /**
     * Intenta autenticar por RUT + password.
     *
     * @return array{ok:bool, user:array|null, error:string}
     */
    public function attempt(string $rut, string $password, string $ip): array
    {
        $rut = normalizar_rut($rut);

        // Rate limit
        $blocked = $this->CI->ratelimiter->isBlocked($ip, $rut);
        if ($blocked) {
            return ['ok' => false, 'user' => null, 'error' => 'Demasiados intentos. Intenta nuevamente en unos minutos.'];
        }

        // Buscar usuario activo no eliminado
        $user = $this->CI->db
            ->where('rut', $rut)
            ->where('activo', 1)
            ->where('deleted_at IS NULL', null, false)
            ->get('gmc_usuarios')->row_array();

        if (!$user) {
            $this->CI->ratelimiter->recordFailure($ip, $rut);
            return ['ok' => false, 'user' => null, 'error' => 'Credenciales inválidas.'];
        }

        if (!password_verify($password, $user['password_hash'])) {
            $this->CI->ratelimiter->recordFailure($ip, $rut);
            return ['ok' => false, 'user' => null, 'error' => 'Credenciales inválidas.'];
        }

        // Rehash si el algoritmo cambió o el cost subió
        if (password_needs_rehash($user['password_hash'], PASSWORD_BCRYPT, ['cost' => 12])) {
            $newHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
            $this->CI->db->where('id', $user['id'])
                         ->update('gmc_usuarios', ['password_hash' => $newHash]);
        }

        // Registrar éxito y limpiar fallos
        $this->CI->ratelimiter->recordSuccess($ip, $rut);
        $this->CI->db->where('id', $user['id'])->update('gmc_usuarios', [
            'ultimo_login_at' => date('Y-m-d H:i:s'),
            'ultimo_login_ip' => $ip,
        ]);

        return ['ok' => true, 'user' => $user, 'error' => ''];
    }

    /**
     * Crea sesión segura tras login exitoso.
     */
    public function startSession(array $user): void
    {
        // Regenera ID de sesión para evitar fixation
        $this->CI->session->sess_regenerate(true);
        $this->CI->session->set_userdata([
            'user_id'   => (int) $user['id'],
            'user_rut'  => $user['rut'],
            'user_name' => trim($user['nombres'] . ' ' . $user['apellidos']),
            'login_at'  => time(),
        ]);
    }

    public function logout(): void
    {
        $this->CI->session->sess_destroy();
    }

    /**
     * Genera y guarda un token de recuperación de clave.
     */
    public function createPasswordResetToken(int $userId, int $ttlSeconds): string
    {
        $token = bin2hex(random_bytes(32));
        $hash  = hash('sha256', $token);
        $expira= date('Y-m-d H:i:s', time() + $ttlSeconds);

        // Reutilizamos gmc_notificaciones como almacén temporal? No, mejor una tabla simple.
        // Por ahora, almacenamos en una tabla auxiliar in-memory: usaremos una columna en
        // notificaciones con tipo=password.reset y payload con userId+hash+expira.
        $this->CI->db->insert('gmc_notificaciones', [
            'tipo'    => 'password.reset.token',
            'canal'   => 'sistema',
            'destinatario_usuario_id' => $userId,
            'asunto'  => 'Token de recuperación',
            'cuerpo'  => '',
            'payload' => json_encode(['hash' => $hash, 'expira' => $expira]),
            'estado'  => 'enviada',  // no se reenvía
            'enviada_at' => date('Y-m-d H:i:s'),
        ]);

        return $token;
    }

    /**
     * Valida un token recibido y devuelve el user_id si OK.
     */
    public function validatePasswordResetToken(string $token): ?int
    {
        $hash = hash('sha256', $token);
        $rows = $this->CI->db
            ->where('tipo', 'password.reset.token')
            ->order_by('id', 'DESC')
            ->limit(50)
            ->get('gmc_notificaciones')->result_array();

        foreach ($rows as $r) {
            $payload = json_decode($r['payload'], true);
            if (!is_array($payload)) continue;
            if (!hash_equals($payload['hash'] ?? '', $hash)) continue;
            if (strtotime($payload['expira'] ?? '1970-01-01') < time()) return null;
            return (int) $r['destinatario_usuario_id'];
        }
        return null;
    }

    /**
     * Cambia clave: hash + flags + auditoría.
     */
    public function changePassword(int $userId, string $newPassword): void
    {
        $hash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);
        $this->CI->db->where('id', $userId)->update('gmc_usuarios', [
            'password_hash'         => $hash,
            'password_changed_at'   => date('Y-m-d H:i:s'),
            'force_password_change' => 0,
        ]);
    }

    /**
     * Validador de fortaleza de password.
     * Mín 8, al menos: una mayúscula, una minúscula, un dígito, un símbolo.
     *
     * @return string '' si OK, mensaje de error si falla.
     */
    public function validatePasswordStrength(string $pwd): string
    {
        if (strlen($pwd) < 8) return 'La clave debe tener al menos 8 caracteres.';
        if (!preg_match('/[A-Z]/', $pwd)) return 'La clave debe incluir una mayúscula.';
        if (!preg_match('/[a-z]/', $pwd)) return 'La clave debe incluir una minúscula.';
        if (!preg_match('/\d/', $pwd))    return 'La clave debe incluir un dígito.';
        if (!preg_match('/[^A-Za-z0-9]/', $pwd)) return 'La clave debe incluir un símbolo.';
        return '';
    }
}
