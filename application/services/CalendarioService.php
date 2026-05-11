<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * CalendarioService — cálculo de fechas considerando días laborales del proyecto + feriados.
 *
 * Se inicializa con un proyecto (que define dias_laborales y trabaja_feriados).
 * Si el proyecto tiene dias_laborales='personalizado', usa el campo
 * dias_laborales_custom (formato "L,Mi,V,S").
 *
 * Métodos clave:
 *   - esLaboral($fecha)           → bool
 *   - sumarDiasLaborales($fecha, $n) → fecha N días laborales después (inclusivo si n>=1)
 *   - duracionLaboral($d1, $d2)   → cantidad de días laborales entre dos fechas (inclusivo)
 *   - siguienteLaboral($fecha)    → próxima fecha laboral si la dada no lo es
 */
class CalendarioService
{
    /** @var array<int,bool>  Map de día de la semana 1=Lun..7=Dom => esLaboral */
    protected $diasSemana = [];
    /** @var array<string,bool> Map fecha => esFeriado */
    protected $feriadosCache = [];
    /** @var bool */
    protected $trabajaFeriados = false;
    /** @var int */
    protected $proyectoId;
    /** @var \CI_DB_query_builder */
    protected $db;

    /**
     * @param array $proyecto  Row de gmc_proyectos con dias_laborales, dias_laborales_custom, trabaja_feriados
     */
    public function __construct(array $proyecto)
    {
        $CI =& get_instance();
        $this->db = $CI->db;
        $this->proyectoId      = (int)$proyecto['id'];
        $this->trabajaFeriados = (bool)($proyecto['trabaja_feriados'] ?? 0);
        $this->_armarDiasSemana(
            $proyecto['dias_laborales'] ?? 'lun_vie',
            $proyecto['dias_laborales_custom'] ?? null
        );
    }

    /** Atajo desde $proyectoId; carga el row internamente. */
    public static function desdeProyecto(int $proyectoId): self
    {
        $CI =& get_instance();
        $p = $CI->db->where('id', $proyectoId)->get('gmc_proyectos')->row_array();
        if (!$p) throw new RuntimeException("Proyecto id={$proyectoId} no existe.");
        return new self($p);
    }

    public function esLaboral(string $fecha): bool
    {
        $dow = (int)date('N', strtotime($fecha));   // 1=Lun..7=Dom
        if (empty($this->diasSemana[$dow])) return false;
        if (!$this->trabajaFeriados && $this->_esFeriado($fecha)) return false;
        return true;
    }

    /**
     * Devuelve la fecha laboral n+1 (sumar n días laborales).
     * Si $fecha es laboral, cuenta como día 0; el resultado es n días después.
     * Si $fecha NO es laboral, primero salta a la próxima laboral y luego suma n.
     */
    public function sumarDiasLaborales(string $fecha, int $n): string
    {
        $cursor = strtotime($fecha);
        // Saltar a próximo laboral si no lo es
        while (!$this->esLaboral(date('Y-m-d', $cursor))) {
            $cursor = strtotime('+1 day', $cursor);
        }
        $contador = 0;
        while ($contador < $n) {
            $cursor = strtotime('+1 day', $cursor);
            if ($this->esLaboral(date('Y-m-d', $cursor))) {
                $contador++;
            }
        }
        return date('Y-m-d', $cursor);
    }

    /**
     * Cuenta días laborales entre $d1 y $d2 (ambos inclusivos).
     * Si $d2 < $d1 retorna 0.
     */
    public function duracionLaboral(string $d1, string $d2): int
    {
        $a = strtotime($d1);
        $b = strtotime($d2);
        if ($a > $b) return 0;
        $count = 0;
        while ($a <= $b) {
            if ($this->esLaboral(date('Y-m-d', $a))) $count++;
            $a = strtotime('+1 day', $a);
        }
        return $count;
    }

    public function siguienteLaboral(string $fecha): string
    {
        $cursor = strtotime($fecha);
        while (!$this->esLaboral(date('Y-m-d', $cursor))) {
            $cursor = strtotime('+1 day', $cursor);
        }
        return date('Y-m-d', $cursor);
    }

    /**
     * Calcula fecha de término dado un inicio y duración en días laborales.
     * Ej: si arranca lunes y dura 5 días laborales (L-V), termina viernes.
     */
    public function calcularTermino(string $fechaInicio, int $duracionDias): string
    {
        if ($duracionDias <= 0) return $fechaInicio;
        // Equivale a sumar (duracion - 1) porque el día de inicio cuenta como día 1.
        return $this->sumarDiasLaborales($fechaInicio, $duracionDias - 1);
    }

    // -------------------- internos --------------------

    private function _armarDiasSemana(string $dias, ?string $custom): void
    {
        // Reset
        $this->diasSemana = [1=>false,2=>false,3=>false,4=>false,5=>false,6=>false,7=>false];

        switch ($dias) {
            case 'lun_vie':
                $this->diasSemana[1]=$this->diasSemana[2]=$this->diasSemana[3]=$this->diasSemana[4]=$this->diasSemana[5]=true;
                break;
            case 'lun_sab':
                for ($i=1;$i<=6;$i++) $this->diasSemana[$i]=true;
                break;
            case 'lun_dom':
                for ($i=1;$i<=7;$i++) $this->diasSemana[$i]=true;
                break;
            case 'personalizado':
                $map = ['L'=>1,'Ma'=>2,'Mi'=>3,'J'=>4,'V'=>5,'S'=>6,'D'=>7];
                foreach (explode(',', (string)$custom) as $d) {
                    $d = trim($d);
                    if (isset($map[$d])) $this->diasSemana[$map[$d]] = true;
                }
                break;
        }
    }

    private function _esFeriado(string $fecha): bool
    {
        if (isset($this->feriadosCache[$fecha])) return $this->feriadosCache[$fecha];
        $found = $this->db->where('fecha', $fecha)->count_all_results('gmc_feriados') > 0;
        return $this->feriadosCache[$fecha] = $found;
    }
}
