<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Tipos_gasto extends MY_AuthController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('TipoGastoRepo');
    }

    public function index()
    {
        $this->require_permission('maestros.tipo_gasto.ver');
        $rows = $this->TipoGastoRepo->findBy([], 'codigo ASC');
        $this->view('maestros/tipos_gasto/index', ['tipos' => $rows]);
    }

    public function crear()
    {
        $this->require_permission('maestros.tipo_gasto.editar');
        if ($this->input->method() !== 'post') {
            return $this->view('maestros/tipos_gasto/form', ['tipo' => null, 'is_edit' => false]);
        }

        $codigo = strtoupper(trim((string)$this->input->post('codigo')));
        $nombre = trim((string)$this->input->post('nombre'));
        $activo = (int)($this->input->post('activo') ?? 1);

        $errors = [];
        if (!preg_match('/^[A-Z0-9_-]{1,30}$/', $codigo)) $errors[] = 'Código inválido (1-30 chars, mayúsculas/números/-/_).';
        if (strlen($nombre) < 2) $errors[] = 'Nombre demasiado corto.';
        if (!$errors && !$this->TipoGastoRepo->checkCodigoUnique($codigo)) $errors[] = 'El código ya existe.';
        if ($errors) {
            $this->flash('error', implode(' ', $errors));
            redirect(base_url('maestros/tipos-gasto/crear'));
        }

        $id = $this->TipoGastoRepo->create(compact('codigo','nombre','activo'));
        $this->audit->log('tipo_gasto.crear', 'gmc_tipos_gasto', $id, null, compact('codigo','nombre'));
        $this->flash('success', 'Tipo de gasto creado.');
        redirect(base_url('maestros/tipos-gasto'));
    }

    public function editar(int $id)
    {
        $this->require_permission('maestros.tipo_gasto.editar');
        $row = $this->TipoGastoRepo->find($id);
        if (!$row) show_404();

        if ($this->input->method() !== 'post') {
            return $this->view('maestros/tipos_gasto/form', ['tipo' => $row, 'is_edit' => true]);
        }

        $codigo = strtoupper(trim((string)$this->input->post('codigo')));
        $nombre = trim((string)$this->input->post('nombre'));
        $activo = (int)($this->input->post('activo') ?? 1);

        if (!$this->TipoGastoRepo->checkCodigoUnique($codigo, $id)) {
            $this->flash('error', 'El código ya existe en otro tipo de gasto.');
            redirect(base_url("maestros/tipos-gasto/editar/{$id}"));
        }

        $this->TipoGastoRepo->update($id, compact('codigo','nombre','activo'));
        $this->audit->logChanges('tipo_gasto.editar', 'gmc_tipos_gasto', $id, $row, compact('codigo','nombre','activo'));
        $this->flash('success', 'Tipo de gasto actualizado.');
        redirect(base_url('maestros/tipos-gasto'));
    }

    public function eliminar(int $id)
    {
        $this->require_permission('maestros.tipo_gasto.editar');
        $row = $this->TipoGastoRepo->find($id);
        if (!$row) show_404();
        $this->TipoGastoRepo->softDelete($id);
        $this->audit->log('tipo_gasto.eliminar', 'gmc_tipos_gasto', $id, $row, null);
        $this->flash('success', 'Tipo de gasto eliminado.');
        redirect(base_url('maestros/tipos-gasto'));
    }
}
