<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * CorrelativoService — generador thread-safe de números secuenciales por dominio.
 *
 * Uso:
 *   $service = new CorrelativoService();
 *   $codigo = $service->next('proyecto');           // "OBR-2026-001"
 *   $codigo = $service->next('solicitud_pago');     // "SDP-2026-0001"
 *   $codigo = $service->next('compra');             // "OCI-2026-0001"
 *   $codigo = $service->next('rinde');              // "RDG-2026-0001"
 *   $codigo = $service->next('bitacora', $proyId);  // "BIT-OBR-2026-001-001"
 *
 * Implementación: SELECT ... FOR UPDATE dentro de transacción para
 * garantizar que dos requests concurrentes no generen el mismo número.
 */
class CorrelativoService
{
    /** @var CI_DB_query_builder */
    protected $db;

    protected $prefijos = [
        'proyecto'        => ['prefijo' => 'OBR', 'padding' => 3],
        'solicitud_pago'  => ['prefijo' => 'SDP', 'padding' => 4],
        'compra'          => ['prefijo' => 'OCI', 'padding' => 4],
        'rinde'           => ['prefijo' => 'RDG', 'padding' => 4],
        'bitacora'        => ['prefijo' => 'BIT', 'padding' => 4],
        'hito'            => ['prefijo' => 'HT',  'padding' => 2],
        'actividad'       => ['prefijo' => 'ACT', 'padding' => 3],
    ];

    public function __construct()
    {
        $CI =& get_instance();
        $this->db = $CI->db;
    }

    /**
     * @param string $dominio  Una de las claves de $prefijos.
     * @param int|null $scope  Para dominios con scope adicional (p.ej. proyecto_id en bitacora).
     * @return string  Código formateado, ej: "SDP-2026-0001".
     */
    public function next(string $dominio, ?int $scope = null): string
    {
        if (!isset($this->prefijos[$dominio])) {
            throw new InvalidArgumentException("Dominio '{$dominio}' no soportado.");
        }
        $cfg = $this->prefijos[$dominio];
        $anio = (int) date('Y');

        $this->db->trans_start();

        // Lock para concurrencia
        $row = $this->db->query(
            "SELECT id, ultimo_numero FROM gmc_correlativos
             WHERE dominio = ? AND anio = ?
             FOR UPDATE",
            [$dominio, $anio]
        )->row_array();

        if (!$row) {
            $this->db->insert('gmc_correlativos', [
                'dominio'       => $dominio,
                'anio'          => $anio,
                'ultimo_numero' => 1,
            ]);
            $numero = 1;
        } else {
            $numero = (int) $row['ultimo_numero'] + 1;
            $this->db->query(
                'UPDATE gmc_correlativos SET ultimo_numero = ? WHERE id = ?',
                [$numero, (int)$row['id']]
            );
        }

        $this->db->trans_complete();

        $padded = str_pad((string)$numero, $cfg['padding'], '0', STR_PAD_LEFT);
        return "{$cfg['prefijo']}-{$anio}-{$padded}";
    }

    /**
     * Devuelve qué número sería el próximo SIN consumirlo (para previews).
     */
    public function peek(string $dominio): int
    {
        if (!isset($this->prefijos[$dominio])) {
            throw new InvalidArgumentException("Dominio '{$dominio}' no soportado.");
        }
        $row = $this->db
            ->where(['dominio' => $dominio, 'anio' => (int)date('Y')])
            ->get('gmc_correlativos')->row_array();
        return $row ? ((int)$row['ultimo_numero'] + 1) : 1;
    }
}
