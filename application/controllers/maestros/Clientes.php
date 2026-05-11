<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Clientes extends MY_AuthController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model(['ClienteRepo','ComunaRepo']);
        $this->load->helper('rut');
    }

    public function index()
    {
        $this->require_permission('maestros.cliente.ver');
        $filters = [
            'q'      => trim((string)$this->input->get('q')),
            'activo' => $this->input->get('activo'),
        ];
        $rows = $this->ClienteRepo->listAll($filters);
        $this->view('maestros/clientes/index', ['clientes' => $rows, 'filters' => $filters]);
    }

    public function ver(int $id)
    {
        $this->require_permission('maestros.cliente.ver');
        $rows = $this->ClienteRepo->listAll([]);
        $cliente = null;
        foreach ($rows as $r) if ((int)$r['id'] === $id) { $cliente = $r; break; }
        if (!$cliente) show_404();
        $this->view('maestros/clientes/ver', ['cliente' => $cliente]);
    }

    public function crear()
    {
        $this->require_permission('maestros.cliente.crear');
        if ($this->input->method() !== 'post') {
            return $this->view('maestros/clientes/form', [
                'cliente' => null,
                'is_edit' => false,
                'comunas' => $this->ComunaRepo->todasConRegion(),
            ]);
        }
        $this->_save();
    }

    public function editar(int $id)
    {
        $this->require_permission('maestros.cliente.editar');
        $cliente = $this->ClienteRepo->find($id);
        if (!$cliente) show_404();

        if ($this->input->method() !== 'post') {
            return $this->view('maestros/clientes/form', [
                'cliente' => $cliente,
                'is_edit' => true,
                'comunas' => $this->ComunaRepo->todasConRegion(),
            ]);
        }
        $this->_save($id);
    }

    public function eliminar(int $id)
    {
        $this->require_permission('maestros.cliente.eliminar');
        $row = $this->ClienteRepo->find($id);
        if (!$row) show_404();
        $this->ClienteRepo->softDelete($id);
        $this->audit->log('cliente.eliminar', 'gmc_clientes', $id, $row, null);
        $this->flash('success', 'Cliente eliminado.');
        redirect(base_url('maestros/clientes'));
    }

    private function _save(?int $id = null): void
    {
        $isEdit = $id !== null;
        $rut             = normalizar_rut((string)$this->input->post('rut'));
        $razon           = trim((string)$this->input->post('razon_social'));
        $fantasia        = trim((string)$this->input->post('nombre_fantasia')) ?: null;
        $giro            = trim((string)$this->input->post('giro')) ?: null;
        $direccion       = trim((string)$this->input->post('direccion')) ?: null;
        $comunaId        = $this->input->post('comuna_id') ?: null;
        $email           = strtolower(trim((string)$this->input->post('email'))) ?: null;
        $telefono        = trim((string)$this->input->post('telefono')) ?: null;
        $ctNombre        = trim((string)$this->input->post('contacto_nombre')) ?: null;
        $ctEmail         = strtolower(trim((string)$this->input->post('contacto_email'))) ?: null;
        $ctTel           = trim((string)$this->input->post('contacto_telefono')) ?: null;
        $activo          = (int)($this->input->post('activo') ?? 1);

        $errors = [];
        if (!validar_rut($rut))      $errors[] = 'RUT inválido.';
        if (strlen($razon) < 2)      $errors[] = 'Razón social inválida.';
        if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email principal inválido.';
        if ($ctEmail && !filter_var($ctEmail, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email de contacto inválido.';
        if (!$errors) {
            if ($e = $this->ClienteRepo->checkUnique($rut, $email, $id)) $errors[] = $e;
        }
        if ($errors) {
            $this->flash('error', implode(' ', $errors));
            redirect($isEdit ? base_url("maestros/clientes/editar/{$id}") : base_url('maestros/clientes/crear'));
        }

        $payload = [
            'rut' => $rut, 'razon_social' => $razon, 'nombre_fantasia' => $fantasia, 'giro' => $giro,
            'direccion' => $direccion, 'comuna_id' => $comunaId ? (int)$comunaId : null,
            'email' => $email, 'telefono' => $telefono,
            'contacto_nombre' => $ctNombre, 'contacto_email' => $ctEmail, 'contacto_telefono' => $ctTel,
            'activo' => $activo,
        ];

        if ($isEdit) {
            $before = $this->ClienteRepo->find($id);
            $this->ClienteRepo->update($id, $payload);
            $this->audit->logChanges('cliente.editar', 'gmc_clientes', $id, $before, $payload);
            $this->flash('success', 'Cliente actualizado.');
        } else {
            $newId = $this->ClienteRepo->create($payload);
            $this->audit->log('cliente.crear', 'gmc_clientes', $newId, null, $payload);
            $this->flash('success', 'Cliente creado.');
            $id = $newId;
        }
        redirect(base_url('maestros/clientes'));
    }
}
