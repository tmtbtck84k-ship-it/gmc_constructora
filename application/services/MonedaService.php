<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * MonedaService — conversión a CLP (moneda funcional) con snapshot de TC.
 *
 * Reglas:
 *   - Para CLP: tipo_cambio_clp = NULL, monto_total_clp = monto_total.
 *   - Para UF/USD/EUR/...: busca el TC vigente (último con fecha <= referencia)
 *     y devuelve monto_total_clp = monto_total * tipo_cambio_clp.
 *   - Si NO hay TC para esa moneda en esa fecha o anterior, lanza excepción
 *     (mejor fallar al crear que generar un cálculo inválido).
 */
class MonedaService
{
    protected $CI;

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->model(['MonedaRepo','TipoCambioRepo']);
    }

    /**
     * Convierte un monto en una moneda dada a CLP en una fecha dada.
     *
     * @return array{tipo_cambio_clp:?float, monto_total_clp:float}
     */
    public function aClp(int $monedaId, float $monto, string $fecha): array
    {
        $moneda = $this->CI->MonedaRepo->find($monedaId);
        if (!$moneda) throw new RuntimeException("Moneda id={$monedaId} no existe.");

        if ($moneda['codigo'] === 'CLP') {
            return ['tipo_cambio_clp' => null, 'monto_total_clp' => round($monto, 2)];
        }

        $tc = $this->CI->TipoCambioRepo->vigente($monedaId, $fecha);
        if (!$tc) {
            throw new RuntimeException("No hay tipo de cambio cargado para {$moneda['codigo']} con fecha <= {$fecha}. Carga el TC en Maestros → Tipos de Cambio antes de continuar.");
        }
        $valor = (float)$tc['valor_clp'];
        return [
            'tipo_cambio_clp' => $valor,
            'monto_total_clp' => round($monto * $valor, 2),
        ];
    }

    /**
     * Atajo para usar al crear/editar SDP, compras y rindes.
     * Devuelve un array para fusionar con el payload.
     */
    public function snapshot(int $monedaId, float $montoTotal, string $fechaReferencia): array
    {
        return $this->aClp($monedaId, $montoTotal, $fechaReferencia);
    }
}
