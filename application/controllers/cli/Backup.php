<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Comando CLI: backup MySQL diario.
 *
 * Cron sugerido (02:00 hora servidor):
 *   0 2 * * * www-data cd /var/www/gmc && /usr/bin/php public/index.php cli/backup >> /var/log/gmc/backup.log 2>&1
 *
 * Genera:
 *   <BACKUP_PATH>/gmc-YYYYMMDD-HHMMSS.sql.gz
 * Y borra archivos > BACKUP_RETENTION_DAYS (default 14).
 */
class Backup extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        if (!$this->input->is_cli_request()) {
            show_error('Este comando sólo puede ejecutarse desde CLI.', 403);
        }
        $this->load->library('Audit');
    }

    public function index()
    {
        $tag = '[' . date('Y-m-d H:i:s') . ']';
        $base = (string)$this->config->item('app_backup_path');
        $retDays = (int)$this->config->item('app_backup_retention_days');

        if (!$base) {
            $msg = "BACKUP_PATH no configurado en .env.php";
            echo "{$tag} backup: ERROR {$msg}\n";
            return;
        }
        if (!is_dir($base) && !@mkdir($base, 0750, true)) {
            $msg = "No se pudo crear directorio de backup: {$base}";
            echo "{$tag} backup: ERROR {$msg}\n";
            return;
        }

        // Lee credenciales BD
        $env = require APPPATH . 'config/.env.php';
        $host = escapeshellarg($env['DB_HOSTNAME'] ?? 'localhost');
        $port = (int)($env['DB_PORT'] ?? 3306);
        $user = escapeshellarg($env['DB_USERNAME'] ?? 'root');
        $pass = $env['DB_PASSWORD'] ?? '';
        $db   = escapeshellarg($env['DB_DATABASE'] ?? 'gmc_erp');

        $stamp = date('Ymd-His');
        $file  = "{$base}/gmc-{$stamp}.sql.gz";

        // Buscar mysqldump
        $mysqldump = $this->_which('mysqldump') ?: '/usr/bin/mysqldump';

        // Construir comando seguro (password vía MYSQL_PWD para no exponer en process list)
        $cmd = sprintf(
            'MYSQL_PWD=%s %s --single-transaction --routines --triggers --events -h %s -P %d -u %s %s 2>/dev/null | gzip > %s',
            escapeshellarg((string)$pass),
            escapeshellarg($mysqldump),
            $host, $port, $user, $db,
            escapeshellarg($file)
        );

        echo "{$tag} backup: ejecutando mysqldump → {$file}\n";
        exec($cmd, $output, $rc);

        if ($rc !== 0 || !is_file($file) || filesize($file) < 1024) {
            $msg = "mysqldump falló (rc={$rc}). Revisa que mysqldump esté instalado y accesible.";
            echo "{$tag} backup: ERROR {$msg}\n";
            $this->audit->log('backup.error', null, null, null, ['file' => $file, 'rc' => $rc]);
            @unlink($file);
            return;
        }

        $size = filesize($file);
        echo "{$tag} backup: OK ({$file}, " . round($size / 1024 / 1024, 2) . " MB)\n";

        // Rotación
        $borrados = 0;
        if ($retDays > 0) {
            $umbral = time() - ($retDays * 86400);
            foreach (glob("{$base}/gmc-*.sql.gz") as $old) {
                if (filemtime($old) < $umbral) { @unlink($old); $borrados++; }
            }
        }
        echo "{$tag} backup: rotación → {$borrados} archivos > {$retDays} días borrados.\n";

        $this->audit->log('backup.ok', null, null, null, [
            'file' => basename($file), 'size_mb' => round($size / 1024 / 1024, 2), 'borrados' => $borrados,
        ]);
    }

    private function _which(string $bin): ?string
    {
        $out = [];
        @exec("command -v " . escapeshellarg($bin) . " 2>/dev/null", $out, $rc);
        return ($rc === 0 && !empty($out[0])) ? $out[0] : null;
    }
}
