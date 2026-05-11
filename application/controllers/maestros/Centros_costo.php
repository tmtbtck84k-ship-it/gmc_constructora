<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Centros_costo extends MY_AuthController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model(['CentroCostoRepo','ProyectoRepo']);
    }

    /**
     * Vista principal: si llega ?proyecto_id=N muestra los CC de ese proyecto;
     * si llega sin parámetro, muestra los CC generales (proyecto_id NULL),
     * típicamente sólo el CC ADM general.
     */
    public function index()
    {
        $this->require_permission('maestros.cc.ver');

        $proyectoId = $this->input->get('proyecto_id');
        $proyectoId = $proyectoId ? (int)$proyectoId : null;

        $proyecto = $proyectoId ? $this->ProyectoRepo->findFull($proyectoId) : null;
        if ($proyectoId && !$proyecto) show_404();

        $rows = $this->CentroCostoRepo->listByProyecto($proyectoId);

        $this->view('maestros/centros_costo/index', [
            'centros'    => $rows,
            'proyecto'   => $proyecto,
            'proyectos'  => $this->ProyectoRepo->activos(),
        ]);
    }

    public function crear()
    {
        $this->require_permission('maestros.cc.crear');
        $proyectoId = $this->input->get('proyecto_id');
        $proyectoId = $proyectoId ? (int)$proyectoId : null;

        if ($this->input->method() !== 'post') {
            return $this->view('maestros/centros_costo/form', [
                'centro'     => null,
                'is_edit'    => false,
                'proyectos'  => $this->ProyectoRepo->activos(),
                'proyecto_id'=> $proyectoId,
            ]);
        }
        $this->_save();
    }

    public function editar(int $id)
    {
        $this->require_permission('maestros.cc.editar');
        $cc = $this->CentroCostoRepo->find($id);
        if (!$cc) show_404();

        // Bloqueo: no editar el CC ADM general (proyecto_id NULL, es_administracion=1)
        if ((int)$cc['es_administracion'] === 1 && $cc['proyecto_id'] === null) {
            $this->flash('error', 'El CC "Administración" general no es editable.');
            redirect(base_url('maestros/centros-costo'));
        }

        if ($this->input->method() !== 'post') {
            return $this->view('maestros/centros_costo/form', [
                'centro'    => $cc,
                'is_edit'   => true,
                'proyectos' => $this->ProyectoRepo->activos(),
                'proyecto_id' => $cc['proyecto_id'] ? (int)$cc['proyecto_id'] : null,
            ]);
        }
        $this->_save($id);
    }

    public function eliminar(int $id)
    {
        $this->require_permission('maestros.cc.eliminar');
        $cc = $this->CentroCostoRepo->find($id);
        if (!$cc) show_404();

        // Bloqueo: no eliminar CC administración (ni el general ni el ADM-OBR auto)
        if ((int)$cc['es_administracion'] === 1) {
            $this->flash('error', 'No puedes eliminar un CC de Administración.');
            redirect(base_url('maestros/centros-costo' . ($cc['proyecto_id'] ? '?proyecto_id=' . (int)$cc['proyecto_id'] : '')));
        }

        $this->CentroCostoRepo->softDelete($id);
        $this->audit->log('cc.eliminar', 'gmc_centros_costo', $id, $cc, null);
        $this->flash('success', 'Centro de costo eliminado.');
        redirect(base_url('maestros/centros-costo' . ($cc['proyecto_id'] ? '?proyecto_id=' . (int)$cc['proyecto_id'] : '')));
    }

    private function _save(?int $id = null): void
    {
        $isEdit = $id !== null;
        $proyectoId = $this->input->post('proyecto_id');
        $proyectoId = $proyectoId ? (int)$proyectoId : null;
        $codigo = strtoupper(trim((string)$this->input->post('codigo')));
        $nombre = trim((string)$this->input->post('nombre'));
        $activo = (int)($this->input->post('activo') ?? 1);

        $errors = [];
        if (!preg_match('/^[A-Z0-9_-]{1,30}$/', $codigo)) $errors[] = 'Código inválido (1-30 chars, mayúsculas/números/-/_).';
        if (strlen($nombre) < 2) $errors[] = 'Nombre demasiado corto.';
        if (!$errors && !$this->CentroCostoRepo->checkCodigoUnique($proyectoId, $codigo, $id)) {
            $errors[] = 'Ya existe un CC con ese código en el mismo ámbito.';
        }
        if ($errors) {
            $this->flash('error', implode(' ', $errors));
            $back = $isEdit ? "maestros/centros-costo/editar/{$id}" : 'maestros/centros-costo/crear' . ($proyectoId ? "?proyecto_id={$proyectoId}" : '');
            redirect(base_url($back));
        }

        $payload = [
            'proyecto_id'       => $proyectoId,
            'codigo'            => $codigo,
            'nombre'            => $nombre,
            'es_administracion' => 0, // sólo el seed/proyecto_service crean es_administracion=1
            'activo'            => $activo,
        ];

        if ($isEdit) {
            $before = $this->CentroCostoRepo->find($id);
            $this->CentroCostoRepo->update($id, $payload);
            $this->audit->logChanges('cc.editar', 'gmc_centros_costo', $id, $before, $payload);
            $this->flash('success', 'Centro de costo actualizado.');
        } else {
            $newId = $this->CentroCostoRepo->create($payload);
            $this->audit->log('cc.crear', 'gmc_centros_costo', $newId, null, $payload);
            $this->flash('success', 'Centro de costo creado.');
        }

        redirect(base_url('maestros/centros-costo' . ($proyectoId ? "?proyecto_id={$proyectoId}" : '')));
    }
}
