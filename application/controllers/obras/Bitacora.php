<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'services/BitacoraService.php';

class Bitacora extends MY_AuthController
{
    protected $svc;

    public function __construct()
    {
        parent::__construct();
        $this->load->model(['BitacoraRepo','ProyectoRepo']);
        $this->load->library('Uploader');
        $this->svc = new BitacoraService();
    }

    public function index()
    {
        $this->require_permission('obras.bitacora.ver');
        $proyectoId = (int)$this->input->get('proyecto_id');
        $proyectos  = $this->ProyectoRepo->activos();

        $proyecto = null;
        $rows = [];
        if ($proyectoId) {
            $proyecto = $this->ProyectoRepo->findFull($proyectoId);
            if (!$proyecto) show_404();
            $filters = [
                'q'     => trim((string)$this->input->get('q')),
                'tipo'  => $this->input->get('tipo'),
                'desde' => $this->input->get('desde'),
                'hasta' => $this->input->get('hasta'),
            ];
            $rows = $this->BitacoraRepo->listByProyecto($proyectoId, $filters);
        }

        $this->view('obras/bitacora/index', [
            'proyecto'  => $proyecto,
            'proyectos' => $proyectos,
            'rows'      => $rows,
            'filters'   => [
                'q'     => $this->input->get('q'),
                'tipo'  => $this->input->get('tipo'),
                'desde' => $this->input->get('desde'),
                'hasta' => $this->input->get('hasta'),
            ],
        ]);
    }

    public function ver(int $id)
    {
        $this->require_permission('obras.bitacora.ver');
        $b = $this->BitacoraRepo->findFull($id);
        if (!$b) show_404();
        $adjuntos = $this->uploader->listFor('bitacora', $id);
        $puedeEditar = can('obras.bitacora.editar') && $this->svc->puedeEditar($b, $this->user_id());
        $this->view('obras/bitacora/ver', [
            'b' => $b, 'adjuntos' => $adjuntos, 'puede_editar' => $puedeEditar,
        ]);
    }

    public function crear()
    {
        $this->require_permission('obras.bitacora.crear');
        $proyectoId = (int)$this->input->get('proyecto_id');
        if (!$proyectoId && $this->input->method() === 'post') {
            $proyectoId = (int)$this->input->post('proyecto_id');
        }

        if ($this->input->method() !== 'post') {
            return $this->view('obras/bitacora/form', [
                'b'         => null,
                'is_edit'   => false,
                'proyecto_id' => $proyectoId,
                'proyectos' => $this->ProyectoRepo->activos(),
            ]);
        }
        try {
            $id = $this->svc->crear($this->input->post(), $this->user_id());
            if (!empty($_FILES['archivo']['name'])) {
                $this->uploader->store($_FILES['archivo'], 'bitacora', $id, 'evidencia');
            }
            $this->flash('success', 'Entrada de bitácora creada.');
            redirect(base_url("obras/bitacora/{$id}"));
        } catch (\Throwable $e) {
            $this->flash('error', $e->getMessage());
            redirect(base_url('obras/bitacora/crear?proyecto_id=' . $proyectoId));
        }
    }

    public function editar(int $id)
    {
        $this->require_permission('obras.bitacora.editar');
        $b = $this->BitacoraRepo->findFull($id);
        if (!$b) show_404();
        if (!$this->svc->puedeEditar($b, $this->user_id())) {
            $this->flash('error', 'No puedes editar esta entrada (regla de 24 hrs).');
            redirect(base_url("obras/bitacora/{$id}"));
        }
        if ($this->input->method() !== 'post') {
            return $this->view('obras/bitacora/form', [
                'b'         => $b,
                'is_edit'   => true,
                'proyecto_id'=> (int)$b['proyecto_id'],
                'proyectos' => $this->ProyectoRepo->activos(),
            ]);
        }
        try {
            $this->svc->editar($id, $this->input->post(), $this->user_id());
            $this->flash('success', 'Bitácora actualizada.');
            redirect(base_url("obras/bitacora/{$id}"));
        } catch (\Throwable $e) {
            $this->flash('error', $e->getMessage());
            redirect(base_url("obras/bitacora/{$id}/editar"));
        }
    }
}
