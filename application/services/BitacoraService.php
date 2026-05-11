<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'services/CorrelativoService.php';

/**
 * BitacoraService — registro de eventos de obra.
 *
 * Reglas:
 *   - Sólo el autor puede editar y dentro de las primeras 24 horas.
 *   - Admin/Gerencia siempre pueden editar.
 *   - Tras 24h queda inmutable como evidencia.
 */
class BitacoraService
{
    /** @var CI_Controller */
    protected $CI;
    protected $correlativo;
    protected $editWindowSeconds = 86400;  // 24 horas

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->model(['BitacoraRepo','ProyectoRepo']);
        $this->CI->load->library(['Audit','Acl']);
        $this->correlativo = new CorrelativoService();
    }

    public function crear(array $input, int $userId): int
    {
        $proyectoId = (int)$input['proyecto_id'];
        $proyecto = $this->CI->ProyectoRepo->find($proyectoId);
        if (!$proyecto) throw new RuntimeException('Proyecto no encontrado.');

        // Correlativo BIT-OBR-AAAA-NNN scoped por proyecto (usamos el código del proyecto)
        $numero = $this->correlativo->next('bitacora') . '/' . $proyecto['codigo'];

        $bitId = $this->CI->BitacoraRepo->create([
            'numero'       => $numero,
            'proyecto_id'  => $proyectoId,
            'fecha_evento' => $input['fecha_evento'] ?? date('Y-m-d'),
            'tipo_evento'  => $input['tipo_evento'] ?? 'avance',
            'titulo'       => trim((string)$input['titulo']),
            'detalle'      => trim((string)$input['detalle']),
            'autor_id'     => $userId,
        ]);

        $this->CI->audit->log('bitacora.crear', 'gmc_bitacoras', $bitId, null, [
            'numero' => $numero, 'titulo' => $input['titulo'], 'tipo' => $input['tipo_evento'],
        ]);
        return $bitId;
    }

    public function editar(int $bitId, array $input, int $userId): void
    {
        $bit = $this->CI->BitacoraRepo->find($bitId);
        if (!$bit) throw new RuntimeException('Entrada de bitácora no encontrada.');

        // Verificar quién puede editar
        if (!$this->puedeEditar($bit, $userId)) {
            throw new RuntimeException('Sólo el autor puede editar dentro de las primeras 24 horas. Después queda como registro inmutable.');
        }

        $payload = [
            'fecha_evento' => $input['fecha_evento'] ?? $bit['fecha_evento'],
            'tipo_evento'  => $input['tipo_evento']  ?? $bit['tipo_evento'],
            'titulo'       => trim((string)($input['titulo'] ?? $bit['titulo'])),
            'detalle'      => trim((string)($input['detalle'] ?? $bit['detalle'])),
        ];
        $this->CI->BitacoraRepo->update($bitId, $payload);
        $this->CI->audit->logChanges('bitacora.editar', 'gmc_bitacoras', $bitId, $bit, $payload);
    }

    public function puedeEditar(array $bit, int $userId): bool
    {
        // Admin / Gerencia: siempre
        if ($this->CI->acl->isAdmin($userId)) return true;
        $roles = $this->CI->acl->rolesOf($userId);
        if (in_array('gerencia', $roles, true)) return true;

        // Autor sólo dentro de 24h
        if ((int)$bit['autor_id'] !== $userId) return false;
        $creado = strtotime($bit['created_at']);
        return ($creado && (time() - $creado) <= $this->editWindowSeconds);
    }
}
