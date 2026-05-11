<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'core/MY_Model.php';

class RindeItemRepo extends MY_Model
{
    protected $table = 'gmc_rinde_items';
    protected $useSoftDelete = false;
    protected $useTimestamps = false;
    protected $useAudit = false;
    protected $fillable = ['rinde_id','tipo_gasto_id','fecha','descripcion','documento_tipo','documento_numero','monto'];

    public function listByRinde(int $rindeId): array
    {
        return $this->db
            ->select('ri.*, tg.codigo AS tg_codigo, tg.nombre AS tg_nombre')
            ->from($this->table . ' ri')
            ->join('gmc_tipos_gasto tg', 'tg.id = ri.tipo_gasto_id', 'left')
            ->where('ri.rinde_id', $rindeId)
            ->order_by('ri.fecha', 'ASC')
            ->order_by('ri.id', 'ASC')
            ->get()->result_array();
    }

    public function deleteByRinde(int $rindeId): void
    {
        $this->db->where('rinde_id', $rindeId)->delete($this->table);
    }

    public function syncItems(int $rindeId, array $items): float
    {
        $this->deleteByRinde($rindeId);
        $total = 0.0;
        foreach ($items as $it) {
            $monto = (float)($it['monto'] ?? 0);
            if ($monto <= 0) continue;
            $this->create([
                'rinde_id'         => $rindeId,
                'tipo_gasto_id'    => (int)$it['tipo_gasto_id'],
                'fecha'            => $it['fecha'] ?? date('Y-m-d'),
                'descripcion'      => trim((string)($it['descripcion'] ?? '')),
                'documento_tipo'   => trim((string)($it['documento_tipo'] ?? '')) ?: null,
                'documento_numero' => trim((string)($it['documento_numero'] ?? '')) ?: null,
                'monto'            => $monto,
            ]);
            $total += $monto;
        }
        return $total;
    }
}
