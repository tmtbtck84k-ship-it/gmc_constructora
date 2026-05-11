<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Mindicador — cliente HTTP para mindicador.cl
 * Provee tipos de cambio y otros indicadores económicos chilenos
 * basados en datos publicados por el Banco Central de Chile.
 *
 * API pública, sin autenticación.
 *   GET https://mindicador.cl/api          → todos los indicadores del día
 *   GET https://mindicador.cl/api/uf       → serie histórica UF (último año)
 *   GET https://mindicador.cl/api/dolar    → serie histórica USD
 *
 * Documentación: https://mindicador.cl/api/
 */
class Mindicador
{
    /** @var string */
    protected $baseUrl = 'https://mindicador.cl/api';
    /** @var int Timeout en segundos */
    protected $timeout = 10;

    /** Mapa: código en mindicador → código de moneda en gmc_monedas */
    protected $mapa = [
        'uf'     => 'UF',
        'dolar'  => 'USD',
        'euro'   => 'EUR',
    ];

    /**
     * Devuelve los TC del día actual desde mindicador.cl.
     *
     * @return array  ['UF' => ['valor'=>39729.42, 'fecha'=>'2026-05-02'], 'USD' => [...], 'EUR' => [...]]
     * @throws RuntimeException si la llamada HTTP falla.
     */
    public function indicadoresHoy(): array
    {
        $json = $this->_get($this->baseUrl);
        $data = json_decode($json, true);
        if (!is_array($data)) {
            throw new RuntimeException('Respuesta de mindicador.cl inválida (JSON malformado).');
        }

        $out = [];
        foreach ($this->mapa as $apiKey => $codigoMoneda) {
            if (!isset($data[$apiKey]['valor']) || !isset($data[$apiKey]['fecha'])) {
                // No bloqueamos por una moneda faltante; saltamos
                continue;
            }
            $fecha = substr($data[$apiKey]['fecha'], 0, 10); // ISO datetime → YYYY-MM-DD
            $out[$codigoMoneda] = [
                'valor' => (float)$data[$apiKey]['valor'],
                'fecha' => $fecha,
            ];
        }
        if (!$out) {
            throw new RuntimeException('mindicador.cl devolvió respuesta sin indicadores reconocibles.');
        }
        return $out;
    }

    /**
     * Serie histórica de UF (últimos 30+ días). Útil para llenar huecos.
     *
     * @return array  ['2026-05-02' => 39729.42, '2026-05-01' => 39712.85, ...]
     */
    public function serieHistorica(string $codigoMonedaApi): array
    {
        $json = $this->_get($this->baseUrl . '/' . $codigoMonedaApi);
        $data = json_decode($json, true);
        if (!isset($data['serie']) || !is_array($data['serie'])) {
            throw new RuntimeException("Serie histórica de '{$codigoMonedaApi}' inválida.");
        }
        $out = [];
        foreach ($data['serie'] as $row) {
            $f = substr($row['fecha'] ?? '', 0, 10);
            if (!$f || !isset($row['valor'])) continue;
            $out[$f] = (float)$row['valor'];
        }
        return $out;
    }

    // ----------------- Internos -----------------

    private function _get(string $url): string
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_USERAGENT      => 'GMC-ERP/1.0 (sync-tc)',
            CURLOPT_HTTPHEADER     => ['Accept: application/json'],
        ]);
        $body = curl_exec($ch);
        $errno = curl_errno($ch);
        $error = $errno ? curl_error($ch) : '';
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($errno) {
            throw new RuntimeException("Error de conexión con mindicador.cl: {$error}");
        }
        if ($code !== 200) {
            throw new RuntimeException("mindicador.cl respondió HTTP {$code}.");
        }
        if (!is_string($body) || $body === '') {
            throw new RuntimeException("mindicador.cl respondió cuerpo vacío.");
        }
        return $body;
    }
}
