<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Comando CLI: procesa la cola gmc_notificaciones.
 * Cron sugerido: cada 5 minutos.
 *
 *   *\/5 * * * * www-data cd /var/www/gmc && /usr/bin/php index.php cli/mailer >> /var/log/gmc/mailer.log 2>&1
 */
class Mailer extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        if (!$this->input->is_cli_request()) {
            show_error('Este comando sólo puede ejecutarse desde CLI.', 403);
        }
        $this->load->library('Notifier');
    }

    public function index($batch = 50, $maxIntentos = 3)
    {
        $batch       = max(1, (int)$batch);
        $maxIntentos = max(1, (int)$maxIntentos);

        echo '[' . date('Y-m-d H:i:s') . "] mailer: lote={$batch} max_intentos={$maxIntentos}\n";
        $r = $this->notifier->procesarCola($batch, $maxIntentos);
        echo "[" . date('Y-m-d H:i:s') . "] mailer: enviadas={$r['procesadas']} fallidas={$r['fallidas']}\n";
    }
}
