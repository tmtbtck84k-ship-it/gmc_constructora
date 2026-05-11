<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'services/ActividadService.php';

class Actividades extends MY_AuthController
{
    /** @var ActividadService */
    protected $svc;

    public function __construct()
    {
        parent::__construct();
        $this->load->model(['ActividadRepo','HitoRepo','ProyectoRepo','UsuarioRepo']);
        $this->svc = new ActividadService();
    }

    /** Lista plana de actividades del proyecto (F2C-A-10). */
    public function index()
    {
        $this->require_permission('obras.gantt.ver');
        $proyectoId = (int)$this->input->get('proyecto_id');
        $proyectos  = $this->ProyectoRepo->activos();

        $proyecto = null; $rows = []; $hitos = []; $usuarios = [];
        if ($proyectoId) {
            $proyecto = $this->ProyectoRepo->findFull($proyectoId);
            if (!$proyecto) show_404();
            $filters = [
                'hito_id'        => (int)$this->input->get('hito_id'),
                'responsable_id' => (int)$this->input->get('responsable_id'),
                'desde'          => $this->input->get('desde'),
                'hasta'          => $this->input->get('hasta'),
                'estado'         => $this->input->get('estado'),
            ];
            $rows = $this->ActividadRepo->listByProyecto($proyectoId, $filters);
            $hitos = $this->HitoRepo->listByProyecto($proyectoId);
            $usuarios = $this->UsuarioRepo->activos();
        }

        $this->view('obras/actividades/index', [
            'proyecto'  => $proyecto,
            'proyectos' => $proyectos,
            'rows'      => $rows,
            'hitos'     => $hitos,
            'usuarios'  => $usuarios,
            'filters'   => [
                'hito_id'        => $this->input->get('hito_id'),
                'responsable_id' => $this->input->get('responsable_id'),
                'desde'          => $this->input->get('desde'),
                'hasta'          => $this->input->get('hasta'),
                'estado'         => $this->input->get('estado'),
            ],
        ]);
    }

    public function nuevo()
    {
        $this->require_permission('obras.gantt.editar');
        $proyectoId = (int)$this->input->get('proyecto_id');
        $proyecto = $this->ProyectoRepo->findFull($proyectoId);
        if (!$proyecto) show_404();
        $this->_assertEditable($proyecto);
        $this->view('obras/actividades/form', [
            'proyecto' => $proyecto,
            'a'        => null,
            'hitos'    => $this->HitoRepo->listByProyecto($proyectoId),
            'usuarios' => $this->UsuarioRepo->activos(),
        ]);
    }

    public function crear()
    {
        $this->require_permission('obras.gantt.editar');
        $proyectoId = (int)$this->input->post('proyecto_id');
        $proyecto = $this->ProyectoRepo->findFull($proyectoId);
        if (!$proyecto) show_404();
        $this->_assertEditable($proyecto);
        try {
            $this->svc->crear($this->input->post(), $this->user_id());
            $this->flash('success','Actividad creada.');
            redirect('obras/actividades?proyecto_id=' . $proyectoId);
        } catch (Throwable $e) {
            $this->flash('error',$e->getMessage());
            redirect('obras/actividades/nuevo?proyecto_id=' . $proyectoId);
        }
    }

    public function editar(int $id)
    {
        $this->require_permission('obras.gantt.editar');
        $a = $this->ActividadRepo->findFull($id);
        if (!$a) show_404();
        $proyecto = $this->ProyectoRepo->findFull((int)$a['proyecto_id']);
        $this->_assertEditable($proyecto);
        $this->view('obras/actividades/form', [
            'proyecto' => $proyecto,
            'a'        => $a,
            'hitos'    => $this->HitoRepo->listByProyecto((int)$a['proyecto_id']),
            'usuarios' => $this->UsuarioRepo->activos(),
        ]);
    }

