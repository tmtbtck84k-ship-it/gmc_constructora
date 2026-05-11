<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'services/CalendarioService.php';

/**
 * PlanificadorService — recalcula fechas de actividades en cascada según:
 *   - Tipo de dependencia (FS/SS/FF/SF) + lag_dias
 *   - Calendario laboral del proyecto (vía CalendarioService)
 *
 * Estrategia:
 *   1) Cargar todas las actividades del proyecto en memoria.
 *   2) Hacer una topological pass: para cada actividad, su nueva fecha de inicio
 *      es el máximo de las restricciones que imponen sus predecesoras.
 *   3) Iterar hasta estabilizar (con tope de seguridad por si se cuela un ciclo).
 *
 * Tipos de dependencia (siendo P=predecesor, S=sucesor):
 *   FS  Finish-to-Start  : start(S) >= finish(P) + lag (default)
 *   SS  Start-to-Start   : start(S) >= start(P) + lag
 *   FF  Finish-to-Finish : finish(S) >= finish(P) + lag
 *   SF  Start-to-Finish  : finish(S) >= start(P) + lag  (raro)
 */
class PlanificadorService
{
    /** @var CI_Controller */
    protected $CI;
    /** @var CalendarioService */
    protected $calendario;
    /** @var int */
    protected $maxIteraciones = 50;

    public function __construct(int $proyectoId)
    {
        $this->CI =& get_instance();
        $this->CI->load->model(['ProyectoRepo','ActividadRepo','DependenciaRepo','HitoRepo']);
        $proyecto = $this->CI->ProyectoRepo->find($proyectoId);
        if (!$proyecto) throw new RuntimeException('Proyecto no encontrado.');
        $this->calendario = new CalendarioService($proyecto);
        $this->proyectoId = $proyectoId;
    }

    /** @var int */
    private $proyectoId;

    /**
     * Recalcula las fechas de toda la cascada que arranca en $actividadIdInicial.
     * Persiste sólo las actividades que cambiaron y registra auditoría agregada.
     *
     * @return array  ['movidas' => [['id','codigo','antes_inicio','antes_termino','despues_inicio','despues_termino']]]
     */
    public function recalcularDesde(int $actividadIdInicial): array
    {
        // Cargar todas las actividades del proyecto en memoria por id
        $actividades = [];
        $rows = $this->CI->db
            ->select('id, codigo, fecha_inicio_planificada, fecha_termino_planificada, duracion_dias, hito_id')
            ->where('proyecto_id', $this->proyectoId)
            ->where('deleted_at IS NULL', null, false)
            ->get('gmc_actividades')->result_array();
        foreach ($rows as $r) {
            $actividades[(int)$r['id']] = [
                'id'           => (int)$r['id'],
                'codigo'       => $r['codigo'],
                'inicio'       => $r['fecha_inicio_planificada'],
                'termino'      => $r['fecha_termino_planificada'],
                'duracion'     => (int)$r['duracion_dias'],
                'hito_id'      => $r['hito_id'] ? (int)$r['hito_id'] : null,
                'inicio_orig'  => $r['fecha_inicio_planificada'],
                'termino_orig' => $r['fecha_termino_planificada'],
            ];
        }
        if (!isset($actividades[$actividadIdInicial])) return ['movidas' => []];

        // Cargar todas las dependencias del proyecto: índice "sucesora_id" => [{predecesor_id, tipo, lag_dias}, ...]
        $depsBySucesora = [];
        $deps = $this->CI->db
            ->select('d.actividad_id AS sucesora_id, d.predecesor_id, d.tipo, d.lag_dias')
            ->from('gmc_actividad_dependencias d')
            ->join('gmc_actividades a', 'a.id = d.actividad_id')
            ->where('a.proyecto_id', $this->proyectoId)
            ->where('a.deleted_at IS NULL', null, false)
            ->get()->result_array();
        foreach ($deps as $d) {
            $depsBySucesora[(int)$d['sucesora_id']][] = [
                'predecesor_id' => (int)$d['predecesor_id'],
                'tipo'          => $d['tipo'],
                'lag'           => (int)$d['lag_dias'],
            ];
        }

        // Iterar hasta estabilizar: en cada vuelta, recalculamos sucesoras de cualquier
        // actividad que haya cambiado en la vuelta anterior (o de la inicial en la 1ra).
        $cambiadas = [$actividadIdInicial => true];
        for ($iter = 0; $iter < $this->maxIteraciones; $iter++) {
            $nuevasCambiadas = [];

            // Recorrer todas las actividades buscando sucesoras de las que cambiaron
            foreach ($depsBySucesora as $sucId => $depList) {
                $tieneAfectada = false;
                foreach ($depList as $d) {
                    if (isset($cambiadas[$d['predecesor_id']])) { $tieneAfectada = true; break; }
                }
                if (!$tieneAfectada) continue;

                $suc = $actividades[$sucId];
                // Para cada predecesor, calcular la fecha mínima que impone
                $inicioMin  = null;
                $terminoMin = null;
                foreach ($depList as $d) {
                    $pred = $actividades[$d['predecesor_id']] ?? null;
                    if (!$pred) continue;
                    [$ti, $tt] = $this->_aplicarRestriccion(
                        $pred, $suc, $d['tipo'], $d['lag']
                    );
                    if ($ti !== null && ($inicioMin === null || $ti > $inicioMin))   $inicioMin  = $ti;
                    if ($tt !== null && ($terminoMin === null || $tt > $terminoMin)) $terminoMin = $tt;
                }

                // Calcular nuevo (inicio, término) respetando duración y calendario
                $nuevoInicio  = $suc['inicio'];
                $nuevoTermino = $suc['termino'];
                if ($inicioMin !== null && $inicioMin > $nuevoInicio) {
                    $nuevoInicio  = $this->calendario->siguienteLaboral($inicioMin);
                    $nuevoTermino = $this->calendario->calcularTermino($nuevoInicio, $suc['duracion']);
                }
                // FF/SF imponen restricción en el término: si el término actual no satisface,
                // mover el inicio hacia atrás manteniendo duración (o adelantar el término)
                if ($terminoMin !== null && $terminoMin > $nuevoTermino) {
                    $nuevoTermino = $this->calendario->siguienteLaboral($terminoMin);
                    // Recalcular inicio retrocediendo desde término por la duración
                    $nuevoInicio  = $this->_inicioPorTermino($nuevoTermino, $suc['duracion']);
                }

                if ($nuevoInicio !== $suc['inicio'] || $nuevoTermino !== $suc['termino']) {
                    $actividades[$sucId]['inicio']  = $nuevoInicio;
                    $actividades[$sucId]['termino'] = $nuevoTermino;
                    $nuevasCambiadas[$sucId] = true;
                }
            }

            if (empty($nuevasCambiadas)) break;
            $cambiadas = $nuevasCambiadas;
        }

        // Persistir los cambios
        $movidas = [];
        $hitosTocados = [];
        foreach ($actividades as $id => $a) {
            if ($a['inicio'] !== $a['inicio_orig'] || $a['termino'] !== $a['termino_orig']) {
                $this->CI->db->where('id', $id)->update('gmc_actividades', [
                    'fecha_inicio_planificada'  => $a['inicio'],
                    'fecha_termino_planificada' => $a['termino'],
                    'updated_at'                => date('Y-m-d H:i:s'),
                ]);
                $movidas[] = [
                    'id'              => $id,
                    'codigo'          => $a['codigo'],
                    'antes_inicio'    => $a['inicio_orig'],
                    'antes_termino'   => $a['termino_orig'],
                    'despues_inicio'  => $a['inicio'],
                    'despues_termino' => $a['termino'],
                ];
                if ($a['hito_id']) $hitosTocados[$a['hito_id']] = true;
            }
        }

        // Recalcular % avance de hitos cuyas actividades se movieron (las duraciones podrían
        // haber cambiado nada acá, pero por consistencia)
        foreach (array_keys($hitosTocados) as $hitoId) {
            $this->CI->HitoRepo->recalcularAvance($hitoId);
        }

        return ['movidas' => $movidas];
    }

