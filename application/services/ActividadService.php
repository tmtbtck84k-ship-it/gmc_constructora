<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'services/CorrelativoService.php';
require_once APPPATH . 'services/CalendarioService.php';

class ActividadService
{
    /** @var CI_Controller */
    protected $CI;
    protected $correlativo;

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->model([
            'ActividadRepo','HitoRepo','ProyectoRepo','DependenciaRepo'
        ]);
        $this->CI->load->library('Audit');
        $this->correlativo = new CorrelativoService();
    }

    /**
     * Crea una actividad nueva.
     * Calcula fecha_termino_planificada en función del calendario del proyecto.
     */
    public function crear(array $input, int $userId): int
    {
        $proyectoId = (int)$input['proyecto_id'];
        $proyecto   = $this->CI->ProyectoRepo->find($proyectoId);
        if (!$proyecto) throw new RuntimeException('Proyecto no encontrado.');

        $hitoId = !empty($input['hito_id']) ? (int)$input['hito_id'] : null;
        if ($hitoId !== null) {
            $h = $this->CI->HitoRepo->find($hitoId);
            if (!$h) throw new RuntimeException('Hito no encontrado.');
            if ((int)$h['proyecto_id'] !== $proyectoId) {
                throw new RuntimeException('El hito no pertenece al proyecto seleccionado.');
            }
        }

        $fechaInicio   = $input['fecha_inicio_planificada'];
        $duracion      = (int)$input['duracion_dias'];
        if ($duracion < 1) throw new RuntimeException('La duración debe ser mayor a 0 días.');

        $cal           = new CalendarioService($proyecto);
        // Si la fecha de inicio cae en no laboral, la corremos al siguiente laboral
        if (!$cal->esLaboral($fechaInicio)) {
            $fechaInicio = $cal->siguienteLaboral($fechaInicio);
        }
        $fechaTermino  = $cal->calcularTermino($fechaInicio, $duracion);

        $codigo = $this->correlativo->next('actividad') . '/' . $proyecto['codigo'];
        $orden  = !empty($input['orden']) ? (int)$input['orden']
                                          : $this->CI->ActividadRepo->siguienteOrden($hitoId ?? 0);

        $payload = [
            'proyecto_id'              => $proyectoId,
            'hito_id'                  => $hitoId,
            'codigo'                   => $codigo,
            'nombre'                   => trim((string)$input['nombre']),
            'descripcion'              => $input['descripcion'] ?? null,
            'fecha_inicio_planificada' => $fechaInicio,
            'fecha_termino_planificada'=> $fechaTermino,
            'duracion_dias'            => $duracion,
            'porcentaje_avance'        => 0,
            'responsable_id'           => !empty($input['responsable_id']) ? (int)$input['responsable_id'] : null,
            'colaboradores_libres'     => $input['colaboradores_libres'] ?? null,
            'orden'                    => $orden,
            'created_by'               => $userId,
            'updated_by'               => $userId,
        ];

        $id = $this->CI->ActividadRepo->create($payload);

        if ($hitoId !== null) {
            $this->CI->HitoRepo->recalcularAvance($hitoId);
        }

        $this->CI->audit->log('actividad.crear', 'gmc_actividades', $id, null, [
            'codigo' => $codigo, 'nombre' => $payload['nombre'], 'proyecto_id' => $proyectoId,
            'hito_id' => $hitoId, 'duracion_dias' => $duracion,
        ]);

        return $id;
    }

    /**
     * Edita campos básicos. Si cambian inicio o duración, recalcula término.
     */
    public function editar(int $id, array $input, int $userId): void
    {
        $a = $this->CI->ActividadRepo->find($id);
        if (!$a) throw new RuntimeException('Actividad no encontrada.');

        $proyecto = $this->CI->ProyectoRepo->find((int)$a['proyecto_id']);
        if (!$proyecto) throw new RuntimeException('Proyecto no encontrado.');

        // Validar hito (si cambia)
        $nuevoHito = array_key_exists('hito_id', $input)
            ? (!empty($input['hito_id']) ? (int)$input['hito_id'] : null)
            : (!empty($a['hito_id']) ? (int)$a['hito_id'] : null);

        if ($nuevoHito !== null) {
            $h = $this->CI->HitoRepo->find($nuevoHito);
            if (!$h) throw new RuntimeException('Hito no encontrado.');
            if ((int)$h['proyecto_id'] !== (int)$a['proyecto_id']) {
                throw new RuntimeException('El hito no pertenece al proyecto.');
            }
        }

        $fechaInicio = $input['fecha_inicio_planificada'] ?? $a['fecha_inicio_planificada'];
        $duracion    = isset($input['duracion_dias']) ? (int)$input['duracion_dias'] : (int)$a['duracion_dias'];
        if ($duracion < 1) throw new RuntimeException('La duración debe ser mayor a 0 días.');

        $cal = new CalendarioService($proyecto);
        if (!$cal->esLaboral($fechaInicio)) {
            $fechaInicio = $cal->siguienteLaboral($fechaInicio);
        }
        $fechaTermino = $cal->calcularTermino($fechaInicio, $duracion);

        $payload = [
            'hito_id'                  => $nuevoHito,
            'nombre'                   => trim((string)($input['nombre'] ?? $a['nombre'])),
            'descripcion'              => array_key_exists('descripcion', $input) ? $input['descripcion'] : $a['descripcion'],
            'fecha_inicio_planificada' => $fechaInicio,
            'fecha_termino_planificada'=> $fechaTermino,
            'fecha_inicio_real'        => array_key_exists('fecha_inicio_real', $input)
                                            ? (!empty($input['fecha_inicio_real']) ? $input['fecha_inicio_real'] : null)
                                            : $a['fecha_inicio_real'],
            'fecha_termino_real'       => array_key_exists('fecha_termino_real', $input)
                                            ? (!empty($input['fecha_termino_real']) ? $input['fecha_termino_real'] : null)
                                            : $a['fecha_termino_real'],
            'duracion_dias'            => $duracion,
            'responsable_id'           => array_key_exists('responsable_id', $input)
                                            ? (!empty($input['responsable_id']) ? (int)$input['responsable_id'] : null)
                                            : (!empty($a['responsable_id']) ? (int)$a['responsable_id'] : null),
            'colaboradores_libres'     => array_key_exists('colaboradores_libres', $input)
                                            ? $input['colaboradores_libres']
                                            : $a['colaboradores_libres'],
            'orden'                    => isset($input['orden']) ? (int)$input['orden'] : (int)$a['orden'],
            'updated_by'               => $userId,
        ];

        $this->CI->ActividadRepo->update($id, $payload);

        // Recalcular avance del hito viejo y nuevo (si cambió)
        $hitoViejo = !empty($a['hito_id']) ? (int)$a['hito_id'] : null;
        if ($hitoViejo !== null) $this->CI->HitoRepo->recalcularAvance($hitoViejo);
        if ($nuevoHito !== null && $nuevoHito !== $hitoViejo) {
            $this->CI->HitoRepo->recalcularAvance($nuevoHito);
        }

        $this->CI->audit->logChanges('actividad.editar', 'gmc_actividades', $id, $a, $payload);
    }

    /**
     * Setter especializado para % de avance (slider del Gantt).
     */
    public function actualizarAvance(int $id, float $porcentaje, int $userId): void
    {
        if ($porcentaje < 0 || $porcentaje > 100) {
            throw new RuntimeException('Porcentaje fuera de rango (0–100).');
        }
        $a = $this->CI->ActividadRepo->find($id);
        if (!$a) throw new RuntimeException('Actividad no encontrada.');

        $payload = [
            'porcentaje_avance' => $porcentaje,
            'updated_by'        => $userId,
        ];
        // Si llega a 100% y no hay fecha real de término, sellar con la planificada
        if ($porcentaje >= 100 && empty($a['fecha_termino_real'])) {
            $payload['fecha_termino_real'] = $a['fecha_termino_planificada'];
        }
        $this->CI->ActividadRepo->update($id, $payload);

        if (!empty($a['hito_id'])) {
            $this->CI->HitoRepo->recalcularAvance((int)$a['hito_id']);
        }

        $this->CI->audit->log('actividad.avance', 'gmc_actividades', $id,
            ['porcentaje_avance' => $a['porcentaje_avance']],
            ['porcentaje_avance' => $porcentaje]
        );
    }

    /**
     * Elimina una actividad (soft delete). Sus dependencias se borran físicamente.
     */
    public function eliminar(int $id, int $userId): void
    {
        $a = $this->CI->ActividadRepo->find($id);
        if (!$a) throw new RuntimeException('Actividad no encontrada.');

        // Borra dependencias hard (no es información histórica relevante)
        $this->CI->DependenciaRepo->eliminarPorActividad($id);

        $this->CI->ActividadRepo->softDelete($id);

        if (!empty($a['hito_id'])) {
            $this->CI->HitoRepo->recalcularAvance((int)$a['hito_id']);
        }

        $this->CI->audit->log('actividad.eliminar', 'gmc_actividades', $id, $a, null);
    }
}
