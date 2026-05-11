<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'services/CalendarioService.php';

/**
 * CpmService — Critical Path Method.
 *
 * Calcula para cada actividad de un proyecto:
 *   ES (Early Start)    : la fecha más temprana en que puede arrancar
 *   EF (Early Finish)   : ES + duración (en días laborales del proyecto)
 *   LS (Late Start)     : la fecha más tardía sin demorar el proyecto
 *   LF (Late Finish)    : LS + duración
 *   slack (holgura)     : LS - ES (en días laborales). Si 0 -> crítica.
 *
 * Persiste `es_critica` y `holgura_dias` en gmc_actividades.
 *
 * Sólo considera dependencias FS (Finish-to-Start) para CPM clásico.
 * Las otras dependencias (SS, FF, SF) restringen fechas pero el cálculo de
 * holgura sigue la lógica FS — es la convención para no complicar la
 * interpretación del usuario final.
 */
class CpmService
{
    /** @var CI_Controller */
    protected $CI;
    /** @var CalendarioService */
    protected $calendario;
    /** @var int */
    protected $proyectoId;

    public function __construct(int $proyectoId)
    {
        $this->CI =& get_instance();
        $this->CI->load->model(['ProyectoRepo','ActividadRepo','DependenciaRepo']);
        $proyecto = $this->CI->ProyectoRepo->find($proyectoId);
        if (!$proyecto) throw new RuntimeException('Proyecto no encontrado.');
        $this->calendario = new CalendarioService($proyecto);
        $this->proyectoId = $proyectoId;
    }

    /**
     * Recalcula CPM para todo el proyecto.
     *
     * @return array  ['actividades' => [...], 'duracion_proyecto' => int, 'criticas' => int]
     */
    public function recalcular(): array
    {
        $acts = [];
        $rows = $this->CI->db
            ->select('id, codigo, fecha_inicio_planificada, fecha_termino_planificada, duracion_dias')
            ->where('proyecto_id', $this->proyectoId)
            ->where('deleted_at IS NULL', null, false)
            ->get('gmc_actividades')->result_array();
        if (!$rows) return ['actividades' => [], 'duracion_proyecto' => 0, 'criticas' => 0];

        foreach ($rows as $r) {
            $acts[(int)$r['id']] = [
                'id'       => (int)$r['id'],
                'codigo'   => $r['codigo'],
                'inicio'   => $r['fecha_inicio_planificada'],
                'termino'  => $r['fecha_termino_planificada'],
                'duracion' => (int)$r['duracion_dias'],
                'preds'    => [],
                'sucs'     => [],
                'es' => null, 'ef' => null, 'ls' => null, 'lf' => null,
                'slack' => 0, 'critica' => false,
            ];
        }

        // Cargar dependencias FS (las clásicas para CPM)
        $deps = $this->CI->db
            ->select('d.actividad_id, d.predecesor_id, d.lag_dias')
            ->from('gmc_actividad_dependencias d')
            ->join('gmc_actividades a', 'a.id = d.actividad_id')
            ->where('a.proyecto_id', $this->proyectoId)
            ->where('a.deleted_at IS NULL', null, false)
            ->where('d.tipo', 'FS')
            ->get()->result_array();
        foreach ($deps as $d) {
            $sucId  = (int)$d['actividad_id'];
            $predId = (int)$d['predecesor_id'];
            if (!isset($acts[$sucId]) || !isset($acts[$predId])) continue;
            $acts[$sucId]['preds'][]  = ['id' => $predId, 'lag' => (int)$d['lag_dias']];
            $acts[$predId]['sucs'][]  = ['id' => $sucId,  'lag' => (int)$d['lag_dias']];
        }

        // ============ FORWARD PASS ============
        // ES de actividad sin predecesores = su fecha_inicio_planificada
        // ES con predecesores = max(EF predecesor + lag + 1 día laboral)
        // EF = ES + (duracion - 1) días laborales (porque la duración es inclusiva)
        $orden = $this->_ordenTopologico($acts);
        foreach ($orden as $id) {
            $a =& $acts[$id];
            if (empty($a['preds'])) {
                $a['es'] = $a['inicio'];
            } else {
                $maxStart = null;
                foreach ($a['preds'] as $p) {
                    $pred = $acts[$p['id']];
                    if (!$pred['ef']) continue;
                    // start = ef del predecesor + lag + 1 día laboral
                    $candidato = $this->calendario->sumarDiasLaborales($pred['ef'], 1 + $p['lag']);
                    if (!$maxStart || $candidato > $maxStart) $maxStart = $candidato;
                }
                $a['es'] = $maxStart ?: $a['inicio'];
            }
            $a['ef'] = $this->calendario->calcularTermino($a['es'], $a['duracion']);
            unset($a);
        }

        // Duración total del proyecto = max(EF) entre todas las actividades
        $endProyecto = null;
        foreach ($acts as $a) {
            if ($a['ef'] && (!$endProyecto || $a['ef'] > $endProyecto)) $endProyecto = $a['ef'];
        }

        // ============ BACKWARD PASS ============
        // LF de actividad sin sucesores = end del proyecto
        // LF con sucesores = min(LS sucesor - lag - 1 día laboral)
        // LS = LF - (duracion - 1) días laborales
        $ordenInverso = array_reverse($orden);
        foreach ($ordenInverso as $id) {
            $a =& $acts[$id];
            if (empty($a['sucs'])) {
                $a['lf'] = $endProyecto;
            } else {
                $minFinish = null;
                foreach ($a['sucs'] as $s) {
                    $suc = $acts[$s['id']];
                    if (!$suc['ls']) continue;
                    $candidato = $this->_restarDiasLaborales($suc['ls'], 1 + $s['lag']);
                    if (!$minFinish || $candidato < $minFinish) $minFinish = $candidato;
                }
                $a['lf'] = $minFinish ?: $endProyecto;
            }
            $a['ls'] = $this->_inicioPorTermino($a['lf'], $a['duracion']);
            unset($a);
        }

        // ============ HOLGURA + CRITICAS ============
        $criticas = 0;
        foreach ($acts as &$a) {
            $slack = $this->calendario->duracionLaboral($a['es'], $a['ls']) - 1;
            if ($a['es'] === $a['ls']) $slack = 0;
            if ($slack < 0) $slack = 0;
            $a['slack']   = $slack;
            $a['critica'] = ($slack === 0);
            if ($a['critica']) $criticas++;
        }
        unset($a);

        // Persistir
        foreach ($acts as $a) {
            $this->CI->db->where('id', $a['id'])->update('gmc_actividades', [
                'es_critica'    => $a['critica'] ? 1 : 0,
                'holgura_dias'  => $a['slack'],
                'updated_at'    => date('Y-m-d H:i:s'),
            ]);
        }

        // Duración total en días laborales del proyecto
        $duracionProyecto = 0;
        if ($acts) {
            $minStart = null;
            foreach ($acts as $a) {
                if (!$minStart || ($a['es'] && $a['es'] < $minStart)) $minStart = $a['es'];
            }
            if ($minStart && $endProyecto) {
                $duracionProyecto = $this->calendario->duracionLaboral($minStart, $endProyecto);
            }
        }

        return [
            'actividades'       => array_values($acts),
            'duracion_proyecto' => $duracionProyecto,
            'criticas'          => $criticas,
        ];
    }

