<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Comando CLI: ejecuta seeders idiomáticos.
 *
 * Uso:
 *   php index.php cli/seed AdminUser           # regenera admin con clave fresca
 *   php index.php cli/seed AdminUser MiClave!  # con clave específica
 */
class Seed extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        if (!$this->input->is_cli_request()) {
            show_error('Este comando sólo puede ejecutarse desde CLI.', 403);
        }
    }

    public function index($name = null, $arg1 = null)
    {
        if (!$name) {
            fwrite(STDERR, "Uso: php index.php cli/seed <NombreSeeder> [args]\n");
            fwrite(STDERR, "Disponibles:\n");
            foreach (glob(APPPATH . 'seeders/*.php') as $f) {
                fwrite(STDERR, '  ' . str_replace(['Seeder.php','.php'], '', basename($f)) . "\n");
            }
            exit(1);
        }

        $class = $name . 'Seeder';
        $file  = APPPATH . 'seeders/' . $class . '.php';
        if (!is_file($file)) {
            fwrite(STDERR, "Seeder no encontrado: {$file}\n");
            exit(1);
        }
        require_once $file;
        if (!class_exists($class)) {
            fwrite(STDERR, "La clase {$class} no existe en {$file}\n");
            exit(1);
        }

        $seeder = new $class();
        $result = $seeder->run($arg1);

        if (is_string($result) && $result !== '') {
            echo "Clave temporal generada: {$result}\n";
            echo "(El usuario será obligado a cambiarla en su próximo login)\n";
        } else {
            echo "Seeder {$name} ejecutado correctamente.\n";
        }
    }
}
