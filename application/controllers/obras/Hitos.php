<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'services/HitoService.php';

class Hitos extends MY_AuthController
{
    /** @var HitoService */
    protected $svc;

    public function __construct()
    {
        parent::__construct();
        $this->load->model(['HitoRepo','ProyectoRepo','ActividadRepo']);
        $this->svc = new HitoService();
    }

    /** Listado de hitos del proyecto. */
    public function index()
    {
        $this->require_permission('obras.gantt.ver');
        $proyectoId = (int)$this->input->get('proyecto_id');
        $proyectos  = $this->ProyectoRepo->activos();

        $proyecto = null; $rows = [];
        if ($proyectoId) {
            $proyecto = $this->ProyectoRepo->findFull($proyectoId);
            if (!$proyecto) show_404();
            $rows = $this->HitoRepo->listByProyecto($proyectoId);
        }
        $this->view('obras/hitos/index', [
            'proyecto'  => $proyecto,
            'proyectos' => $proyectos,
            'rows'      => $rows,
        ]);
    }

    public function nuevo()
    {
        $this->require_permission('obras.gantt.editar');
        $proyectoId = (int)$this->input->get('proyecto_id');
        $proyecto = $this->ProyectoRepo->findFull($proyectoId);
        if (!$proyecto) show_404();
        $this->_assertEditable($proyecto);
        $this->view('obras/hitos/form', [
            'proyecto' => $proyecto, 'h' => null,
        ]);
    }

    public function crear()
    {
        $this->require_permission('obras.gantt.editar');
        $proyectoId = (int)$this->input->post('proyecto_id');
        $proyecto = $this->ProyectoRepo->findFull($proyectoId);
        if (!$proyecto) show_404();
        $this->_assertEditable($proyecto);

        try {
            $id = $this->svc->crear($this->input->post(), $this->user_id());
            $this->flash('success','Hito creado.');
            redirect('obras/hitos?proyecto_id=' . $proyectoId);
        } catch (Throwable $e) {
            $this->flash('error',$e->getMessage());
            redirect('obras/hitos/nuevo?proyecto_id=' . $proyectoId);
        }
    }

    public function editar(int $id)
    {
        $this->require_permission('obras.gantt.editar');
        $h = $this->HitoRepo->find($id);
        if (!$h) show_404();
        $proyecto = $this->ProyectoRepo->findFull((int)$h['proyecto_id']);
        $this->_assertEditable($proyecto);
        $this->view('obras/hitos/form', [
            'proyecto' => $proyecto, 'h' => $h,
        ]);
    }

    public function actualizar(int $id)
    {
        $this->require_permission('obras.gantt.editar');
        $h = $this->HitoRepo->find($id);
        if (!$h) show_404();
        $proyecto = $this->ProyectoRepo->findFull((int)$h['proyecto_id']);
        $this->_assertEditable($proyecto);

        try {
            $this->svc->editar($id, $this->input->post(), $this->user_id());
            $this->flash('success','Hito actualizado.');
            redirect('obras/hitos?proyecto_id=' . $h['proyecto_id']);
        } catch (Throwable $e) {
            $this->flash('error',$e->getMessage());
            redirect('obras/hitos/editar/' . $id);
        }
    }

    public function eliminar(int $id)
    {
        $this->require_permission('obras.gantt.editar');
        $h = $this->HitoRepo->find($id);
        if (!$h) show_404();
        $proyecto = $this->ProyectoRepo->findFull((int)$h['proyecto_id']);
        $this->_assertEditable($proyecto);
        try {
            $this->svc->eliminar($id, $this->user_id());
            $this->flash('success','Hito eliminado.');
        } catch (Throwable $e) {
            $this->flash('error',$e->getMessage());
        }
        redirect('obras/hitos?proyecto_id=' . $h['proyecto_id']);
    }

    private function _assertEditable(array $proyecto): void
    {
        if (in_array($proyecto['estado'] ?? '', ['cerrado','cancelado'], true)) {
            $this->flash('error','El proyecto está cerrado: el Gantt es de sólo lectura.');
            redirect('obras/hitos?proyecto_id=' . $proyecto['id']);
        }
    }
}
