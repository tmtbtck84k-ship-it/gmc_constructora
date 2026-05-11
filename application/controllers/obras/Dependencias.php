<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'services/DependenciaService.php';

class Dependencias extends MY_AuthController
{
    /** @var DependenciaService */
    protected $svc;

    public function __construct()
    {
        parent::__construct();
        $this->load->model(['DependenciaRepo','ActividadRepo','ProyectoRepo']);
        $this->svc = new DependenciaService();
    }

    /** AJAX: lista todas las dependencias de un proyecto. */
    public function por_proyecto(int $proyectoId)
    {
        $this->require_permission('obras.gantt.ver');
        $rows = $this->DependenciaRepo->listByProyecto($proyectoId);
        return $this->json(['ok' => true, 'rows' => $rows]);
    }

    /** AJAX POST: crea una dependencia. */
    public function crear()
    {
        $this->require_permission('obras.gantt.dependencia');
        try {
            $r = $this->svc->crear($this->input->post(), $this->user_id());
            return $this->json(['ok' => true] + $r);
        } catch (Throwable $e) {
            return $this->json(['ok' => false, 'error' => $e->getMessage()], 422);
        }
    }

    /** AJAX POST: elimina una dependencia. */
    public function eliminar(int $id)
    {
        $this->require_permission('obras.gantt.dependencia');
        try {
            $r = $this->svc->eliminar($id, $this->user_id());
            return $this->json(['ok' => true] + $r);
        } catch (Throwable $e) {
            return $this->json(['ok' => false, 'error' => $e->getMessage()], 422);
        }
    }
}
