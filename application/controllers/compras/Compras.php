<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'services/CompraService.php';

class Compras extends MY_AuthController
{
    /** @var CompraService */
    protected $svc;

    public function __construct()
    {
        parent::__construct();
        $this->load->model([
            'CompraRepo','CompraItemRepo',
            'ProyectoRepo','ProveedorRepo','TipoGastoRepo',
            'CentroCostoRepo','MonedaRepo',
            'SolicitudPagoRepo','RindeRepo',
        ]);
        $this->load->library('Uploader');
        $this->svc = new CompraService();
    }

    public function index()
    {
        $this->require_permission('compras.compra.ver');
        $page = max(1, (int)$this->input->get('page'));
        $limit = 50;
        $offset = ($page - 1) * $limit;

        $filters = [
            'q'             => trim((string)$this->input->get('q')),
            'proyecto_id'   => $this->input->get('proyecto_id'),
            'proveedor_id'  => $this->input->get('proveedor_id'),
            'estado_id'     => $this->input->get('estado_id'),
            'desde'         => $this->input->get('desde'),
            'hasta'         => $this->input->get('hasta'),
        ];
        $r = $this->CompraRepo->bandeja($filters, $limit, $offset);
        $this->view('compras/compras/index', [
            'rows'       => $r['rows'],
            'total'      => $r['total'],
            'page'       => $page,
            'totalPages' => (int)ceil($r['total'] / $limit),
            'filters'    => $filters,
            'proyectos'  => $this->ProyectoRepo->activos(),
            'proveedores'=> $this->ProveedorRepo->activos(),
            'estados'    => $this->db->where('dominio','compra')->order_by('orden')->get('gmc_estados')->result_array(),
        ]);
    }

    public function ver(int $id)
    {
        $this->require_permission('compras.compra.ver');
        $compra = $this->CompraRepo->findFull($id);
        if (!$compra) show_404();
        $items    = $this->CompraItemRepo->listByCompra($id);
        $adjuntos = $this->uploader->listFor('compra', $id);
        $this->view('compras/compras/ver', [
            'compra'  => $compra, 'items' => $items, 'adjuntos' => $adjuntos,
        ]);
    }

    public function crear()
    {
        $this->require_permission('compras.compra.crear');
        if ($this->input->method() !== 'post') {
            return $this->_renderForm(null);
        }
        try {
            $items = $this->_extraerItems();
            if (!$items) throw new RuntimeException('Agrega al menos un ítem.');

            $id = $this->svc->crear($this->input->post(), $items, $this->user_id());

            if (!empty($_FILES['archivo']['name'])) {
                $this->uploader->store($_FILES['archivo'], 'compra', $id, 'factura');
            }
            $this->flash('success', 'Compra creada en estado Borrador.');
            redirect(base_url("compras/compras/{$id}"));
        } catch (\Throwable $e) {
            log_message('error', 'Compra crear: ' . $e->getMessage());
            $this->flash('error', $e->getMessage());
            redirect(base_url('compras/compras/crear'));
        }
    }

    public function editar(int $id)
    {
        $this->require_permission('compras.compra.editar');
        $compra = $this->CompraRepo->findFull($id);
        if (!$compra) show_404();
        if ($compra['estado_codigo'] !== 'borrador') {
            $this->flash('error', 'Sólo se puede editar una compra en Borrador.');
            redirect(base_url("compras/compras/{$id}"));
        }
        if ($this->input->method() !== 'post') {
            return $this->_renderForm($compra);
        }
        try {
            $items = $this->_extraerItems();
            if (!$items) throw new RuntimeException('Agrega al menos un ítem.');
            $this->svc->editar($id, $this->input->post(), $items, $this->user_id());
            $this->flash('success', 'Compra actualizada.');
            redirect(base_url("compras/compras/{$id}"));
        } catch (\Throwable $e) {
            log_message('error', 'Compra editar: ' . $e->getMessage());
            $this->flash('error', $e->getMessage());
            redirect(base_url("compras/compras/{$id}/editar"));
        }
    }

    public function confirmar(int $id)
    {
        $this->require_permission('compras.compra.editar');
        try { $this->svc->confirmar($id, $this->user_id()); $this->flash('success', 'Compra recibida.'); }
        catch (\Throwable $e) { $this->flash('error', $e->getMessage()); }
        redirect(base_url("compras/compras/{$id}"));
    }

    public function anular(int $id)
    {
        $this->require_permission('compras.compra.anular');
        if ($this->input->method() !== 'post') {
            $compra = $this->CompraRepo->findFull($id);
            if (!$compra) show_404();
            return $this->view('compras/compras/anular', ['compra' => $compra]);
        }
        $motivo = (string)$this->input->post('motivo');
        try { $this->svc->anular($id, $this->user_id(), $motivo); $this->flash('success', 'Compra anulada.'); }
        catch (\Throwable $e) { $this->flash('error', $e->getMessage()); }
        redirect(base_url("compras/compras/{$id}"));
    }

    // ---------------- helpers ----------------

    private function _extraerItems(): array
    {
        // El form envía items[N][campo]
        $raw = $this->input->post('items') ?: [];
        $items = [];
        foreach ($raw as $r) {
            if (empty($r['descripcion'])) continue;
            $items[] = $r;
        }
        return $items;
    }

    private function _renderForm(?array $compra): void
    {
        $proyId = $compra ? ($compra['proyecto_id'] ? (int)$compra['proyecto_id'] : null) : null;
        $ccs = array_merge(
            $proyId ? $this->CentroCostoRepo->listByProyecto($proyId) : [],
            $this->CentroCostoRepo->listByProyecto(null)
        );
        $items = $compra ? $this->CompraItemRepo->listByCompra((int)$compra['id']) : [];
        // SDPs y Rindes para vinculación opcional (filtrar por proyecto si hay)
        $sdps  = $this->db->select('id, numero, fecha_emision, monto_total')
                          ->where('deleted_at IS NULL', null, false)
                          ->order_by('id','DESC')->limit(200)->get('gmc_solicitudes_pago')->result_array();
        $rindes = $this->db->select('id, numero, fecha_rendicion, monto_total')
                           ->where('deleted_at IS NULL', null, false)
                           ->order_by('id','DESC')->limit(200)->get('gmc_rindes_gastos')->result_array();

        $this->view('compras/compras/form', [
            'compra'      => $compra,
            'is_edit'     => $compra !== null,
            'items'       => $items,
            'proyectos'   => $this->ProyectoRepo->activos(),
            'proveedores' => $this->ProveedorRepo->activos(),
            'tipos_gasto' => $this->TipoGastoRepo->activos(),
            'monedas'     => $this->MonedaRepo->activas(),
            'centros'     => $ccs,
            'sdps'        => $sdps,
            'rindes'      => $rindes,
        ]);
    }
}
