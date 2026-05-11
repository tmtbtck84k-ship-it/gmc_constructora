<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'services/ProyectoService.php';

class Proyectos extends MY_AuthController
{
    /** @var ProyectoService */
    protected $proyectoSvc;

    public function __construct()
    {
        parent::__construct();
        $this->load->model(['ProyectoRepo','ClienteRepo','ComunaRepo','CentroCostoRepo']);
        $this->proyectoSvc = new ProyectoService();
    }

    public function index()
    {
        $this->require_permission('maestros.proyecto.ver');
        $filters = [
            'q'          => trim((string)$this->input->get('q')),
            'estado_id'  => $this->input->get('estado_id'),
        ];
        $rows = $this->ProyectoRepo->listAll($filters);
        $estados = $this->db->where('dominio','proyecto')->order_by('orden')->get('gmc_estados')->result_array();
        $this->view('maestros/proyectos/index', ['proyectos' => $rows, 'filters' => $filters, 'estados' => $estados]);
    }

    public function ver(int $id)
    {
        $this->require_permission('maestros.proyecto.ver');
        $p = $this->ProyectoRepo->findFull($id);
        if (!$p) show_404();
        $ccs = $this->CentroCostoRepo->listByProyecto($id);
        $this->view('maestros/proyectos/ver', ['proyecto' => $p, 'centros_costo' => $ccs]);
    }

    public function crear()
    {
        $this->require_permission('maestros.proyecto.crear');
        if ($this->input->method() !== 'post') {
            return $this->_renderForm(null);
        }

        $nombre = trim((string)$this->input->post('nombre'));
        $clienteId = (int)$this->input->post('cliente_id');
        $errors = [];
        if (strlen($nombre) < 3) $errors[] = 'Nombre del proyecto inválido.';
        if ($clienteId <= 0)     $errors[] = 'Cliente requerido.';
        if ($errors) {
            $this->flash('error', implode(' ', $errors));
            redirect(base_url('maestros/proyectos/crear'));
        }

        try {
            $id = $this->proyectoSvc->crear($this->input->post(), $this->user_id());
            $this->flash('success', 'Proyecto creado con código correlativo. Centro de costo "ADM-OBR" creado automáticamente.');
            redirect(base_url("maestros/proyectos/{$id}"));
        } catch (Throwable $e) {
            log_message('error', 'ProyectoService crear: ' . $e->getMessage());
            $this->flash('error', 'Error al crear: ' . $e->getMessage());
            redirect(base_url('maestros/proyectos/crear'));
        }
    }

    public function editar(int $id)
    {
        $this->require_permission('maestros.proyecto.editar');
        $p = $this->ProyectoRepo->find($id);
        if (!$p) show_404();
        if ($this->input->method() !== 'post') {
            return $this->_renderForm($p);
        }
        try {
            $this->proyectoSvc->actualizar($id, $this->input->post(), $this->user_id());
            $this->flash('success', 'Proyecto actualizado.');
            redirect(base_url("maestros/proyectos/{$id}"));
        } catch (Throwable $e) {
            log_message('error', 'ProyectoService actualizar: ' . $e->getMessage());
            $this->flash('error', 'Error al actualizar: ' . $e->getMessage());
            redirect(base_url("maestros/proyectos/editar/{$id}"));
        }
    }

    public function eliminar(int $id)
    {
        $this->require_permission('maestros.proyecto.eliminar');
        $p = $this->ProyectoRepo->find($id);
        if (!$p) show_404();
        $this->ProyectoRepo->softDelete($id);
        $this->audit->log('proyecto.eliminar', 'gmc_proyectos', $id, $p, null);
        $this->flash('success', 'Proyecto eliminado.');
        redirect(base_url('maestros/proyectos'));
    }

    private function _renderForm(?array $proyecto): void
    {
        $clientes = $this->ClienteRepo->findBy(['activo' => 1], 'razon_social ASC');
        $usuarios = $this->db->select('id, nombres, apellidos, email')
                             ->where('activo', 1)
                             ->where('deleted_at IS NULL', null, false)
                             ->order_by('nombres')->get('gmc_usuarios')->result_array();
        $estados  = $this->db->where('dominio','proyecto')->order_by('orden')->get('gmc_estados')->result_array();

        $this->view('maestros/proyectos/form', [
            'proyecto' => $proyecto,
            'is_edit'  => $proyecto !== null,
            'clientes' => $clientes,
            'comunas'  => $this->ComunaRepo->todasConRegion(),
            'usuarios' => $usuarios,
            'estados'  => $estados,
        ]);
    }
}
