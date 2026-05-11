<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Comando CLI: limpieza diaria de datos transitorios.
 *
 * Cron sugerido (03:00):
 *   0 3 * * * www-data cd /var/www/gmc && /usr/bin/php public/index.php cli/cleanup >> /var/log/gmc/cleanup.log 2>&1
 *
 * Acciones:
 *   - Borra adjuntos físicos huérfanos en storage/uploads (sin fila viva en gmc_adjuntos).
 *   - Borra registros gmc_login_attempts > 30 días.
 *   - Borra notificaciones enviadas > 90 días.
 *   - Borra adjuntos soft-deleted > 60 días (físico + DB).
 */
class Cleanup extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        if (!$this->input->is_cli_request()) {
            show_error('Este comando sólo puede ejecutarse desde CLI.', 403);
        }
        $this->load->library(['Audit','RateLimiter']);
    }

    public function index()
    {
        $tag = '[' . date('Y-m-d H:i:s') . ']';
        echo "{$tag} cleanup: iniciando...\n";

        // 1. Login attempts > 30 días
        $deletedAttempts = $this->ratelimiter->purgeOlderThan(30);
        echo "{$tag} cleanup: gmc_login_attempts borrados ({$deletedAttempts} > 30 días)\n";

        // 2. Notificaciones enviadas > 90 días
        $this->db->where('estado', 'enviada')
                 ->where('enviada_at <', date('Y-m-d H:i:s', strtotime('-90 days')))
                 ->delete('gmc_notificaciones');
        $delNotif = $this->db->affected_rows();
        echo "{$tag} cleanup: gmc_notificaciones enviadas borradas ({$delNotif} > 90 días)\n";

        // 3. Adjuntos soft-deleted > 60 días: borrar archivo físico + fila
        $base = rtrim((string)$this->config->item('app_upload_base_path'), '/');
        $rows = $this->db->where('deleted_at IS NOT NULL', null, false)
                         ->where('deleted_at <', date('Y-m-d H:i:s', strtotime('-60 days')))
                         ->get('gmc_adjuntos')->result_array();
        $delAdj = 0; $delBytes = 0;
        foreach ($rows as $r) {
            $path = $base . '/' . $r['ruta'];
            if (is_file($path)) { $delBytes += filesize($path); @unlink($path); }
            $this->db->where('id', (int)$r['id'])->delete('gmc_adjuntos');
            $delAdj++;
        }
        echo "{$tag} cleanup: gmc_adjuntos soft-deleted purgados ({$delAdj} archivos, " . round($delBytes/1024/1024, 2) . " MB)\n";

        // 4. Archivos físicos huérfanos en storage/uploads (sin fila viva en BD)
        $orphans = 0; $orphBytes = 0;
        if (is_dir($base)) {
            $iter = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base, RecursiveDirectoryIterator::SKIP_DOTS));
            foreach ($iter as $file) {
                if (!$file->isFile()) continue;
                $relpath = substr($file->getPathname(), strlen($base) + 1);
                $found = $this->db->where('ruta', $relpath)->count_all_results('gmc_adjuntos');
                if ($found === 0) {
                    // Sólo borrar archivos de más de 7 días para no afectar uploads en curso
                    if (filemtime($file->getPathname()) < time() - 7 * 86400) {
                        $orphBytes += $file->getSize();
                        @unlink($file->getPathname());
                        $orphans++;
                    }
                }
            }
        }
        echo "{$tag} cleanup: archivos huérfanos purgados ({$orphans} archivos, " . round($orphBytes/1024/1024, 2) . " MB)\n";

        $this->audit->log('cleanup.ok', null, null, null, [
            'login_attempts'   => $deletedAttempts,
            'notif_enviadas'   => $delNotif,
            'adjuntos_purgados'=> $delAdj,
            'huerfanos'        => $orphans,
        ]);

        echo "{$tag} cleanup: terminado.\n";
    }
}
