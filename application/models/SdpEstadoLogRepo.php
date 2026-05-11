<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'core/MY_Model.php';

class SdpEstadoLogRepo extends MY_Model
{
    protected $table = 'gmc_sdp_estados_log';
    protected $useSoftDelete = false;
    protected $useTimestamps = false;
    protected $useAudit = false;
    protected $fillable = ['solicitud_pago_id','estado_anterior_id','estado_nuevo_id','usuario_id','comentario'];

    public function logTransicion(int $sdpId, ?int $estadoAnterior, int $estadoNuevo, int $usuarioId, ?string $comentario = null): int
    {
        return $this->create([
            'solicitud_pago_id'  => $sdpId,
            'estado_anterior_id' => $estadoAnterior,
            'estado_nuevo_id'    => $estadoNuevo,
            'usuario_id'         => $usuarioId,
            'comentario'         => $comentario,
        ]);
    }

    public function timeline(int $sdpId): array
    {
        return $this->db
            ->select('l.*, '
                . 'u.nombres, u.apellidos, '
                . 'ea.codigo AS estado_anterior_codigo, ea.nombre AS estado_anterior_nombre, ea.color AS estado_anterior_color, '
                . 'en.codigo AS estado_nuevo_codigo,    en.nombre AS estado_nuevo_nombre,    en.color AS estado_nuevo_color')
            ->from($this->table . ' l')
            ->join('gmc_usuarios u', 'u.id = l.usuario_id', 'left')
            ->join('gmc_estados ea', 'ea.id = l.estado_anterior_id', 'left')
            ->join('gmc_estados en', 'en.id = l.estado_nuevo_id', 'left')
            ->where('l.solicitud_pago_id', $sdpId)
            ->order_by('l.id', 'ASC')
            ->get()->result_array();
    }
}
