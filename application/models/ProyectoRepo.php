<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'core/MY_Model.php';

class ProyectoRepo extends MY_Model
{
    protected $table = 'gmc_proyectos';
    protected $useSoftDelete = true;
    protected $useTimestamps = true;
    protected $useAudit = true;
    protected $fillable = [
        'codigo','nombre','cliente_id','direccion','comuna_id',
        'jefe_proyecto_id','administrador_obra_id','estado_id',
        'fecha_inicio','fecha_termino_estimada','fecha_termino_real',
        'moneda_base_id','valor_uf_referencia',
        'dias_laborales','dias_laborales_custom','trabaja_feriados',
        'observaciones',
    ];

    public function listAll(array $filters = []): array
    {
        $q = $this->db
            ->select('p.*, c.razon_social AS cliente, e.nombre AS estado_nombre, e.color AS estado_color, '
                . 'jp.nombres AS jp_nombres, jp.apellidos AS jp_apellidos, '
                . 'ao.nombres AS ao_nombres, ao.apellidos AS ao_apellidos')
            ->from($this->table . ' p')
            ->join('gmc_clientes c', 'c.id = p.cliente_id', 'left')
            ->join('gmc_estados e', 'e.id = p.estado_id', 'left')
            ->join('gmc_usuarios jp', 'jp.id = p.jefe_proyecto_id', 'left')
            ->join('gmc_usuarios ao', 'ao.id = p.administrador_obra_id', 'left')
            ->where('p.deleted_at IS NULL', null, false)
            ->order_by('p.id', 'DESC');

        if (!empty($filters['q'])) {
            $term = '%' . $filters['q'] . '%';
            $q->group_start()
              ->like('p.codigo', $term)
              ->or_like('p.nombre', $term)
              ->or_like('c.razon_social', $term)
              ->group_end();
        }
        if (!empty($filters['estado_id'])) $q->where('p.estado_id', (int)$filters['estado_id']);
        if (!empty($filters['cliente_id'])) $q->where('p.cliente_id', (int)$filters['cliente_id']);

        return $q->get()->result_array();
    }

    public function findFull(int $id): ?array
    {
        $this->db->select('p.*, c.razon_social AS cliente, e.codigo AS estado, e.nombre AS estado_nombre, e.color AS estado_color, '
                . 'm.codigo AS moneda_codigo')
            ->from($this->table . ' p')
            ->join('gmc_clientes c', 'c.id = p.cliente_id', 'left')
            ->join('gmc_estados e', 'e.id = p.estado_id', 'left')
            ->join('gmc_monedas m', 'm.id = p.moneda_base_id', 'left')
            ->where('p.id', $id)
            ->where('p.deleted_at IS NULL', null, false);
        return $this->db->get()->row_array() ?: null;
    }

    public function activos(): array
    {
        $sql = "SELECT p.id, p.codigo, p.nombre, c.razon_social AS cliente
                FROM {$this->table} p
                LEFT JOIN gmc_clientes c ON c.id = p.cliente_id
                LEFT JOIN gmc_estados e ON e.id = p.estado_id
                WHERE p.deleted_at IS NULL
                  AND e.dominio = 'proyecto'
                  AND e.codigo IN ('planificacion','en_ejecucion','pausado')
                ORDER BY p.codigo DESC";
        return $this->db->query($sql)->result_array();
    }
}
