<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Gantt extends MY_AuthController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model(['ProyectoRepo','HitoRepo','ActividadRepo','DependenciaRepo','FeriadoRepo']);
    }

    /**
     * Exporta el Gantt a PDF (mPDF). Genera una vista server-side con tabla
     * de actividades + línea de tiempo simplificada. PNG queda en cliente
     * con html2canvas.
     */
    public function pdf(int $proyectoId)
    {
        $this->require_permission('obras.gantt.exportar');
        $proyecto = $this->ProyectoRepo->findFull($proyectoId);
        if (!$proyecto) show_404();
        $hitos       = $this->HitoRepo->listByProyecto($proyectoId);
        $actividades = $this->ActividadRepo->listByProyecto($proyectoId);
        $deps        = $this->DependenciaRepo->listByProyecto($proyectoId);

        $this->load->library('Pdf');
        $html = $this->load->view('obras/gantt/pdf', [
            'proyecto'    => $proyecto,
            'hitos'       => $hitos,
            'actividades' => $actividades,
            'deps'        => $deps,
            'fecha'       => date('Y-m-d H:i'),
        ], true);
        $this->pdf->render($html, [
            'filename'    => 'gantt-' . $proyecto['codigo'] . '-' . date('Ymd') . '.pdf',
            'orientation' => 'L',
            'format'      => 'A3',
        ]);
    }

    /** Reporte avance vs planificado con semáforo. */
    public function reporte(int $proyectoId)
    {
        $this->require_permission('obras.gantt.ver');
        $proyecto = $this->ProyectoRepo->findFull($proyectoId);
        if (!$proyecto) show_404();
        $actividades = $this->ActividadRepo->listByProyecto($proyectoId);

        $hoy = date('Y-m-d');
        $rows = [];
        foreach ($actividades as $a) {
            $inicio  = $a['fecha_inicio_planificada'];
            $termino = $a['fecha_termino_planificada'];
            $dur     = max(1, (int)$a['duracion_dias']);

            // % planificado al día de hoy: regla lineal sobre la duración
            $planAlDia = 0;
            if ($hoy <= $inicio)       $planAlDia = 0;
            elseif ($hoy >= $termino)  $planAlDia = 100;
            else {
                $totalDias = (strtotime($termino) - strtotime($inicio)) / 86400 + 1;
                $diasPasados = (strtotime($hoy) - strtotime($inicio)) / 86400 + 1;
                $planAlDia = round(($diasPasados / $totalDias) * 100, 1);
            }
            $real = (float)$a['porcentaje_avance'];
            $desv = $real - $planAlDia; // negativo = atrasado

            // Semáforo: verde >= 0, amarillo entre -10 y 0, rojo < -10 o terminada vencida
            $sem = 'verde';
            if ($desv < -10) $sem = 'rojo';
            elseif ($desv < 0) $sem = 'amarillo';
            if ($real < 100 && $termino < $hoy) $sem = 'rojo';

            $diasDesvFecha = 0;
            if ($real >= 100 && !empty($a['fecha_termino_real'])) {
                $diasDesvFecha = (strtotime($a['fecha_termino_real']) - strtotime($termino)) / 86400;
            } elseif ($real < 100 && $termino < $hoy) {
                $diasDesvFecha = (strtotime($hoy) - strtotime($termino)) / 86400;
            }

            $rows[] = $a + [
                'plan_al_dia'      => $planAlDia,
                'desviacion_pct'   => round($desv, 1),
                'desviacion_dias'  => (int)$diasDesvFecha,
                'semaforo'         => $sem,
            ];
        }

        $this->view('obras/gantt/reporte', [
            'proyecto'    => $proyecto,
            'rows'        => $rows,
            'hoy'         => $hoy,
        ]);
    }

    /** AJAX POST: fuerza recálculo CPM (ruta crítica + holguras). */
    public function recalcular_cpm(int $proyectoId)
    {
        $this->require_permission('obras.gantt.editar');
        try {
            require_once APPPATH . 'services/CpmService.php';
            $cpm = new CpmService($proyectoId);
            $r = $cpm->recalcular();
            return $this->json([
                'ok' => true,
                'criticas'          => $r['criticas'],
                'duracion_proyecto' => $r['duracion_proyecto'],
            ]);
        } catch (Throwable $e) {
            return $this->json(['ok' => false, 'error' => $e->getMessage()], 422);
        }
    }

    /** Vista Gantt: si llega proyecto_id, renderiza Frappe Gantt. */
    public function index()
    {
        $this->require_permission('obras.gantt.ver');
        $proyectoId = (int)$this->input->get('proyecto_id');
        $proyectos  = $this->ProyectoRepo->activos();

        $proyecto = null;
        if ($proyectoId) {
            $proyecto = $this->ProyectoRepo->findFull($proyectoId);
            if (!$proyecto) show_404();
        }

        $this->view('obras/gantt/index', [
            'proyecto'  => $proyecto,
            'proyectos' => $proyectos,
        ]);
    }

    /**
     * AJAX: devuelve los datos del Gantt en formato Frappe Gantt + nuestras
     * extensiones (hitos, feriados, info para el panel lateral).
     *
     * GET /obras/gantt/data/{proyecto_id}
     */
    public function data(int $proyectoId)
    {
        $this->require_permission('obras.gantt.ver');
        $proyecto = $this->ProyectoRepo->findFull($proyectoId);
        if (!$proyecto) return $this->json(['ok'=>false,'error'=>'Proyecto no encontrado.'], 404);

        $hitos = $this->HitoRepo->listByProyecto($proyectoId);
        // map hito_id => color (paleta cíclica)
        $paleta = ['#0d6efd','#6f42c1','#fd7e14','#198754','#dc3545','#20c997','#6610f2','#e83e8c'];
        $colorByHito = [];
        foreach ($hitos as $idx => $h) {
            $colorByHito[(int)$h['id']] = $paleta[$idx % count($paleta)];
        }

        $actividades = $this->ActividadRepo->listByProyecto($proyectoId);
        $tasks = [];
        foreach ($actividades as $a) {
            $color = $colorByHito[(int)$a['hito_id']] ?? '#6c757d';
            if ((int)$a['es_critica'] === 1) $color = '#dc3545'; // rojo crítica
            $tasks[] = [
                'id'           => (string)$a['id'],
                'name'         => $a['codigo'] . ' · ' . $a['nombre'],
                'start'        => $a['fecha_inicio_planificada'],
                'end'          => $a['fecha_termino_planificada'],
                'progress'     => (float)$a['porcentaje_avance'],
                'duration'     => (int)$a['duracion_dias'],
                'hito_id'      => $a['hito_id'] ? (int)$a['hito_id'] : null,
                'hito_codigo'  => $a['hito_codigo'] ?? null,
                'es_critica'   => (int)$a['es_critica'],
                'responsable'  => $a['responsable_nombre'] ?? null,
                'custom_class' => 'g-bar-' . ((int)$a['es_critica'] === 1 ? 'critica' : 'normal'),
                'color'        => $color,
            ];
        }

        // Dependencias: Frappe Gantt usa "dependencies" como string CSV de IDs predecesores
        $deps = $this->DependenciaRepo->listByProyecto($proyectoId);
        $depMap = [];
        foreach ($deps as $d) {
            $depMap[(int)$d['actividad_id']][] = (int)$d['predecesor_id'];
        }
        foreach ($tasks as &$t) {
            $sucId = (int)$t['id'];
            $t['dependencies'] = isset($depMap[$sucId]) ? implode(',', $depMap[$sucId]) : '';
        }
        unset($t);

        // Feriados como rango: del mínimo al máximo de las actividades
        $minDate = null; $maxDate = null;
        foreach ($actividades as $a) {
            if (!$minDate || $a['fecha_inicio_planificada'] < $minDate) $minDate = $a['fecha_inicio_planificada'];
            if (!$maxDate || $a['fecha_termino_planificada'] > $maxDate) $maxDate = $a['fecha_termino_planificada'];
        }
        $feriados = [];
        if ($minDate && $maxDate) {
            $feriados = array_keys($this->FeriadoRepo->rangoSet($minDate, $maxDate));
        }

        // Stats CPM
        $criticas = 0; $maxHolgura = 0;
        foreach ($actividades as $a) {
            if ((int)$a['es_critica'] === 1) $criticas++;
            if ((int)$a['holgura_dias'] > $maxHolgura) $maxHolgura = (int)$a['holgura_dias'];
        }

        return $this->json([
            'ok'           => true,
            'cpm_stats'    => ['criticas' => $criticas, 'max_holgura' => $maxHolgura],
            'proyecto'     => [
                'id'                 => (int)$proyecto['id'],
                'codigo'             => $proyecto['codigo'],
                'nombre'             => $proyecto['nombre'],
                'dias_laborales'     => $proyecto['dias_laborales'] ?? 'lun_vie',
                'dias_laborales_custom' => $proyecto['dias_laborales_custom'] ?? null,
                'trabaja_feriados'   => (int)($proyecto['trabaja_feriados'] ?? 0),
                'estado'             => $proyecto['estado'] ?? null,
            ],
            'hitos'        => array_map(function($h) use ($colorByHito) {
                return [
                    'id'            => (int)$h['id'],
                    'codigo'        => $h['codigo'],
                    'nombre'        => $h['nombre'],
                    'fecha_objetivo'=> $h['fecha_objetivo'],
                    'porcentaje'    => (float)$h['porcentaje_avance'],
                    'completado'    => (int)$h['completado'],
                    'color'         => $colorByHito[(int)$h['id']] ?? '#6c757d',
                ];
            }, $hitos),
            'tasks'        => $tasks,
            'dependencies' => $deps,
            'feriados'     => $feriados,
        ]);
    }
}