    public function actualizar(int $id)
    {
        $this->require_permission('obras.gantt.editar');
        $a = $this->ActividadRepo->find($id);
        if (!$a) show_404();
        $proyecto = $this->ProyectoRepo->findFull((int)$a['proyecto_id']);
        $this->_assertEditable($proyecto);
        try {
            $this->svc->editar($id, $this->input->post(), $this->user_id());
            $this->flash('success','Actividad actualizada.');
            redirect('obras/actividades?proyecto_id=' . $a['proyecto_id']);
        } catch (Throwable $e) {
            $this->flash('error',$e->getMessage());
            redirect('obras/actividades/editar/' . $id);
        }
    }

    public function eliminar(int $id)
    {
        $this->require_permission('obras.gantt.editar');
        $a = $this->ActividadRepo->find($id);
        if (!$a) show_404();
        $proyecto = $this->ProyectoRepo->findFull((int)$a['proyecto_id']);
        $this->_assertEditable($proyecto);
        try {
            $this->svc->eliminar($id, $this->user_id());
            $this->flash('success','Actividad eliminada.');
        } catch (Throwable $e) {
            $this->flash('error',$e->getMessage());
        }
        redirect('obras/actividades?proyecto_id=' . $a['proyecto_id']);
    }

    /**
     * AJAX: drag&drop de barra del Gantt.
     * Recibe nuevas fechas (inicio y fin) y la duración recalculada.
     * Dispara la cascada por dependencias.
     */
    public function mover(int $id)
    {
        $this->require_permission('obras.gantt.editar');
        $a = $this->ActividadRepo->find($id);
        if (!$a) return $this->json(['ok'=>false,'error'=>'Actividad no encontrada.'], 404);
        $proyecto = $this->ProyectoRepo->findFull((int)$a['proyecto_id']);
        if (in_array($proyecto['estado'] ?? '', ['cerrado','cancelado'], true)) {
            return $this->json(['ok'=>false,'error'=>'Proyecto cerrado: sólo lectura.'], 403);
        }
        try {
            $input = [
                'fecha_inicio_planificada' => $this->input->post('fecha_inicio'),
                'duracion_dias'            => (int)$this->input->post('duracion_dias'),
            ];
            $this->svc->editar($id, $input, $this->user_id());

            require_once APPPATH . 'services/PlanificadorService.php';
            require_once APPPATH . 'services/CpmService.php';
            $planner   = new PlanificadorService((int)$a['proyecto_id']);
            $resultado = $planner->recalcularDesde($id);
            (new CpmService((int)$a['proyecto_id']))->recalcular();

            $actualizada = $this->ActividadRepo->find($id);
            return $this->json([
                'ok' => true,
                'actividad' => [
                    'id'      => (int)$actualizada['id'],
                    'inicio'  => $actualizada['fecha_inicio_planificada'],
                    'termino' => $actualizada['fecha_termino_planificada'],
                    'duracion'=> (int)$actualizada['duracion_dias'],
                ],
                'movidas' => $resultado['movidas'],
            ]);
        } catch (Throwable $e) {
            return $this->json(['ok'=>false,'error'=>$e->getMessage()], 422);
        }
    }

    /** AJAX: actualizar % de avance desde el slider del Gantt o lista. */
    public function avance(int $id)
    {
        $this->require_permission('obras.gantt.editar');
        $porcentaje = (float)$this->input->post('porcentaje');
        try {
            $this->svc->actualizarAvance($id, $porcentaje, $this->user_id());
            return $this->json(['ok' => true, 'porcentaje' => $porcentaje]);
        } catch (Throwable $e) {
            return $this->json(['ok' => false, 'error' => $e->getMessage()], 422);
        }
    }

    private function _assertEditable(array $proyecto): void
    {
        if (in_array($proyecto['estado'] ?? '', ['cerrado','cancelado'], true)) {
            $this->flash('error','El proyecto está cerrado: el Gantt es de sólo lectura.');
            redirect('obras/actividades?proyecto_id=' . $proyecto['id']);
        }
    }
}
