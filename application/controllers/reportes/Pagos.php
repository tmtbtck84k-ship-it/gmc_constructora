<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'services/ReporteService.php';

class Pagos extends MY_AuthController
{
    protected $svc;

    public function __construct()
    {
        parent::__construct();
        $this->load->model(['ProyectoRepo','ProveedorRepo']);
        $this->svc = new ReporteService();
    }

    public function index()
    {
        $this->require_permission('reportes.pagos.ver');
        $filters = $this->_filters();
        $r = $this->svc->estadoPagos($filters);
        $this->view('reportes/pagos/index', [
            'rows'    => $r['rows'],
            'totales' => $r['totales'],
            'filters' => $filters,
            'proyectos'   => $this->ProyectoRepo->activos(),
            'proveedores' => $this->ProveedorRepo->activos(),
            'estados' => $this->db->where('dominio','solicitud_pago')->order_by('orden')->get('gmc_estados')->result_array(),
        ]);
    }

    public function exportar()
    {
        $this->require_permission('reportes.pagos.exportar');
        $filters = $this->_filters();
        $r = $this->svc->estadoPagos($filters);

        $headers = ['Nº SDP','Fecha emisión','Fecha programada','Fecha pago','Proyecto','CC','Proveedor','Tipo gasto',
                    'Moneda','Monto','TC','Monto CLP','Estado','Forma pago'];
        $body = [];
        foreach ($r['rows'] as $row) {
            $body[] = [
                $row['numero'], $row['fecha_emision'], $row['fecha_programada'], $row['fecha_pago'],
                $row['proyecto_codigo'] ?? '', $row['cc_codigo'] ?? '',
                $row['proveedor'] ?? '', $row['tg_codigo'] ?? '',
                $row['moneda'], $row['monto_total'], $row['tipo_cambio_clp'] ?? '', $row['monto_total_clp'] ?? '',
                $row['estado_nombre'], $row['forma_pago'] ?? '',
            ];
        }
        $this->audit->log('reporte.pagos.exportar', null, null, null, ['filtros' => $filters, 'filas' => count($body)]);
        $this->_exportCsv('estado_pagos', $headers, $body);
    }

    private function _filters(): array
    {
        return [
            'proyecto_id'  => $this->input->get('proyecto_id'),
            'proveedor_id' => $this->input->get('proveedor_id'),
            'estado_id'    => $this->input->get('estado_id'),
            'desde'        => $this->input->get('desde'),
            'hasta'        => $this->input->get('hasta'),
        ];
    }

    private function _exportCsv(string $base, array $headers, array $body): void
    {
        $filename = $base . '_' . date('Ymd_His') . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header("Content-Disposition: attachment; filename=\"{$filename}\"");
        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, $headers);
        foreach ($body as $r) fputcsv($out, $r);
        fclose($out);
        exit;
    }
}
