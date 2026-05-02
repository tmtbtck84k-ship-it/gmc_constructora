<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Seeder PHP idiomático: crea / regenera el usuario administrador con
 * un hash bcrypt fresco (no hardcodeado). Útil cuando se necesita reasignar
 * la clave inicial sin tocar el SQL.
 *
 * Ubicación: application/seeders/AdminUserSeeder.php
 *
 * Uso (con un controller cli/Seed):
 *   php index.php cli/seed AdminUser
 */
class AdminUserSeeder
{
    /** @var CI_DB_query_builder */
    private $db;
    /** @var string */
    private $rolAdminCode = 'admin';

    public function __construct()
    {
        $CI =& get_instance();
        $this->db = $CI->db;
    }

    /**
     * Crea o restablece el admin.
     *
     * @param string|null $password  Si null, se genera una clave temporal aleatoria.
     * @return string Clave en claro (mostrar una sola vez al operador).
     */
    public function run(?string $password = null): string
    {
        $password = $password ?? $this->generarPasswordTemporal();
        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

        $existing = $this->db->where('email', 'admin@gmc.cl')->get('gmc_usuarios')->row();

        if ($existing) {
            $this->db->where('id', $existing->id)->update('gmc_usuarios', [
                'password_hash'         => $hash,
                'password_changed_at'   => null,
                'force_password_change' => 1,
                'activo'                => 1,
                'updated_by'            => $existing->id,
            ]);
            $userId = (int) $existing->id;
        } else {
            $this->db->insert('gmc_usuarios', [
                'rut'                   => '11111111-1',
                'nombres'               => 'Administrador',
                'apellidos'             => 'GMC',
                'email'                 => 'admin@gmc.cl',
                'telefono'              => null,
                'password_hash'         => $hash,
                'password_changed_at'   => null,
                'force_password_change' => 1,
                'activo'                => 1,
            ]);
            $userId = (int) $this->db->insert_id();
        }

        // Asegurar rol admin
        $rolAdmin = $this->db->where('codigo', $this->rolAdminCode)->get('gmc_roles')->row();
        if (!$rolAdmin) {
            throw new RuntimeException('Rol admin no existe; ejecutar el seed_data.sql antes.');
        }

        $exists = $this->db
            ->where('usuario_id', $userId)
            ->where('rol_id', $rolAdmin->id)
            ->count_all_results('gmc_usuarios_roles');

        if (!$exists) {
            $this->db->insert('gmc_usuarios_roles', [
                'usuario_id' => $userId,
                'rol_id'     => $rolAdmin->id,
            ]);
        }

        return $password;
    }

    /**
     * Genera una clave temporal segura (12 chars: minus, mayus, num, simbolo).
     */
    private function generarPasswordTemporal(): string
    {
        $sets = [
            'abcdefghjkmnpqrstuvwxyz',          // sin l/o/i
            'ABCDEFGHJKLMNPQRSTUVWXYZ',         // sin I/O
            '23456789',                         // sin 0/1
            '!@#%&*+-=?',
        ];
        $pwd = '';
        foreach ($sets as $set) {
            $pwd .= $set[random_int(0, strlen($set) - 1)];
        }
        $allChars = implode('', $sets);
        for ($i = strlen($pwd); $i < 14; $i++) {
            $pwd .= $allChars[random_int(0, strlen($allChars) - 1)];
        }
        return str_shuffle($pwd);
    }
}
