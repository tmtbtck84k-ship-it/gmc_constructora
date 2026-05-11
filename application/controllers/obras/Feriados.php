<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'services/FeriadoService.php';

class Feriados extends MY_AuthController
{
    /** @var FeriadoService */
    protected $svc;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('FeriadoRepo');
        $this->load->library('Uploader');
        $this->svc = new FeriadoService();
    }

    public function index()
    {
        // ver es público con cualquier permiso de gantt; editar requiere obras.feriado.editar
        if (!can('obras.gantt.ver') && !can('obras.feriado.editar')) {
            $this->require_permission('obras.feriado.editar');
        }
        $anio = (int)$this->input->get('anio') ?: (int)date('Y');
        $rows = $this->FeriadoRepo->listAll($anio);
        $this->view('obras/feriados/index', [
            'rows' => $rows,
            'anio' => $anio,
        ]);
    }

    public function nuevo()
    {
        $this->require_permission('obras.feriado.editar');
        $this->view('obras/feriados/form', ['f' => null]);
    }

    public function crear()
    {
        $this->require_permission('obras.feriado.editar');
        try {
            $this->svc->crear($this->input->post(), $this->user_id());
            $this->flash('success','Feriado creado.');
            redirect('obras/feriados');
        } catch (Throwable $e) {
            $this->flash('error',$e->getMessage());
            redirect('obras/feriados/nuevo');
        }
    }

    public function editar(int $id)
    {
        $this->require_permission('obras.feriado.editar');
        $f = $this->FeriadoRepo->find($id);
        if (!$f) show_404();
        $this->view('obras/feriados/form', ['f' => $f]);
    }

    public function actualizar(int $id)
    {
        $this->require_permission('obras.feriado.editar');
        try {
            $this->svc->editar($id, $this->input->post(), $this->user_id());
            $this->flash('success','Feriado actualizado.');
            redirect('obras/feriados');
        } catch (Throwable $e) {
            $this->flash('error',$e->getMessage());
            redirect('obras/feriados/editar/' . $id);
        }
    }

    public function eliminar(int $id)
    {
        $this->require_permission('obras.feriado.editar');
        try {
            $this->svc->eliminar($id, $this->user_id());
            $this->flash('success','Feriado eliminado.');
        } catch (Throwable $e) {
            $this->flash('error',$e->getMessage());
        }
        redirect('obras/feriados');
    }

    /** Importación masiva CSV (encabezado: fecha,nombre,irrenunciable,tipo). */
    public function importar()
    {
        $this->require_permission('obras.feriado.editar');
        if ($this->input->method() !== 'post') {
            return $this->view('obras/feriados/importar');
        }
        if (empty($_FILES['archivo']['tmp_name'])) {
            $this->flash('error','Adjunte el archivo CSV.');
            redirect('obras/feriados/importar');
        }
        try {
            [$ins, $upd, $errores] = $this->svc->importarCsv(
                $_FILES['archivo']['tmp_name'], $this->user_id()
            );
            $msg = "Importación: {$ins} insertados, {$upd} actualizados, " . count($errores) . ' errores.';
            if ($errores) {
                $this->session->set_flashdata('errores_csv', $errores);
            }
            $this->flash('success',$msg);
            redirect('obras/feriados');
        } catch (Throwable $e) {
            $this->flash('error',$e->getMessage());
            redirect('obras/feriados/importar');
        }
    }
}
