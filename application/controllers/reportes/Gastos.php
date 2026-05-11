<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'services/ReporteService.php';

class Gastos extends MY_AuthController
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
        $this->require_permission('reportes.gastos.ver');
        $filters = [
            'proyecto_id' => $this->input->get('proyecto_id'),
            'desde'       => $this->input->get('desde'),
            'hasta'       => $this->input->get('hasta'),
        ];
        $r = $this->svc->gastosPorObra($filters);
        $this->view('reportes/gastos/index', [
            'rows'         => $r['rows'],
            'total_global' => $r['total_global'],
            'filters'      => $filters,
            'proyectos'    => $this->ProyectoRepo->activos(),
        ]);
    }

    public function exportar()
    {
        $this->require_permission('reportes.gastos.exportar');
        $filters = [
            'proyecto_id' => $this->input->get('proyecto_id'),
            'desde'       => $this->input->get('desde'),
            'hasta'       => $this->input->get('hasta'),
        ];
        $r = $this->svc->gastosPorObra($filters);
        $headers = ['Proyecto','Nombre proyecto','CC','CC nombre','Tipo gasto','Origen','Documentos','Total CLP'];
        $body = [];
        foreach ($r['rows'] as $row) {
            $body[] = [
                $row['proyecto_codigo'] ?? '', $row['proyecto_nombre'] ?? '',
                $row['cc_codigo'] ?? '', $row['cc_nombre'] ?? '',
                $row['tg_codigo'] ?? '', $row['origen'],
                $row['docs'], $row['total_clp'],
            ];
        }
        $this->audit->log('reporte.gastos.exportar', null, null, null, ['filtros' => $filters, 'filas' => count($body)]);
        $filename = 'gastos_' . date('Ymd_His') . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header("Content-Disposition: attachment; filename=\"{$filename}\"");
        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, $headers);
        foreach ($body as $r2) fputcsv($out, $r2);
        fclose($out);
        exit;
    }
}
