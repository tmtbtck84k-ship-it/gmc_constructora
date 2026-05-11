<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Comando CLI: sincroniza tipos de cambio desde mindicador.cl
 *
 * Cron sugerido (diario 09:30 hora Chile):
 *   30 9 * * * www-data cd /var/www/gmc && /usr/bin/php public/index.php cli/sync_tc >> /var/log/gmc/sync_tc.log 2>&1
 *
 * Uso manual:
 *   php public/index.php cli/sync_tc
 *
 * Reglas:
 *   - Trae UF, USD, EUR del día desde mindicador.cl.
 *   - Hace upsert con origen='auto'.
 *   - Si ya existe un TC 'manual' para esa fecha, NO lo sobreescribe.
 *   - Cualquier error de red/API se loguea pero no rompe (exit 0 siempre,
 *     el cron seguirá intentando al día siguiente).
 */
class Sync_tc extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        if (!$this->input->is_cli_request()) {
            show_error('Este comando sólo puede ejecutarse desde CLI.', 403);
        }
        $this->load->library('Mindicador');
        $this->load->library('Audit');
        $this->load->model(['MonedaRepo','TipoCambioRepo']);
    }

    public function index()
    {
        $tag = '[' . date('Y-m-d H:i:s') . ']';
        echo "{$tag} sync_tc: iniciando...\n";

        try {
            $indicadores = $this->mindicador->indicadoresHoy();
        } catch (\Throwable $e) {
            $msg = "sync_tc: ERROR llamando a mindicador.cl: " . $e->getMessage();
            echo "{$tag} {$msg}\n";
            log_message('error', $msg);
            $this->audit->log('tipo_cambio.sync_error', 'gmc_tipos_cambio', null, null, ['error' => $e->getMessage()]);
            return; // No romper el cron
        }

        $resumen = ['creado' => 0, 'actualizado' => 0, 'preservado_manual' => 0, 'sin_moneda' => 0];

        foreach ($indicadores as $codigo => $info) {
            $moneda = $this->MonedaRepo->findByCodigo($codigo);
            if (!$moneda) {
                $resumen['sin_moneda']++;
                echo "{$tag} sync_tc: moneda '{$codigo}' no existe en gmc_monedas, saltando\n";
                continue;
            }
            $r = $this->TipoCambioRepo->upsert(
                (int)$moneda['id'],
                $info['fecha'],
                (float)$info['valor'],
                'auto',
                null    // ejecutado por sistema, no usuario
            );
            $resumen[$r['accion']]++;
            echo "{$tag} sync_tc: {$codigo} {$info['fecha']} = {$info['valor']} → {$r['accion']}\n";
        }

        $this->audit->log('tipo_cambio.sync_auto', 'gmc_tipos_cambio', null, null, [
            'fecha'     => date('Y-m-d'),
            'resumen'   => $resumen,
            'fuente'    => 'mindicador.cl',
        ]);

        echo "{$tag} sync_tc: terminado. Creados={$resumen['creado']} Actualizados={$resumen['actualizado']} PreservadosManual={$resumen['preservado_manual']}\n";
    }
}
