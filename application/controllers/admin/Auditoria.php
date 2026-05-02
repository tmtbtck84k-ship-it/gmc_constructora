<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Auditoria extends MY_AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('AuditoriaRepo');
    }

    public function index()
    {
        $this->require_permission('audit.log.ver');

        $page  = max(1, (int) $this->input->get('page'));
        $limit = 50;
        $offset = ($page - 1) * $limit;

        $filters = [
            'usuario_id' => $this->input->get('usuario_id'),
            'accion'     => $this->input->get('accion'),
            'entidad'    => $this->input->get('entidad'),
            'desde'      => $this->input->get('desde'),
            'hasta'      => $this->input->get('hasta'),
        ];

        $result = $this->AuditoriaRepo->search($filters, $limit, $offset);
        $totalPages = (int) ceil($result['total'] / $limit);

        $this->view('admin/auditoria/index', [
            'rows'       => $result['rows'],
            'total'      => $result['total'],
            'page'       => $page,
            'totalPages' => $totalPages,
            'filters'    => $filters,
        ]);
    }

    public function exportar()
    {
        $this->require_permission('audit.log.ver');

        $filters = [
            'usuario_id' => $this->input->get('usuario_id'),
            'accion'     => $this->input->get('accion'),
            'entidad'    => $this->input->get('entidad'),
            'desde'      => $this->input->get('desde'),
            'hasta'      => $this->input->get('hasta'),
        ];
        $result = $this->AuditoriaRepo->search($filters, 50000, 0);

        $filename = 'auditoria_' . date('Ymd_His') . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header("Content-Disposition: attachment; filename=\"{$filename}\"");

        $out = fopen('php://output', 'w');
        // BOM UTF-8 para Excel
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, ['ID','Fecha','Usuario','Email','Acción','Entidad','EntidadID','IP','User-Agent']);
        foreach ($result['rows'] as $r) {
            fputcsv($out, [
                $r['id'], $r['created_at'],
                trim(($r['nombres'] ?? '') . ' ' . ($r['apellidos'] ?? '')),
                $r['usuario_email'] ?? '',
                $r['accion'], $r['entidad'] ?? '', $r['entidad_id'] ?? '',
                $r['ip'] ?? '', $r['user_agent'] ?? '',
            ]);
        }
        fclose($out);
        exit;
    }
}
