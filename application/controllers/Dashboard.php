<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Dashboard extends MY_AuthController
{
    public function index()
    {
        // Datos placeholder; los KPIs reales se llenan en Sprint 5.
        $data = [
            'kpis' => [
                ['label'=>'SDP Pendientes',      'icon'=>'hourglass-split', 'value'=>'—', 'color'=>'warning'],
                ['label'=>'SDP Validadas',       'icon'=>'check2-circle',   'value'=>'—', 'color'=>'info'],
                ['label'=>'SDP Programadas',     'icon'=>'calendar-event',  'value'=>'—', 'color'=>'primary'],
                ['label'=>'SDP Pagadas (mes)',   'icon'=>'check2-all',      'value'=>'—', 'color'=>'success'],
                ['label'=>'Proyectos activos',   'icon'=>'kanban',          'value'=>'—', 'color'=>'secondary'],
                ['label'=>'Gasto del mes (CLP)', 'icon'=>'cash-stack',      'value'=>'—', 'color'=>'dark'],
            ],
        ];
        $this->view('dashboard/index', $data);
    }
}
