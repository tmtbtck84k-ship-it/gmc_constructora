<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Adjuntos — controller transversal para upload/descarga/borrado.
 * El permiso se valida según la entidad-objetivo (mapeo abajo).
 */
class Adjuntos extends MY_AuthController
{
    /** @var string[] mapeo entidad → permiso requerido para subir */
    private $permsUpload = [
        'solicitud_pago' => 'finanzas.sdp.editar',
        'compra'         => 'compras.compra.editar',
        'rinde'          => 'compras.rinde.editar',
        'bitacora'       => 'obras.bitacora.crear',
        'cierre'         => 'obras.cierre.crear',
        'proyecto'       => 'maestros.proyecto.editar',
        'cliente'        => 'maestros.cliente.editar',
        'proveedor'      => 'maestros.proveedor.editar',
    ];

    public function __construct()
    {
        parent::__construct();
        $this->load->library('Uploader');
    }

    public function upload()
    {
        $entidad   = (string) $this->input->post('entidad');
        $entidadId = (int)    $this->input->post('entidad_id');
        $categoria = $this->input->post('categoria') ?: null;

        if (!isset($this->permsUpload[$entidad])) {
            $this->json(['ok' => false, 'error' => 'Entidad no soportada.'], 400);
            return;
        }
        $this->require_permission($this->permsUpload[$entidad]);

        try {
            $id = $this->uploader->store($_FILES['archivo'] ?? [], $entidad, $entidadId, $categoria);
            $this->audit->log('adjunto.subir', $entidad, $entidadId, null, ['adjunto_id' => $id, 'categoria' => $categoria]);
            $this->json(['ok' => true, 'id' => $id]);
        } catch (\Throwable $e) {
            $this->json(['ok' => false, 'error' => $e->getMessage()], 422);
        }
    }

    public function descargar(int $id)
    {
        $info = $this->uploader->get($id);
        if (!$info) show_error('Adjunto no encontrado.', 404);

        $row = $info['row'];
        // El permiso de DESCARGAR es el de VER de la entidad. Por simplicidad,
        // basta con que el usuario tenga al menos un permiso del módulo asociado.
        if (!$this->_canDownload($row['entidad'])) {
            show_error('No tienes permiso para descargar este adjunto.', 403);
        }

        $this->audit->log('adjunto.descargar', $row['entidad'], (int)$row['entidad_id'], null, ['adjunto_id' => $id]);

        header('Content-Type: ' . $row['mime']);
        header('Content-Disposition: attachment; filename="' . addslashes($row['nombre_original']) . '"');
        header('Content-Length: ' . $row['tamano_bytes']);
        readfile($info['path']);
        exit;
    }

    public function eliminar(int $id)
    {
        $info = $this->uploader->get($id);
        if (!$info) {
            $this->flash('error', 'Adjunto no encontrado.');
            redirect($this->input->server('HTTP_REFERER') ?: base_url());
        }
        $row = $info['row'];
        if (!isset($this->permsUpload[$row['entidad']])) show_error('Entidad no soportada', 400);
        $this->require_permission($this->permsUpload[$row['entidad']]);

        $this->uploader->softDelete($id);
        $this->audit->log('adjunto.eliminar', $row['entidad'], (int)$row['entidad_id'], $row, null);
        $this->flash('success', 'Adjunto eliminado.');
        redirect($this->input->server('HTTP_REFERER') ?: base_url());
    }

    private function _canDownload(string $entidad): bool
    {
        $map = [
            'solicitud_pago' => 'finanzas.sdp.ver',
            'compra'         => 'compras.compra.ver',
            'rinde'          => 'compras.rinde.ver',
            'bitacora'       => 'obras.bitacora.ver',
            'cierre'         => 'obras.cierre.ver',
            'proyecto'       => 'maestros.proyecto.ver',
            'cliente'        => 'maestros.cliente.ver',
            'proveedor'      => 'maestros.proveedor.ver',
        ];
        if (!isset($map[$entidad])) return false;
        return $this->acl->can($map[$entidad], $this->user_id());
    }
}
