<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'services/CorrelativoService.php';

/**
 * ProyectoService — encapsula creación/edición de proyectos con sus efectos colaterales:
 *   - Genera código correlativo OBR-AAAA-NNN.
 *   - Resuelve estado inicial = 'planificacion'.
 *   - Crea automáticamente el CC "ADM-OBR" del proyecto.
 *   - Registra auditoría.
 */
class ProyectoService
{
    /** @var CI_Controller */
    protected $CI;
    protected $correlativo;

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->model(['ProyectoRepo','CentroCostoRepo']);
        $this->CI->load->library('Audit');
        $this->correlativo = new CorrelativoService();
    }

    public function crear(array $input, ?int $userId): int
    {
        // Estado inicial 'planificacion' del dominio 'proyecto'
        $estado = $this->CI->db
            ->where(['dominio' => 'proyecto', 'codigo' => 'planificacion'])
            ->get('gmc_estados')->row_array();
        if (!$estado) {
            throw new RuntimeException("Estado inicial 'planificacion' no existe en gmc_estados.");
        }

        // Moneda CLP por default
        $moneda = $this->CI->db
            ->where('codigo', 'CLP')
            ->get('gmc_monedas')->row_array();
        if (!$moneda) {
            throw new RuntimeException("Moneda 'CLP' no existe en gmc_monedas.");
        }

        $this->CI->db->trans_start();

        $codigo = $this->correlativo->next('proyecto');

        [$diasLab, $diasCustom] = $this->_normalizarCalendario($input);

        $proyectoId = $this->CI->ProyectoRepo->create([
            'codigo'                 => $codigo,
            'nombre'                 => $input['nombre'],
            'cliente_id'             => (int)$input['cliente_id'],
            'direccion'              => $input['direccion'] ?? null,
            'comuna_id'              => !empty($input['comuna_id']) ? (int)$input['comuna_id'] : null,
            'jefe_proyecto_id'       => !empty($input['jefe_proyecto_id']) ? (int)$input['jefe_proyecto_id'] : null,
            'administrador_obra_id'  => !empty($input['administrador_obra_id']) ? (int)$input['administrador_obra_id'] : null,
            'estado_id'              => (int)$estado['id'],
            'fecha_inicio'           => $input['fecha_inicio'] ?? null,
            'fecha_termino_estimada' => $input['fecha_termino_estimada'] ?? null,
            'moneda_base_id'         => (int)$moneda['id'],
            'valor_uf_referencia'    => !empty($input['valor_uf_referencia']) ? $input['valor_uf_referencia'] : null,
            'dias_laborales'         => $diasLab,
            'dias_laborales_custom'  => $diasCustom,
            'trabaja_feriados'       => !empty($input['trabaja_feriados']) ? 1 : 0,
            'observaciones'          => $input['observaciones'] ?? null,
            'created_by'             => $userId,
            'updated_by'             => $userId,
        ]);

        // Crear CC "ADM-OBR" automático
        $this->CI->CentroCostoRepo->crearAdmObra($proyectoId, $userId);

        $this->CI->audit->log('proyecto.crear', 'gmc_proyectos', $proyectoId, null, [
            'codigo' => $codigo,
            'nombre' => $input['nombre'],
        ]);

        $this->CI->db->trans_complete();
        return $proyectoId;
    }

    public function actualizar(int $id, array $input, ?int $userId): void
    {
        $before = $this->CI->ProyectoRepo->find($id);
        if (!$before) throw new RuntimeException('Proyecto no encontrado.');

        [$diasLab, $diasCustom] = $this->_normalizarCalendario($input);

        $payload = [
            'nombre'                 => $input['nombre'],
            'cliente_id'             => (int)$input['cliente_id'],
            'direccion'              => $input['direccion'] ?? null,
            'comuna_id'              => !empty($input['comuna_id']) ? (int)$input['comuna_id'] : null,
            'jefe_proyecto_id'       => !empty($input['jefe_proyecto_id']) ? (int)$input['jefe_proyecto_id'] : null,
            'administrador_obra_id'  => !empty($input['administrador_obra_id']) ? (int)$input['administrador_obra_id'] : null,
            'fecha_inicio'           => $input['fecha_inicio'] ?? null,
            'fecha_termino_estimada' => $input['fecha_termino_estimada'] ?? null,
            'dias_laborales'         => $diasLab,
            'dias_laborales_custom'  => $diasCustom,
            'trabaja_feriados'       => !empty($input['trabaja_feriados']) ? 1 : 0,
            'observaciones'          => $input['observaciones'] ?? null,
            'updated_by'             => $userId,
        ];
        if (!empty($input['estado_id'])) $payload['estado_id'] = (int)$input['estado_id'];

        $this->CI->ProyectoRepo->update($id, $payload);

        $this->CI->audit->logChanges('proyecto.editar', 'gmc_proyectos', $id, $before, $payload);
    }

    /**
     * Normaliza la entrada del bloque "Calendario laboral" del form.
     * Devuelve [dias_laborales, dias_laborales_custom|null].
     */
    private function _normalizarCalendario(array $input): array
    {
        $diasLab = $input['dias_laborales'] ?? 'lun_vie';
        $valid = ['lun_vie','lun_sab','lun_dom','personalizado'];
        if (!in_array($diasLab, $valid, true)) $diasLab = 'lun_vie';

        $diasCustom = null;
        if ($diasLab === 'personalizado') {
            $cust = $input['dias_laborales_custom'] ?? null;
            if (is_array($cust)) {
                $diasCustom = implode(',', array_filter(array_map('trim', $cust)));
            } elseif (is_string($cust)) {
                $diasCustom = trim($cust);
            }
            if (empty($diasCustom)) {
                throw new RuntimeException('Debe seleccionar al menos un día personalizado.');
            }
        }
        return [$diasLab, $diasCustom];
    }
}
