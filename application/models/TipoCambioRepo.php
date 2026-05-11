<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'core/MY_Model.php';

class TipoCambioRepo extends MY_Model
{
    protected $table = 'gmc_tipos_cambio';
    protected $useSoftDelete = false;
    protected $useTimestamps = false;
    protected $useAudit = false;
    protected $fillable = ['moneda_id','fecha','valor_clp','origen','actualizado_por','actualizado_at'];

    /**
     * TC vigente para una moneda en una fecha dada.
     * Devuelve el último TC con fecha <= $fecha. Si no existe, retorna null.
     */
    public function vigente(int $monedaId, string $fecha): ?array
    {
        return $this->db
            ->where('moneda_id', $monedaId)
            ->where('fecha <=', $fecha)
            ->order_by('fecha', 'DESC')
            ->limit(1)
            ->get($this->table)->row_array() ?: null;
    }

    public function vigenteByCodigo(string $codigoMoneda, string $fecha): ?array
    {
        $moneda = $this->db->where('codigo', $codigoMoneda)->get('gmc_monedas')->row_array();
        if (!$moneda) return null;
        return $this->vigente((int)$moneda['id'], $fecha);
    }

    /**
     * Obtiene el listado pivotado por mes:
     * fecha, UF, USD, EUR, ...
     */
    public function pivotPorMes(int $anio, int $mes): array
    {
        $sql = "SELECT tc.fecha, m.codigo, tc.valor_clp, tc.origen
                FROM gmc_tipos_cambio tc
                JOIN gmc_monedas m ON m.id = tc.moneda_id
                WHERE YEAR(tc.fecha) = ? AND MONTH(tc.fecha) = ?
                ORDER BY tc.fecha DESC, m.codigo";
        $rows = $this->db->query($sql, [$anio, $mes])->result_array();

        // Pivot: agrupa por fecha y arma columnas por moneda con valor + origen
        $pivot = [];
        foreach ($rows as $r) {
            $f = $r['fecha'];
            if (!isset($pivot[$f])) $pivot[$f] = ['fecha' => $f];
            $pivot[$f][$r['codigo']] = [
                'valor'  => $r['valor_clp'],
                'origen' => $r['origen'] ?? 'manual',
            ];
        }
        return array_values($pivot);
    }

    public function findByMonedaFecha(int $monedaId, string $fecha): ?array
    {
        return $this->firstBy(['moneda_id' => $monedaId, 'fecha' => $fecha]);
    }

    /**
     * Upsert respetuoso de origen.
     *
     * @param string $origen 'manual' o 'auto'
     * @param int|null $usuarioId  Usuario que ejecuta (NULL para 'auto')
     * @return array  ['id'=>int, 'accion'=>'creado|actualizado|preservado_manual']
     */
    public function upsert(int $monedaId, string $fecha, float $valor, string $origen = 'manual', ?int $usuarioId = null): array
    {
        $existing = $this->findByMonedaFecha($monedaId, $fecha);
        $now = date('Y-m-d H:i:s');

        if ($existing) {
            // Regla: 'auto' NO sobreescribe un TC 'manual' existente.
            if ($origen === 'auto' && ($existing['origen'] ?? 'manual') === 'manual') {
                return ['id' => (int)$existing['id'], 'accion' => 'preservado_manual'];
            }

            $this->update((int)$existing['id'], [
                'valor_clp'        => $valor,
                'origen'           => $origen,
                'actualizado_por'  => $usuarioId,
                'actualizado_at'   => $now,
            ]);
            return ['id' => (int)$existing['id'], 'accion' => 'actualizado'];
        }

        $id = $this->create([
            'moneda_id'        => $monedaId,
            'fecha'            => $fecha,
            'valor_clp'        => $valor,
            'origen'           => $origen,
            'actualizado_por'  => $usuarioId,
            'actualizado_at'   => $now,
        ]);
        return ['id' => $id, 'accion' => 'creado'];
    }
}
