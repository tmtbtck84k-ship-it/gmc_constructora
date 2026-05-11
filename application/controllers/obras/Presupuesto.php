<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'services/PresupuestoService.php';

class Presupuesto extends MY_AuthController
{
    protected $svc;

    public function __construct()
    {
        parent::__construct();
        $this->load->model([
            'PresupuestoObraRepo','PresupuestoObraItemRepo',
            'ProyectoRepo','CentroCostoRepo','TipoGastoRepo','MonedaRepo',
        ]);
        $this->svc = new PresupuestoService();
    }

    public function index()
    {
        $this->require_permission('obras.presupuesto.ver');
        $proyectoId = (int)$this->input->get('proyecto_id');
        $proyectos  = $this->ProyectoRepo->activos();
        $proyecto = null;
        $versiones = [];
        if ($proyectoId) {
            $proyecto = $this->ProyectoRepo->findFull($proyectoId);
            if (!$proyecto) show_404();
            $versiones = $this->PresupuestoObraRepo->listByProyecto($proyectoId);
        }
        $this->view('obras/presupuesto/index', [
            'proyecto' => $proyecto, 'proyectos' => $proyectos, 'versiones' => $versiones,
        ]);
    }

    public function ver(int $id)
    {
        $this->require_permission('obras.presupuesto.ver');
        $p = $this->PresupuestoObraRepo->findFull($id);
        if (!$p) show_404();
        $items = $this->PresupuestoObraItemRepo->listByPresupuesto($id);
        $this->view('obras/presupuesto/ver', ['p' => $p, 'items' => $items]);
    }

    public function crear()
    {
        $this->require_permission('obras.presupuesto.editar');
        $proyectoId = (int)$this->input->get('proyecto_id');
        if (!$proyectoId && $this->input->method() === 'post') {
            $proyectoId = (int)$this->input->post('proyecto_id');
        }
        if (!$proyectoId) show_404();

        $proyecto = $this->ProyectoRepo->findFull($proyectoId);
        if (!$proyecto) show_404();

        if ($this->input->method() !== 'post') {
            return $this->_renderForm($proyecto, null);
        }
        try {
            $items = $this->_extraerItems();
            if (!$items) throw new RuntimeException('Agrega al menos un ítem al presupuesto.');
            $id = $this->svc->crear($proyectoId, $this->input->post(), $items, $this->user_id());
            $this->flash('success', 'Presupuesto creado y marcado como vigente.');
            redirect(base_url("obras/presupuesto/{$id}"));
        } catch (\Throwable $e) {
            $this->flash('error', $e->getMessage());
            redirect(base_url("obras/presupuesto/crear?proyecto_id={$proyectoId}"));
        }
    }

    public function editar(int $id)
    {
        $this->require_permission('obras.presupuesto.editar');
        $p = $this->PresupuestoObraRepo->findFull($id);
        if (!$p) show_404();
        $proyecto = $this->ProyectoRepo->findFull((int)$p['proyecto_id']);
        if ($this->input->method() !== 'post') {
            return $this->_renderForm($proyecto, $p);
        }
        try {
            $items = $this->_extraerItems();
            if (!$items) throw new RuntimeException('Agrega al menos un ítem.');
            $this->svc->editar($id, $this->input->post(), $items, $this->user_id());
            $this->flash('success', 'Presupuesto actualizado.');
            redirect(base_url("obras/presupuesto/{$id}"));
        } catch (\Throwable $e) {
            $this->flash('error', $e->getMessage());
            redirect(base_url("obras/presupuesto/{$id}/editar"));
        }
    }

    public function nueva_version(int $proyectoId)
    {
        $this->require_permission('obras.presupuesto.editar');
        try {
            $newId = $this->svc->nuevaVersion($proyectoId, $this->user_id());
            $this->flash('success', 'Nueva versión creada (vigente). Edita los items según corresponda.');
            redirect(base_url("obras/presupuesto/{$newId}/editar"));
        } catch (\Throwable $e) {
            $this->flash('error', $e->getMessage());
            redirect(base_url("obras/presupuesto?proyecto_id={$proyectoId}"));
        }
    }

    private function _extraerItems(): array
    {
        $raw = $this->input->post('items') ?: [];
        $items = [];
        foreach ($raw as $r) {
            if (empty($r['descripcion']) || (float)($r['monto'] ?? 0) <= 0) continue;
            if (empty($r['centro_costo_id']) || empty($r['tipo_gasto_id'])) continue;
            $items[] = $r;
        }
        return $items;
    }

    private function _renderForm(array $proyecto, ?array $p): void
    {
        $items = $p ? $this->PresupuestoObraItemRepo->listByPresupuesto((int)$p['id']) : [];
        $ccs = array_merge(
            $this->CentroCostoRepo->listByProyecto((int)$proyecto['id']),
            $this->CentroCostoRepo->listByProyecto(null)
        );
        $this->view('obras/presupuesto/form', [
            'proyecto'    => $proyecto,
            'p'           => $p,
            'is_edit'     => $p !== null,
            'items'       => $items,
            'tipos_gasto' => $this->TipoGastoRepo->activos(),
            'centros'     => $ccs,
            'monedas'     => $this->MonedaRepo->activas(),
        ]);
    }
}
