<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Uploader — gestión de adjuntos polimórficos (gmc_adjuntos).
 *
 * - Valida MIME real con finfo (no sólo extensión).
 * - Whitelist de extensiones según config.
 * - Tamaño máximo configurable.
 * - Almacena en storage/uploads/<entidad>/<aaaa>/<mm>/<uuid>.<ext>.
 * - Devuelve el id del adjunto creado.
 */
class Uploader
{
    /** @var CI_Controller */
    protected $CI;

    /** Mapa MIME → extensiones permitidas (defensa adicional). */
    protected $mimeMap = [
        'application/pdf' => ['pdf'],
        'image/jpeg'      => ['jpg','jpeg'],
        'image/png'       => ['png'],
        'image/webp'      => ['webp'],
        'application/msword' => ['doc'],
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => ['docx'],
        'application/vnd.ms-excel' => ['xls'],
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => ['xlsx'],
        'text/csv'        => ['csv'],
        'text/plain'      => ['txt','csv'],
    ];

    public function __construct()
    {
        $this->CI =& get_instance();
    }

    /**
     * Almacena un archivo subido y registra en gmc_adjuntos.
     *
     * @param array  $file       $_FILES['campo']
     * @param string $entidad    p.ej. 'solicitud_pago'
     * @param int    $entidadId
     * @param string|null $categoria  p.ej. 'factura', 'comprobante'
     *
     * @return int adjunto_id
     * @throws RuntimeException en caso de error
     */
    public function store(array $file, string $entidad, int $entidadId, ?string $categoria = null): int
    {
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            throw new RuntimeException('Archivo no recibido o inválido.');
        }
        if ((int)$file['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Error en la subida (código ' . (int)$file['error'] . ').');
        }

        $maxBytes = (int) $this->CI->config->item('app_upload_max_bytes');
        if (filesize($file['tmp_name']) > $maxBytes) {
            throw new RuntimeException('El archivo supera el tamaño máximo permitido.');
        }

        // MIME real
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime  = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        $allowedExt = array_map('trim', explode(',', (string) $this->CI->config->item('app_upload_allowed_ext')));
        $origName = (string) $file['name'];
        $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));

        if (!in_array($ext, $allowedExt, true)) {
            throw new RuntimeException("Extensión no permitida: .{$ext}");
        }

        if (isset($this->mimeMap[$mime])) {
            if (!in_array($ext, $this->mimeMap[$mime], true)) {
                throw new RuntimeException("Inconsistencia entre extensión (.{$ext}) y contenido del archivo.");
            }
        }

        // Carpeta destino
        $base = rtrim((string) $this->CI->config->item('app_upload_base_path'), '/');
        $rel  = $entidad . '/' . date('Y') . '/' . date('m');
        $dir  = $base . '/' . $rel;
        if (!is_dir($dir) && !@mkdir($dir, 0750, true) && !is_dir($dir)) {
            throw new RuntimeException("No se pudo crear el directorio: {$dir}");
        }

        // Nombre único
        $uuid = bin2hex(random_bytes(16));
        $finalName = "{$uuid}.{$ext}";
        $destPath  = "{$dir}/{$finalName}";

        if (!@move_uploaded_file($file['tmp_name'], $destPath)) {
            throw new RuntimeException("No se pudo guardar el archivo.");
        }

        // Permisos restrictivos
        @chmod($destPath, 0640);

        // Registrar en BD
        $this->CI->db->insert('gmc_adjuntos', [
            'entidad'         => $entidad,
            'entidad_id'      => $entidadId,
            'categoria'       => $categoria,
            'nombre_original' => substr($origName, 0, 180),
            'ruta'            => "{$rel}/{$finalName}",
            'mime'            => substr($mime, 0, 120),
            'tamano_bytes'    => filesize($destPath),
            'uploaded_by'     => (int) $this->CI->session->userdata('user_id'),
        ]);

        return (int) $this->CI->db->insert_id();
    }

    /**
     * Devuelve metadata + path absoluto para descarga.
     *
     * @return array{row:array, path:string}|null
     */
    public function get(int $adjuntoId): ?array
    {
        $row = $this->CI->db
            ->where('id', $adjuntoId)
            ->where('deleted_at IS NULL', null, false)
            ->get('gmc_adjuntos')->row_array();
        if (!$row) return null;

        $base = rtrim((string) $this->CI->config->item('app_upload_base_path'), '/');
        $path = $base . '/' . $row['ruta'];
        if (!is_file($path)) return null;

        return ['row' => $row, 'path' => $path];
    }

    public function softDelete(int $adjuntoId): bool
    {
        $this->CI->db->where('id', $adjuntoId)
                     ->update('gmc_adjuntos', ['deleted_at' => date('Y-m-d H:i:s')]);
        return $this->CI->db->affected_rows() > 0;
    }

    /**
     * Lista adjuntos de una entidad.
     */
    public function listFor(string $entidad, int $entidadId): array
    {
        return $this->CI->db
            ->where('entidad', $entidad)
            ->where('entidad_id', $entidadId)
            ->where('deleted_at IS NULL', null, false)
            ->order_by('id', 'DESC')
            ->get('gmc_adjuntos')->result_array();
    }
}
