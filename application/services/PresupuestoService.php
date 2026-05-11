<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * PresupuestoService — versionado de presupuesto inicial por obra.
 *
 * Reglas:
 *   - Sólo una versión vigente por proyecto.
 *   - Crear nueva versión: copia ítems de la vigente y la marca como vigente nueva,
 *     desmarcando la anterior.
 */
class PresupuestoService
{
    /** @var CI_Controller */
    protected $CI;

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->model(['PresupuestoObraRepo','PresupuestoObraItemRepo','ProyectoRepo','MonedaRepo']);
        $this->CI->load->library('Audit');
    }

    public function crear(int $proyectoId, array $input, array $items, int $userId): int
    {
        $proyecto = $this->CI->ProyectoRepo->find($proyectoId);
        if (!$proyecto) throw new RuntimeException('Proyecto no encontrado.');

        $monedaId = !empty($input['moneda_id'])
            ? (int)$input['moneda_id']
            : (int)$proyecto['moneda_base_id'];

        $version = $this->CI->PresupuestoObraRepo->siguienteVersion($proyectoId);

        $this->CI->db->trans_start();

        $presupuestoId = $this->CI->PresupuestoObraRepo->create([
            'proyecto_id' => $proyectoId,
            'version'     => $version,
            'moneda_id'   => $monedaId,
            'monto_total' => 0,
            'vigente'     => 1,
            'created_by'  => $userId,
            'updated_by'  => $userId,
        ]);

        $total = $this->CI->PresupuestoObraItemRepo->syncItems($presupuestoId, $items);

        $this->CI->PresupuestoObraRepo->update($presupuestoId, ['monto_total' => $total]);
        $this->CI->PresupuestoObraRepo->marcarUnicaVigente($proyectoId, $presupuestoId);

        $this->CI->audit->log('presupuesto.crear', 'gmc_presupuestos_obra', $presupuestoId, null, [
            'proyecto_id' => $proyectoId, 'version' => $version, 'monto_total' => $total,
        ]);

        $this->CI->db->trans_complete();
        return $presupuestoId;
    }

    public function editar(int $presupuestoId, array $input, array $items, int $userId): void
    {
        $p = $this->CI->PresupuestoObraRepo->find($presupuestoId);
        if (!$p) throw new RuntimeException('Presupuesto no encontrado.');

        $this->CI->db->trans_start();
        $total = $this->CI->PresupuestoObraItemRepo->syncItems($presupuestoId, $items);
        $payload = ['monto_total' => $total, 'updated_by' => $userId];
        if (!empty($input['moneda_id'])) $payload['moneda_id'] = (int)$input['moneda_id'];
        $this->CI->PresupuestoObraRepo->update($presupuestoId, $payload);
        $this->CI->audit->logChanges('presupuesto.editar', 'gmc_presupuestos_obra', $presupuestoId, $p, $payload);
        $this->CI->db->trans_complete();
    }

    /** Crea nueva versión copiando los items de la vigente. */
    public function nuevaVersion(int $proyectoId, int $userId): int
    {
        $vigente = $this->CI->PresupuestoObraRepo->vigentePorProyecto($proyectoId);
        if (!$vigente) {
            // No hay vigente, crea v1 vacío
            return $this->crear($proyectoId, [], [], $userId);
        }
        $items = $this->CI->PresupuestoObraItemRepo->listByPresupuesto((int)$vigente['id']);
        return $this->crear($proyectoId, ['moneda_id' => $vigente['moneda_id']], $items, $userId);
    }
}
