<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'services/ReporteService.php';

class Dashboard extends MY_AuthController
{
    public function index()
    {
        $svc = new ReporteService();
        $kpis  = $svc->kpisDashboard();
        $serie = $svc->gastoUltimos12Meses();

        // Últimas bitácoras (5)
        $bitacoras = $this->db
            ->select('b.id, b.numero, b.titulo, b.fecha_evento, b.tipo_evento, p.codigo AS proyecto_codigo')
            ->from('gmc_bitacoras b')
            ->join('gmc_proyectos p', 'p.id = b.proyecto_id', 'left')
            ->where('b.deleted_at IS NULL', null, false)
            ->order_by('b.id', 'DESC')
            ->limit(5)
            ->get()->result_array();

        $cards = [
            ['label'=>'SDP Pendientes',      'icon'=>'hourglass-split', 'value'=>number_format($kpis['sdp_pendientes'], 0, ',', '.'),   'color'=>'warning'],
            ['label'=>'SDP Validadas',       'icon'=>'check2-circle',   'value'=>number_format($kpis['sdp_validadas'], 0, ',', '.'),    'color'=>'info'],
            ['label'=>'SDP Programadas',     'icon'=>'calendar-event',  'value'=>number_format($kpis['sdp_programadas'], 0, ',', '.'),  'color'=>'primary'],
            ['label'=>'SDP Pagadas (mes)',   'icon'=>'check2-all',      'value'=>number_format($kpis['sdp_pagadas_mes'], 0, ',', '.'),  'color'=>'success'],
            ['label'=>'Proyectos activos',   'icon'=>'kanban',          'value'=>number_format($kpis['proyectos_activos'], 0, ',', '.'),'color'=>'secondary'],
            ['label'=>'Gasto del mes (CLP)', 'icon'=>'cash-stack',      'value'=>'$ ' . number_format($kpis['gasto_mes_clp'], 0, ',', '.'),'color'=>'dark'],
        ];

        $this->view('dashboard/index', [
            'kpis'      => $cards,
            'serie'     => $serie,
            'bitacoras' => $bitacoras,
        ]);
    }
}
