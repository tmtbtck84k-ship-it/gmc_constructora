<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'services/CorrelativoService.php';

class HitoService
{
    /** @var CI_Controller */
    protected $CI;
    protected $correlativo;

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->model(['HitoRepo','ProyectoRepo']);
        $this->CI->load->library('Audit');
        $this->correlativo = new CorrelativoService();
    }

    public function crear(array $input, int $userId): int
    {
        $proyectoId = (int)$input['proyecto_id'];
        $proyecto = $this->CI->ProyectoRepo->find($proyectoId);
        if (!$proyecto) throw new RuntimeException('Proyecto no encontrado.');

        $codigo = $this->correlativo->next('hito') . '/' . $proyecto['codigo'];
        $orden  = !empty($input['orden']) ? (int)$input['orden']
                                          : $this->CI->HitoRepo->siguienteOrden($proyectoId);

        $id = $this->CI->HitoRepo->create([
            'proyecto_id'    => $proyectoId,
            'codigo'         => $codigo,
            'nombre'         => trim((string)$input['nombre']),
            'descripcion'    => $input['descripcion'] ?? null,
            'fecha_objetivo' => !empty($input['fecha_objetivo']) ? $input['fecha_objetivo'] : null,
            'orden'          => $orden,
            'created_by'     => $userId,
            'updated_by'     => $userId,
        ]);
        $this->CI->audit->log('hito.crear', 'gmc_hitos', $id, null, [
            'codigo' => $codigo, 'nombre' => $input['nombre'], 'proyecto_id' => $proyectoId,
        ]);
        return $id;
    }

    public function editar(int $id, array $input, int $userId): void
    {
        $h = $this->CI->HitoRepo->find($id);
        if (!$h) throw new RuntimeException('Hito no encontrado.');

        $payload = [
            'nombre'         => trim((string)$input['nombre']),
            'descripcion'    => $input['descripcion'] ?? null,
            'fecha_objetivo' => !empty($input['fecha_objetivo']) ? $input['fecha_objetivo'] : null,
            'fecha_real'     => !empty($input['fecha_real'])     ? $input['fecha_real']     : null,
            'orden'          => isset($input['orden']) ? (int)$input['orden'] : $h['orden'],
            'updated_by'     => $userId,
        ];
        $this->CI->HitoRepo->update($id, $payload);
        $this->CI->audit->logChanges('hito.editar', 'gmc_hitos', $id, $h, $payload);
    }

    public function eliminar(int $id, int $userId): void
    {
        $h = $this->CI->HitoRepo->find($id);
        if (!$h) throw new RuntimeException('Hito no encontrado.');
        // Sus actividades quedan con hito_id = NULL (FK ON DELETE SET NULL)
        $this->CI->HitoRepo->softDelete($id);
        $this->CI->audit->log('hito.eliminar', 'gmc_hitos', $id, $h, null);
    }
}
