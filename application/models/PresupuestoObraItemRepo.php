<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'core/MY_Model.php';

class PresupuestoObraItemRepo extends MY_Model
{
    protected $table = 'gmc_presupuestos_obra_items';
    protected $useSoftDelete = false;
    protected $useTimestamps = false;
    protected $useAudit = false;
    protected $fillable = ['presupuesto_id','centro_costo_id','tipo_gasto_id','descripcion','monto'];

    public function listByPresupuesto(int $presupuestoId): array
    {
        return $this->db
            ->select('pi.*, cc.codigo AS cc_codigo, cc.nombre AS cc_nombre, '
                . 'tg.codigo AS tg_codigo, tg.nombre AS tg_nombre')
            ->from($this->table . ' pi')
            ->join('gmc_centros_costo cc', 'cc.id = pi.centro_costo_id', 'left')
            ->join('gmc_tipos_gasto tg', 'tg.id = pi.tipo_gasto_id', 'left')
            ->where('pi.presupuesto_id', $presupuestoId)
            ->order_by('pi.id', 'ASC')
            ->get()->result_array();
    }

    public function deleteByPresupuesto(int $presupuestoId): void
    {
        $this->db->where('presupuesto_id', $presupuestoId)->delete($this->table);
    }

    public function syncItems(int $presupuestoId, array $items): float
    {
        $this->deleteByPresupuesto($presupuestoId);
        $total = 0.0;
        foreach ($items as $it) {
            $monto = (float)($it['monto'] ?? 0);
            if ($monto <= 0) continue;
            $this->create([
                'presupuesto_id'  => $presupuestoId,
                'centro_costo_id' => (int)$it['centro_costo_id'],
                'tipo_gasto_id'   => (int)$it['tipo_gasto_id'],
                'descripcion'     => trim((string)($it['descripcion'] ?? '')),
                'monto'           => $monto,
            ]);
            $total += $monto;
        }
        return $total;
    }
}
