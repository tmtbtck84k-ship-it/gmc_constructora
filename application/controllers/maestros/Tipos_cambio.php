<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Tipos_cambio extends MY_AuthController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model(['MonedaRepo','TipoCambioRepo']);
    }

    public function index()
    {
        $this->require_permission('maestros.tipo_cambio.ver');

        $anio = (int)($this->input->get('anio') ?: date('Y'));
        $mes  = (int)($this->input->get('mes')  ?: date('n'));

        $monedasNoClp = array_values(array_filter(
            $this->MonedaRepo->activas(),
            fn($m) => $m['codigo'] !== 'CLP'
        ));

        $rows = $this->TipoCambioRepo->pivotPorMes($anio, $mes);

        // Último TC por moneda (panel derecho)
        $hoy = date('Y-m-d');
        $vigentes = [];
        foreach ($monedasNoClp as $m) {
            $tc = $this->TipoCambioRepo->vigente((int)$m['id'], $hoy);
            $vigentes[$m['codigo']] = $tc ? (float)$tc['valor_clp'] : null;
        }

        $this->view('maestros/tipos_cambio/index', [
            'anio'      => $anio,
            'mes'       => $mes,
            'monedas'   => $monedasNoClp,
            'rows'      => $rows,
            'vigentes'  => $vigentes,
        ]);
    }

    public function cargar()
    {
        $this->require_permission('maestros.tipo_cambio.editar');

        if ($this->input->method() !== 'post') {
            $monedasNoClp = array_values(array_filter(
                $this->MonedaRepo->activas(),
                fn($m) => $m['codigo'] !== 'CLP'
            ));
            return $this->view('maestros/tipos_cambio/cargar', [
                'monedas' => $monedasNoClp,
                'fecha'   => date('Y-m-d'),
            ]);
        }

        $fecha = $this->input->post('fecha');
        if (!$fecha || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            $this->flash('error', 'Fecha inválida.');
            redirect(base_url('maestros/tipos-cambio/cargar'));
        }

        $valores = (array)$this->input->post('valor');  // ['UF' => 39729, 'USD' => 950, ...]
        $cargados = 0;
        foreach ($valores as $codigo => $valor) {
            if ($valor === '' || !is_numeric($valor)) continue;
            $valor = (float)$valor;
            if ($valor <= 0) continue;
            $moneda = $this->MonedaRepo->findByCodigo($codigo);
            if (!$moneda) continue;
            $this->TipoCambioRepo->upsert((int)$moneda['id'], $fecha, $valor, 'manual', $this->user_id());
            $cargados++;
        }
        $this->audit->log('tipo_cambio.cargar', 'gmc_tipos_cambio', null, null, [
            'fecha' => $fecha, 'valores' => $valores,
        ]);
        $this->flash('success', "Tipos de cambio actualizados ({$cargados} valores) para {$fecha}.");
        redirect(base_url('maestros/tipos-cambio?anio=' . substr($fecha,0,4) . '&mes=' . (int)substr($fecha,5,2)));
    }
}
