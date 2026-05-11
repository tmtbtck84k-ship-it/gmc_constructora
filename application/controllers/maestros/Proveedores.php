<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Proveedores extends MY_AuthController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model(['ProveedorRepo','ComunaRepo']);
        $this->load->helper('rut');
    }

    public function index()
    {
        $this->require_permission('maestros.proveedor.ver');
        $filters = [
            'q'      => trim((string)$this->input->get('q')),
            'activo' => $this->input->get('activo'),
            'subc'   => $this->input->get('subc'),
        ];
        $rows = $this->ProveedorRepo->listAll($filters);
        $this->view('maestros/proveedores/index', ['proveedores' => $rows, 'filters' => $filters]);
    }

    public function crear()
    {
        $this->require_permission('maestros.proveedor.crear');
        if ($this->input->method() !== 'post') {
            return $this->view('maestros/proveedores/form', [
                'proveedor' => null, 'is_edit' => false,
                'comunas' => $this->ComunaRepo->todasConRegion(),
            ]);
        }
        $this->_save();
    }

    public function editar(int $id)
    {
        $this->require_permission('maestros.proveedor.editar');
        $row = $this->ProveedorRepo->find($id);
        if (!$row) show_404();
        if ($this->input->method() !== 'post') {
            return $this->view('maestros/proveedores/form', [
                'proveedor' => $row, 'is_edit' => true,
                'comunas' => $this->ComunaRepo->todasConRegion(),
            ]);
        }
        $this->_save($id);
    }

    public function eliminar(int $id)
    {
        $this->require_permission('maestros.proveedor.eliminar');
        $row = $this->ProveedorRepo->find($id);
        if (!$row) show_404();
        $this->ProveedorRepo->softDelete($id);
        $this->audit->log('proveedor.eliminar', 'gmc_proveedores', $id, $row, null);
        $this->flash('success', 'Proveedor eliminado.');
        redirect(base_url('maestros/proveedores'));
    }

    private function _save(?int $id = null): void
    {
        $isEdit = $id !== null;
        $rut       = normalizar_rut((string)$this->input->post('rut'));
        $razon     = trim((string)$this->input->post('razon_social'));
        $fantasia  = trim((string)$this->input->post('nombre_fantasia')) ?: null;
        $giro      = trim((string)$this->input->post('giro')) ?: null;
        $direccion = trim((string)$this->input->post('direccion')) ?: null;
        $comunaId  = $this->input->post('comuna_id') ?: null;
        $email     = strtolower(trim((string)$this->input->post('email'))) ?: null;
        $telefono  = trim((string)$this->input->post('telefono')) ?: null;
        $ctNombre  = trim((string)$this->input->post('contacto_nombre')) ?: null;
        $ctEmail   = strtolower(trim((string)$this->input->post('contacto_email'))) ?: null;
        $ctTel     = trim((string)$this->input->post('contacto_telefono')) ?: null;
        $esSubc    = (int)($this->input->post('es_subcontratista') ?? 0);
        $categoria = trim((string)$this->input->post('categoria')) ?: null;
        $activo    = (int)($this->input->post('activo') ?? 1);

        $errors = [];
        if (!validar_rut($rut)) $errors[] = 'RUT inválido.';
        if (strlen($razon) < 2) $errors[] = 'Razón social inválida.';
        if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email inválido.';
        if (!$errors) {
            if ($e = $this->ProveedorRepo->checkUnique($rut, $id)) $errors[] = $e;
        }
        if ($errors) {
            $this->flash('error', implode(' ', $errors));
            redirect($isEdit ? base_url("maestros/proveedores/editar/{$id}") : base_url('maestros/proveedores/crear'));
        }

        $payload = [
            'rut' => $rut, 'razon_social' => $razon, 'nombre_fantasia' => $fantasia, 'giro' => $giro,
            'direccion' => $direccion, 'comuna_id' => $comunaId ? (int)$comunaId : null,
            'email' => $email, 'telefono' => $telefono,
            'contacto_nombre' => $ctNombre, 'contacto_email' => $ctEmail, 'contacto_telefono' => $ctTel,
            'es_subcontratista' => $esSubc, 'categoria' => $categoria, 'activo' => $activo,
        ];

        if ($isEdit) {
            $before = $this->ProveedorRepo->find($id);
            $this->ProveedorRepo->update($id, $payload);
            $this->audit->logChanges('proveedor.editar', 'gmc_proveedores', $id, $before, $payload);
            $this->flash('success', 'Proveedor actualizado.');
        } else {
            $newId = $this->ProveedorRepo->create($payload);
            $this->audit->log('proveedor.crear', 'gmc_proveedores', $newId, null, $payload);
            $this->flash('success', 'Proveedor creado.');
        }
        redirect(base_url('maestros/proveedores'));
    }
}
