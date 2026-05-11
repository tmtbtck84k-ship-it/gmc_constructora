<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'services/CierreObraService.php';

class Cierre extends MY_AuthController
{
    protected $svc;

    public function __construct()
    {
        parent::__construct();
        $this->load->model([
            'CierreObraRepo','ProyectoRepo','BitacoraRepo',
            'PresupuestoObraRepo','PresupuestoObraItemRepo',
        ]);
        $this->load->library(['Uploader','Pdf']);
        $this->svc = new CierreObraService();
    }

    public function index()
    {
        $this->require_permission('obras.cierre.ver');
        $rows = $this->CierreObraRepo->listAll();
        $this->view('obras/cierre/index', ['rows' => $rows]);
    }

    public function ver(int $proyectoId)
    {
        $this->require_permission('obras.cierre.ver');
        $proyecto = $this->ProyectoRepo->findFull($proyectoId);
        if (!$proyecto) show_404();
        $cierre = $this->CierreObraRepo->findFullByProyecto($proyectoId);
        $adjuntos = $cierre ? $this->uploader->listFor('cierre', (int)$cierre['id']) : [];
        $this->view('obras/cierre/ver', [
            'proyecto' => $proyecto, 'cierre' => $cierre, 'adjuntos' => $adjuntos,
        ]);
    }

    public function editar(int $proyectoId)
    {
        $this->require_permission('obras.cierre.crear');
        $proyecto = $this->ProyectoRepo->findFull($proyectoId);
        if (!$proyecto) show_404();
        $cierre = $this->CierreObraRepo->findFullByProyecto($proyectoId);

        if ($this->input->method() !== 'post') {
            return $this->view('obras/cierre/form', [
                'proyecto' => $proyecto,
                'cierre'   => $cierre,
            ]);
        }
        try {
            $cierreId = $this->svc->crearOEditar($proyectoId, $this->input->post(), $this->user_id());
            if (!empty($_FILES['archivo']['name'])) {
                $this->uploader->store($_FILES['archivo'], 'cierre', $cierreId, 'cierre');
            }
            $this->flash('success', 'Borrador de cierre guardado.');
            redirect(base_url("obras/cierre/{$proyectoId}"));
        } catch (\Throwable $e) {
            $this->flash('error', $e->getMessage());
            redirect(base_url("obras/cierre/{$proyectoId}/editar"));
        }
    }

    public function cerrar(int $proyectoId)
    {
        $this->require_permission('obras.cierre.cerrar');
        try {
            $this->svc->cerrar($proyectoId, $this->user_id());
            $this->flash('success', 'Obra cerrada formalmente.');
        } catch (\Throwable $e) {
            $this->flash('error', $e->getMessage());
        }
        redirect(base_url("obras/cierre/{$proyectoId}"));
    }

    public function pdf(int $proyectoId)
    {
        $this->require_permission('obras.cierre.ver');
        $proyecto = $this->ProyectoRepo->findFull($proyectoId);
        $cierre   = $this->CierreObraRepo->findFullByProyecto($proyectoId);
        if (!$proyecto || !$cierre) show_404();

        $bitacoras = $this->BitacoraRepo->listByProyecto($proyectoId, []);
        $presupuesto = $this->PresupuestoObraRepo->vigentePorProyecto($proyectoId);
        $presupuestoItems = $presupuesto
            ? $this->PresupuestoObraItemRepo->listByPresupuesto((int)$presupuesto['id'])
            : [];

        $html = $this->load->view('obras/cierre/pdf', [
            'proyecto'   => $proyecto,
            'cierre'     => $cierre,
            'bitacoras'  => $bitacoras,
            'presupuesto'=> $presupuesto,
            'pres_items' => $presupuestoItems,
            'company'    => $this->config->item('app_company_name'),
        ], true);

        $filename = 'cierre_' . $proyecto['codigo'] . '.pdf';
        $this->audit->log('cierre.pdf', 'gmc_cierres_obra', (int)$cierre['id'], null, ['proyecto_id' => $proyectoId]);
        $this->pdf->renderHtml($html, $filename, 'D');
    }
}
