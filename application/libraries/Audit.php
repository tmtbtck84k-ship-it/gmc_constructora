<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Audit — Registro de acciones críticas en gmc_auditoria_logs.
 *
 * Uso típico:
 *   $this->audit->log('sdp.validar', 'gmc_solicitudes_pago', $sdpId, $antes, $despues);
 *
 * Acción libre (sugerencia: '<modulo>.<recurso>.<accion>' o '<recurso>.<accion>').
 */
class Audit
{
    /** @var CI_Controller */
    protected $CI;

    public function __construct()
    {
        $this->CI =& get_instance();
    }

    public function log(
        string $accion,
        ?string $entidad = null,
        ?int $entidadId = null,
        $estadoAnterior = null,
        $estadoNuevo = null
    ): void {
        $userId = (int) $this->CI->session->userdata('user_id') ?: null;
        $ip = $this->CI->input->ip_address();
        $ua = substr((string)$this->CI->input->user_agent(), 0, 255);

        $row = [
            'usuario_id'       => $userId,
            'accion'           => substr($accion, 0, 80),
            'entidad'          => $entidad ? substr($entidad, 0, 60) : null,
            'entidad_id'       => $entidadId,
            'estado_anterior'  => $estadoAnterior !== null ? json_encode($this->_clean($estadoAnterior), JSON_UNESCAPED_UNICODE) : null,
            'estado_nuevo'     => $estadoNuevo    !== null ? json_encode($this->_clean($estadoNuevo),    JSON_UNESCAPED_UNICODE) : null,
            'ip'               => $ip ?: null,
            'user_agent'       => $ua ?: null,
        ];

        $this->CI->db->insert('gmc_auditoria_logs', $row);
    }

    /**
     * Helper para registrar sólo el delta (campos que cambiaron).
     */
    public function logChanges(string $accion, string $entidad, int $entidadId, array $before, array $after): void
    {
        $changes = [];
        $beforeFiltered = [];
        foreach ($after as $k => $v) {
            $b = $before[$k] ?? null;
            if ($b !== $v) {
                $beforeFiltered[$k] = $b;
                $changes[$k] = $v;
            }
        }
        if ($changes) {
            $this->log($accion, $entidad, $entidadId, $beforeFiltered, $changes);
        }
    }

    private function _clean($v)
    {
        if (is_array($v)) {
            // Nunca persistir hashes ni datos sensibles
            unset($v['password_hash'], $v['password'], $v['_token']);
            return $v;
        }
        return $v;
    }
}
