<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'core/MY_Model.php';

class CompraItemRepo extends MY_Model
{
    protected $table = 'gmc_compras_items';
    protected $useSoftDelete = false;
    protected $useTimestamps = false;
    protected $useAudit = false;
    protected $fillable = ['compra_id','tipo_gasto_id','descripcion','cantidad','unidad','precio_unitario','total_linea'];

    public function listByCompra(int $compraId): array
    {
        return $this->db
            ->select('ci.*, tg.codigo AS tg_codigo, tg.nombre AS tg_nombre')
            ->from($this->table . ' ci')
            ->join('gmc_tipos_gasto tg', 'tg.id = ci.tipo_gasto_id', 'left')
            ->where('ci.compra_id', $compraId)
            ->order_by('ci.id', 'ASC')
            ->get()->result_array();
    }

    public function deleteByCompra(int $compraId): void
    {
        $this->db->where('compra_id', $compraId)->delete($this->table);
    }

    /** Reemplaza el set completo de items con los provistos. */
    public function syncItems(int $compraId, array $items): float
    {
        $this->deleteByCompra($compraId);
        $totalNeto = 0.0;
        foreach ($items as $it) {
            $cantidad = (float)($it['cantidad'] ?? 0);
            $precio   = (float)($it['precio_unitario'] ?? 0);
            $total    = round($cantidad * $precio, 2);
            $this->create([
                'compra_id'       => $compraId,
                'tipo_gasto_id'   => !empty($it['tipo_gasto_id']) ? (int)$it['tipo_gasto_id'] : null,
                'descripcion'     => trim((string)($it['descripcion'] ?? '')),
                'cantidad'        => $cantidad,
                'unidad'          => trim((string)($it['unidad'] ?? '')) ?: null,
                'precio_unitario' => $precio,
                'total_linea'     => $total,
            ]);
            $totalNeto += $total;
        }
        return $totalNeto;
    }
}