    /**
     * Devuelve los IDs ordenados topológicamente (predecesores antes que sucesores).
     */
    private function _ordenTopologico(array $acts): array
    {
        $inDegree = [];
        foreach ($acts as $id => $a) $inDegree[$id] = count($a['preds']);
        $queue = [];
        foreach ($inDegree as $id => $deg) {
            if ($deg === 0) $queue[] = $id;
        }
        $orden = [];
        while ($queue) {
            $id = array_shift($queue);
            $orden[] = $id;
            foreach ($acts[$id]['sucs'] as $s) {
                $inDegree[$s['id']]--;
                if ($inDegree[$s['id']] === 0) $queue[] = $s['id'];
            }
        }
        // Si quedaron actividades sin procesar (ciclo), las agregamos al final
        foreach (array_keys($acts) as $id) {
            if (!in_array($id, $orden, true)) $orden[] = $id;
        }
        return $orden;
    }

    private function _restarDiasLaborales(string $fecha, int $n): string
    {
        $cursor = $fecha;
        while ($n > 0) {
            $cursor = date('Y-m-d', strtotime($cursor . ' -1 day'));
            if ($this->calendario->esLaboral($cursor)) $n--;
        }
        return $cursor;
    }

    private function _inicioPorTermino(string $termino, int $duracion): string
    {
        if ($duracion <= 1) return $termino;
        $cursor = $termino;
        $restantes = $duracion - 1;
        while ($restantes > 0) {
            $cursor = date('Y-m-d', strtotime($cursor . ' -1 day'));
            if ($this->calendario->esLaboral($cursor)) $restantes--;
        }
        return $cursor;
    }
}