    /**
     * Devuelve [inicio_minimo, termino_minimo] que impone una dependencia
     * predecesor->sucesor. Cualquiera puede ser null (no impone esa cota).
     */
    private function _aplicarRestriccion(array $pred, array $suc, string $tipo, int $lag): array
    {
        $inicioMin  = null;
        $terminoMin = null;
        switch ($tipo) {
            case 'FS': // start(S) >= finish(P) + 1 día laboral + lag
                // El día siguiente laboral al término del predecesor + lag
                $inicioMin = $this->calendario->sumarDiasLaborales($pred['termino'], 1 + $lag);
                break;
            case 'SS': // start(S) >= start(P) + lag
                $inicioMin = $this->calendario->sumarDiasLaborales($pred['inicio'], $lag);
                break;
            case 'FF': // finish(S) >= finish(P) + lag
                $terminoMin = $this->calendario->sumarDiasLaborales($pred['termino'], $lag);
                break;
            case 'SF': // finish(S) >= start(P) + lag
                $terminoMin = $this->calendario->sumarDiasLaborales($pred['inicio'], $lag);
                break;
        }
        return [$inicioMin, $terminoMin];
    }

    /**
     * Dado un término y una duración (en días laborales), calcula la fecha
     * de inicio retrocediendo. Aproximación: usar duracionLaboral hacia atrás.
     */
    private function _inicioPorTermino(string $termino, int $duracion): string
    {
        if ($duracion <= 1) return $termino;
        // Retrocedemos (duracion-1) días laborales desde el término
        $cursor = $termino;
        $restantes = $duracion - 1;
        // Ir un día atrás iterando por calendario hacia el pasado
        while ($restantes > 0) {
            $cursor = date('Y-m-d', strtotime($cursor . ' -1 day'));
            if ($this->calendario->esLaboral($cursor)) {
                $restantes--;
            }
        }
        return $cursor;
    }
}
