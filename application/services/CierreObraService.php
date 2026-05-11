<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * CierreObraService — registro y cierre formal de obra.
 *
 * Reglas:
 *   - Un cierre por proyecto.
 *   - Se crea en estado 'borrador' y se cierra a 'cerrada' (final).
 *   - Para cerrar, todas las SDPs del proyecto deben estar en estado FINAL
 *     (Pagada o Rechazada). Si hay alguna pendiente/validada/programada,
 *     se bloquea el cierre con mensaje claro.
 *   - Al cerrar también se actualiza proyecto.estado a 'cerrado' y
 *     proyecto.fecha_termino_real.
 */
class CierreObraService
{
    /** @var CI_Controller */
    protected $CI;

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->model(['CierreObraRepo','ProyectoRepo']);
        $this->CI->load->library('Audit');
    }

    public function crearOEditar(int $proyectoId, array $input, int $userId): int
    {
        $p = $this->CI->ProyectoRepo->find($proyectoId);
        if (!$p) throw new RuntimeException('Proyecto no encontrado.');

        $existente = $this->CI->CierreObraRepo->findByProyecto($proyectoId);

        $estadoBorrador = $this->_estado('borrador');

        $payload = [
            'fecha_termino_real' => $input['fecha_termino_real'],
            'resumen'            => trim((string)$input['resumen']),
            'conformidades'      => $input['conformidades'] ?? null,
            'observaciones'      => $input['observaciones'] ?? null,
            'updated_by'         => $userId,
        ];

        if ($existente) {
            // Si ya está cerrado, no permitir re-edición
            $estActual = $this->_estadoById((int)$existente['estado_id']);
            if ($estActual['codigo'] === 'cerrada') {
                throw new RuntimeException('Esta obra ya fue cerrada formalmente y no se puede modificar.');
            }
            $this->CI->CierreObraRepo->update((int)$existente['id'], $payload);
            $this->CI->audit->logChanges('cierre.editar', 'gmc_cierres_obra', (int)$existente['id'], $existente, $payload);
            return (int)$existente['id'];
        }

        $payload = array_merge($payload, [
            'proyecto_id' => $proyectoId,
            'estado_id'   => (int)$estadoBorrador['id'],
            'created_by'  => $userId,
        ]);
        $cierreId = $this->CI->CierreObraRepo->create($payload);
        $this->CI->audit->log('cierre.crear', 'gmc_cierres_obra', $cierreId, null, $payload);
        return $cierreId;
    }

    /**
     * Cierre formal: marca cierre y proyecto como 'cerrado'.
     * Verifica que todas las SDPs del proyecto estén en estado final.
     */
    public function cerrar(int $proyectoId, int $userId): void
    {
        $p = $this->CI->ProyectoRepo->find($proyectoId);
        if (!$p) throw new RuntimeException('Proyecto no encontrado.');

        $cierre = $this->CI->CierreObraRepo->findByProyecto($proyectoId);
        if (!$cierre) throw new RuntimeException('Debes crear un borrador de cierre antes de cerrar la obra.');

        // Verificar SDPs pendientes
        $pendientes = $this->_sdpsNoFinales($proyectoId);
        if ($pendientes > 0) {
            throw new RuntimeException("No se puede cerrar la obra: hay {$pendientes} SDP(s) en estado distinto de Pagada/Rechazada. Resuélvelas antes de cerrar.");
        }

        $estadoCerradaCierre   = $this->_estado('cerrada');           // dominio cierre
        $estadoCerradaProyecto = $this->_estadoProyecto('cerrado');   // dominio proyecto

        $this->CI->db->trans_start();

        // Actualizar cierre
        $this->CI->CierreObraRepo->update((int)$cierre['id'], [
            'estado_id'   => (int)$estadoCerradaCierre['id'],
            'cerrada_por' => $userId,
            'cerrada_at'  => date('Y-m-d H:i:s'),
            'updated_by'  => $userId,
        ]);
        // Actualizar proyecto
        $this->CI->ProyectoRepo->update($proyectoId, [
            'estado_id'          => (int)$estadoCerradaProyecto['id'],
            'fecha_termino_real' => $cierre['fecha_termino_real'],
            'updated_by'         => $userId,
        ]);

        $this->CI->audit->log('cierre.cerrar', 'gmc_cierres_obra', (int)$cierre['id'], null, [
            'proyecto_id' => $proyectoId, 'fecha_termino_real' => $cierre['fecha_termino_real'],
        ]);

        $this->CI->db->trans_complete();
    }

    private function _sdpsNoFinales(int $proyectoId): int
    {
        $sql = "SELECT COUNT(*) AS total
                FROM gmc_solicitudes_pago s
                JOIN gmc_estados e ON e.id = s.estado_id AND e.dominio = 'solicitud_pago'
                WHERE s.proyecto_id = ?
                  AND s.deleted_at IS NULL
                  AND e.es_final = 0";
        return (int)$this->CI->db->query($sql, [$proyectoId])->row()->total;
    }

    private function _estado(string $codigo): array
    {
        $row = $this->CI->db->where(['dominio' => 'cierre', 'codigo' => $codigo])->get('gmc_estados')->row_array();
        if (!$row) throw new RuntimeException("Estado '{$codigo}' no existe en dominio 'cierre'.");
        return $row;
    }

    private function _estadoById(int $id): array
    {
        $row = $this->CI->db->where('id', $id)->get('gmc_estados')->row_array();
        if (!$row) throw new RuntimeException("Estado id={$id} no existe.");
        return $row;
    }

    private function _estadoProyecto(string $codigo): array
    {
        $row = $this->CI->db->where(['dominio' => 'proyecto', 'codigo' => $codigo])->get('gmc_estados')->row_array();
        if (!$row) throw new RuntimeException("Estado '{$codigo}' no existe en dominio 'proyecto'.");
        return $row;
    }
}
