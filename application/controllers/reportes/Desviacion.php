<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'services/ReporteService.php';

class Desviacion extends MY_AuthController
{
    protected $svc;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('ProyectoRepo');
        $this->svc = new ReporteService();
    }

    public function index()
    {
        $this->require_permission('reportes.desviacion.ver');
        $proyectoId = (int)$this->input->get('proyecto_id');
        $proyecto = $proyectoId ? $this->ProyectoRepo->findFull($proyectoId) : null;
        $data = $proyectoId ? $this->svc->desviacion($proyectoId) : null;

        $this->view('reportes/desviacion/index', [
            'proyecto'  => $proyecto,
            'proyectos' => $this->ProyectoRepo->activos(),
            'data'      => $data,
        ]);
    }

    public function exportar()
    {
        $this->require_permission('reportes.desviacion.exportar');
        $proyectoId = (int)$this->input->get('proyecto_id');
        if (!$proyectoId) show_error('Proyecto requerido', 400);
        $r = $this->svc->desviacion($proyectoId);

        $headers = ['CC','CC nombre','Tipo gasto','Descripción','Presupuestado CLP','Real CLP','Desviación CLP','Desviación %'];
        $body = [];
        foreach ($r['lineas'] as $l) {
            $body[] = [
                $l['cc_codigo'], $l['cc_nombre'], $l['tg_codigo'], $l['descripcion'],
                $l['presupuestado'], $l['real_clp'], $l['desv'], $l['desv_pct'],
            ];
        }
        // Totales
        $body[] = ['','','','TOTAL', $r['totales']['presupuestado'], $r['totales']['real'], $r['totales']['desv'], $r['totales']['desv_pct']];

        $this->audit->log('reporte.desviacion.exportar', null, null, null, ['proyecto_id' => $proyectoId]);
        $filename = 'desviacion_' . $proyectoId . '_' . date('Ymd_His') . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header("Content-Disposition: attachment; filename=\"{$filename}\"");
        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, $headers);
        foreach ($body as $row) fputcsv($out, $row);
        fclose($out);
        exit;
    }
}
