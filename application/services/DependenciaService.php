<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'services/PlanificadorService.php';
require_once APPPATH . 'services/CpmService.php';

class DependenciaService
{
    /** @var CI_Controller */
    protected $CI;

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->model(['DependenciaRepo','ActividadRepo']);
        $this->CI->load->library('Audit');
    }

    /**
     * Crea una dependencia entre dos actividades.
     * Valida ciclos antes de guardar y recalcula la cascada.
     *
     * @return array  ['id' => N, 'movidas' => [...]]
     */
    public function crear(array $input, int $userId): array
    {
        $sucesoraId   = (int)$input['actividad_id'];
        $predecesorId = (int)$input['predecesor_id'];
        $tipo         = $input['tipo']     ?? 'FS';
        $lag          = isset($input['lag_dias']) ? (int)$input['lag_dias'] : 0;

        if ($sucesoraId === $predecesorId) {
            throw new RuntimeException('Una actividad no puede depender de sí misma.');
        }
        if (!in_array($tipo, ['FS','SS','FF','SF'], true)) {
            throw new RuntimeException('Tipo de dependencia inválido.');
        }

        $sucesora = $this->CI->ActividadRepo->find($sucesoraId);
        $predecesor = $this->CI->ActividadRepo->find($predecesorId);
        if (!$sucesora || !$predecesor) {
            throw new RuntimeException('Actividad sucesora o predecesora no existe.');
        }
        if ((int)$sucesora['proyecto_id'] !== (int)$predecesor['proyecto_id']) {
            throw new RuntimeException('Las actividades deben pertenecer al mismo proyecto.');
        }

        // Detectar ciclo BEFORE inserting (DFS)
        if ($this->CI->DependenciaRepo->generariaCiclo($sucesoraId, $predecesorId)) {
            throw new RuntimeException(
                'Esta dependencia generaría un ciclo: ' . $sucesora['codigo'] .
                ' ya depende (directa o indirectamente) de ' . $predecesor['codigo']
            );
        }

        // Evitar duplicado
        $dup = $this->CI->db
            ->where('actividad_id', $sucesoraId)
            ->where('predecesor_id', $predecesorId)
            ->get('gmc_actividad_dependencias')->row_array();
        if ($dup) throw new RuntimeException('Esta dependencia ya existe.');

        $id = $this->CI->DependenciaRepo->create([
            'actividad_id'  => $sucesoraId,
            'predecesor_id' => $predecesorId,
            'tipo'          => $tipo,
            'lag_dias'      => $lag,
        ]);

        $this->CI->audit->log('dependencia.crear', 'gmc_actividad_dependencias', $id, null, [
            'sucesora'   => $sucesora['codigo'],
            'predecesor' => $predecesor['codigo'],
            'tipo'       => $tipo,
            'lag_dias'   => $lag,
        ]);

        // Disparar recálculo en cascada + CPM
        $planner = new PlanificadorService((int)$sucesora['proyecto_id']);
        $resultado = $planner->recalcularDesde($predecesorId);
        (new CpmService((int)$sucesora['proyecto_id']))->recalcular();

        return ['id' => $id, 'movidas' => $resultado['movidas']];
    }

    /**
     * Elimina una dependencia y dispara recálculo (puede liberar a sucesoras).
     *
     * @return array  ['movidas' => [...]]
     */
    public function eliminar(int $id, int $userId): array
    {
        $dep = $this->CI->db->where('id', $id)->get('gmc_actividad_dependencias')->row_array();
        if (!$dep) throw new RuntimeException('Dependencia no encontrada.');

        $sucesora = $this->CI->ActividadRepo->find((int)$dep['actividad_id']);
        if (!$sucesora) throw new RuntimeException('Sucesora no encontrada.');

        $this->CI->db->where('id', $id)->delete('gmc_actividad_dependencias');

        $this->CI->audit->log('dependencia.eliminar', 'gmc_actividad_dependencias', $id, $dep, null);

        $planner = new PlanificadorService((int)$sucesora['proyecto_id']);
        $resultado = $planner->recalcularDesde((int)$dep['predecesor_id']);
        (new CpmService((int)$sucesora['proyecto_id']))->recalcular();

        return ['movidas' => $resultado['movidas']];
    }
}
