<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'services/RindeService.php';

class Rindes extends MY_AuthController
{
    /** @var RindeService */
    protected $svc;

    public function __construct()
    {
        parent::__construct();
        $this->load->model([
            'RindeRepo','RindeItemRepo',
            'ProyectoRepo','ProveedorRepo','TipoGastoRepo',
            'CentroCostoRepo','MonedaRepo',
        ]);
        $this->load->library('Uploader');
        $this->svc = new RindeService();
    }

    public function index()
    {
        $this->require_permission('compras.rinde.ver');
        $page = max(1, (int)$this->input->get('page'));
        $limit = 50;
        $offset = ($page - 1) * $limit;

        $filters = [
            'q'           => trim((string)$this->input->get('q')),
            'proyecto_id' => $this->input->get('proyecto_id'),
            'estado_id'   => $this->input->get('estado_id'),
            'usuario_id'  => $this->input->get('usuario_id'),
            'desde'       => $this->input->get('desde'),
            'hasta'       => $this->input->get('hasta'),
        ];
        $r = $this->RindeRepo->bandeja($filters, $limit, $offset);
        $this->view('compras/rindes/index', [
            'rows'       => $r['rows'],
            'total'      => $r['total'],
            'page'       => $page,
            'totalPages' => (int)ceil($r['total'] / $limit),
            'filters'    => $filters,
            'proyectos'  => $this->ProyectoRepo->activos(),
            'estados'    => $this->db->where('dominio','rinde')->order_by('orden')->get('gmc_estados')->result_array(),
        ]);
    }

    public function ver(int $id)
    {
        $this->require_permission('compras.rinde.ver');
        $rinde = $this->RindeRepo->findFull($id);
        if (!$rinde) show_404();
        $items    = $this->RindeItemRepo->listByRinde($id);
        $adjuntos = $this->uploader->listFor('rinde', $id);
        $puedeAprobar = ($rinde['estado_codigo'] === 'enviada')
            && $this->svc->puedeAprobar($rinde, $this->user_id());

        $this->view('compras/rindes/ver', [
            'rinde'    => $rinde,
            'items'    => $items,
            'adjuntos' => $adjuntos,
            'puede_aprobar' => $puedeAprobar,
        ]);
    }

    public function crear()
    {
        $this->require_permission('compras.rinde.crear');
        if ($this->input->method() !== 'post') {
            return $this->_renderForm(null);
        }
        try {
            $items = $this->_extraerItems();
            if (!$items) throw new RuntimeException('Agrega al menos un ítem.');
            $id = $this->svc->crear($this->input->post(), $items, $this->user_id());
            if (!empty($_FILES['archivo']['name'])) {
                $this->uploader->store($_FILES['archivo'], 'rinde', $id, 'comprobante');
            }
            $this->flash('success', 'Rinde creado en Borrador.');
            redirect(base_url("compras/rindes/{$id}"));
        } catch (\Throwable $e) {
            log_message('error', 'Rinde crear: ' . $e->getMessage());
            $this->flash('error', $e->getMessage());
            redirect(base_url('compras/rindes/crear'));
        }
    }

    public function editar(int $id)
    {
        $this->require_permission('compras.rinde.editar');
        $rinde = $this->RindeRepo->findFull($id);
        if (!$rinde) show_404();
        if ($rinde['estado_codigo'] !== 'borrador') {
            $this->flash('error', 'Sólo se puede editar un rinde en Borrador.');
            redirect(base_url("compras/rindes/{$id}"));
        }
        if ($this->input->method() !== 'post') {
            return $this->_renderForm($rinde);
        }
        try {
            $items = $this->_extraerItems();
            if (!$items) throw new RuntimeException('Agrega al menos un ítem.');
            $this->svc->editar($id, $this->input->post(), $items, $this->user_id());
            $this->flash('success', 'Rinde actualizado.');
            redirect(base_url("compras/rindes/{$id}"));
        } catch (\Throwable $e) {
            $this->flash('error', $e->getMessage());
            redirect(base_url("compras/rindes/{$id}/editar"));
        }
    }

    public function enviar(int $id)
    {
        $this->require_permission('compras.rinde.enviar');
        try { $this->svc->enviar($id, $this->user_id()); $this->flash('success', 'Rinde enviado a aprobación.'); }
        catch (\Throwable $e) { $this->flash('error', $e->getMessage()); }
        redirect(base_url("compras/rindes/{$id}"));
    }

    public function aprobar(int $id)
    {
        $this->require_permission('compras.rinde.aprobar');
        try { $this->svc->aprobar($id, $this->user_id()); $this->flash('success', 'Rinde aprobado.'); }
        catch (\Throwable $e) { $this->flash('error', $e->getMessage()); }
        redirect(base_url("compras/rindes/{$id}"));
    }

    public function rechazar(int $id)
    {
        $this->require_permission('compras.rinde.rechazar');
        if ($this->input->method() !== 'post') {
            $rinde = $this->RindeRepo->findFull($id);
            if (!$rinde) show_404();
            return $this->view('compras/rindes/rechazar', ['rinde' => $rinde]);
        }
        $motivo = (string)$this->input->post('motivo');
        try { $this->svc->rechazar($id, $this->user_id(), $motivo); $this->flash('success', 'Rinde rechazado.'); }
        catch (\Throwable $e) { $this->flash('error', $e->getMessage()); }
        redirect(base_url("compras/rindes/{$id}"));
    }

    public function generar_sdp(int $id)
    {
        $this->require_permission('compras.rinde.aprobar');
        if ($this->input->method() !== 'post') {
            $rinde = $this->RindeRepo->findFull($id);
            if (!$rinde) show_404();
            return $this->view('compras/rindes/generar_sdp', [
                'rinde'       => $rinde,
                'proveedores' => $this->ProveedorRepo->activos(),
                'tipos_gasto' => $this->TipoGastoRepo->activos(),
            ]);
        }
        $provId = (int)$this->input->post('proveedor_id');
        $tgId   = (int)$this->input->post('tipo_gasto_id');
        try {
            $sdpId = $this->svc->generarSdpDePago($id, $provId, $tgId, $this->user_id());
            $this->flash('success', 'SDP generada y vinculada al rinde.');
            redirect(base_url("finanzas/sdp/{$sdpId}"));
        } catch (\Throwable $e) {
            $this->flash('error', $e->getMessage());
            redirect(base_url("compras/rindes/{$id}"));
        }
    }

    // ---------------- helpers ----------------

    private function _extraerItems(): array
    {
        $raw = $this->input->post('items') ?: [];
        $items = [];
        foreach ($raw as $r) {
            if (empty($r['descripcion']) || (float)($r['monto'] ?? 0) <= 0) continue;
            $items[] = $r;
        }
        return $items;
    }

    private function _renderForm(?array $rinde): void
    {
        $proyId = $rinde ? ($rinde['proyecto_id'] ? (int)$rinde['proyecto_id'] : null) : null;
        $ccs = array_merge(
            $proyId ? $this->CentroCostoRepo->listByProyecto($proyId) : [],
            $this->CentroCostoRepo->listByProyecto(null)
        );
        $items = $rinde ? $this->RindeItemRepo->listByRinde((int)$rinde['id']) : [];
        $this->view('compras/rindes/form', [
            'rinde'       => $rinde,
            'is_edit'     => $rinde !== null,
            'items'       => $items,
            'proyectos'   => $this->ProyectoRepo->activos(),
            'tipos_gasto' => $this->TipoGastoRepo->activos(),
            'monedas'     => $this->MonedaRepo->activas(),
            'centros'     => $ccs,
        ]);
    }
}
