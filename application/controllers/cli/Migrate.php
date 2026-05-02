<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Comando CLI: migraciones.
 *
 * Ubicación: application/controllers/cli/Migrate.php
 *
 * Uso:
 *   php index.php cli/migrate latest       Aplica todas las pendientes.
 *   php index.php cli/migrate version 20260501000002   Migrar a una versión.
 *   php index.php cli/migrate rollback     Rollback a la versión anterior.
 *   php index.php cli/migrate status       Lista qué migraciones hay y cuáles aplicaron.
 *
 * Requiere haber configurado application/config/migration.php (ver
 * config_migration.php en este rediseño).
 */
class Migrate extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        if (!$this->input->is_cli_request()) {
            show_error('Este comando sólo puede ejecutarse desde CLI.', 403);
        }
        $this->load->library('migration');
        $this->load->dbforge();
    }

    public function index()
    {
        $this->latest();
    }

    public function latest()
    {
        echo "Ejecutando migraciones a la última versión...\n";
        if ($this->migration->latest() === FALSE) {
            $this->_fail($this->migration->error_string());
        }
        echo "Migraciones aplicadas correctamente.\n";
    }

    public function version($version = null)
    {
        if ($version === null) {
            $this->_fail('Debes indicar una versión (timestamp).');
        }
        echo "Migrando a la versión {$version}...\n";
        if ($this->migration->version($version) === FALSE) {
            $this->_fail($this->migration->error_string());
        }
        echo "Migración a {$version} OK.\n";
    }

    public function rollback()
    {
        // Rollback al penúltimo timestamp registrado en `migrations`
        $current = $this->db->get('migrations')->row();
        if (!$current) {
            $this->_fail('No hay migraciones aplicadas.');
        }
        // Buscar la versión anterior por orden alfabético del archivo
        $files = glob(APPPATH . 'migrations/*.php');
        if (!$files) $this->_fail('No se encontraron archivos de migración.');

        $versions = [];
        foreach ($files as $f) {
            if (preg_match('/(\d{14})_/', basename($f), $m)) {
                $versions[] = $m[1];
            }
        }
        sort($versions);
        $idx = array_search($current->version, $versions);
        if ($idx === false || $idx === 0) {
            $this->_fail('No se puede hacer rollback: no hay versión anterior.');
        }
        $previous = $versions[$idx - 1];

        echo "Rollback {$current->version} -> {$previous}...\n";
        if ($this->migration->version($previous) === FALSE) {
            $this->_fail($this->migration->error_string());
        }
        echo "Rollback OK.\n";
    }

    public function status()
    {
        $current = $this->db->get('migrations')->row();
        $cur = $current ? $current->version : '(ninguna)';
        echo "Migración actual: {$cur}\n";

        $files = glob(APPPATH . 'migrations/*.php') ?: [];
        sort($files);
        echo "\nArchivos disponibles:\n";
        foreach ($files as $f) {
            echo "  " . basename($f) . "\n";
        }
    }

    private function _fail(string $msg): void
    {
        fwrite(STDERR, "[ERROR] {$msg}\n");
        exit(1);
    }
}
