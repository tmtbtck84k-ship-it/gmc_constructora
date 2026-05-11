<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class FeriadoService
{
    /** @var CI_Controller */
    protected $CI;

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->model('FeriadoRepo');
        $this->CI->load->library('Audit');
    }

    public function crear(array $input, int $userId): int
    {
        $fecha = $input['fecha'] ?? '';
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            throw new RuntimeException('Fecha inválida (use formato YYYY-MM-DD).');
        }
        if (!$this->CI->FeriadoRepo->checkFechaUnique($fecha)) {
            throw new RuntimeException('Ya existe un feriado registrado para esa fecha.');
        }
        $payload = [
            'fecha'         => $fecha,
            'nombre'        => trim((string)$input['nombre']),
            'irrenunciable' => !empty($input['irrenunciable']) ? 1 : 0,
            'tipo'          => $input['tipo'] ?? null,
        ];
        $id = $this->CI->FeriadoRepo->create($payload);
        $this->CI->audit->log('feriado.crear', 'gmc_feriados', $id, null, $payload);
        return $id;
    }

    public function editar(int $id, array $input, int $userId): void
    {
        $f = $this->CI->FeriadoRepo->find($id);
        if (!$f) throw new RuntimeException('Feriado no encontrado.');

        $fecha = $input['fecha'] ?? $f['fecha'];
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            throw new RuntimeException('Fecha inválida (use formato YYYY-MM-DD).');
        }
        if (!$this->CI->FeriadoRepo->checkFechaUnique($fecha, $id)) {
            throw new RuntimeException('Ya existe otro feriado en esa fecha.');
        }
        $payload = [
            'fecha'         => $fecha,
            'nombre'        => trim((string)($input['nombre'] ?? $f['nombre'])),
            'irrenunciable' => !empty($input['irrenunciable']) ? 1 : 0,
            'tipo'          => array_key_exists('tipo', $input) ? $input['tipo'] : $f['tipo'],
        ];
        $this->CI->FeriadoRepo->update($id, $payload);
        $this->CI->audit->logChanges('feriado.editar', 'gmc_feriados', $id, $f, $payload);
    }

    public function eliminar(int $id, int $userId): void
    {
        $f = $this->CI->FeriadoRepo->find($id);
        if (!$f) throw new RuntimeException('Feriado no encontrado.');
        $this->CI->FeriadoRepo->destroy($id);
        $this->CI->audit->log('feriado.eliminar', 'gmc_feriados', $id, $f, null);
    }

    /**
     * Carga masiva desde CSV: encabezado fecha,nombre,irrenunciable,tipo
     * Devuelve [insertados, actualizados, errores[]]
     */
    public function importarCsv(string $rutaArchivo, int $userId): array
    {
        if (!is_readable($rutaArchivo)) {
            throw new RuntimeException('No se pudo leer el archivo CSV.');
        }
        $fh = fopen($rutaArchivo, 'r');
        $headers = fgetcsv($fh);
        if (!$headers) {
            fclose($fh);
            throw new RuntimeException('CSV vacío o inválido.');
        }
        $headers = array_map('trim', array_map('strtolower', $headers));
        $idx = array_flip($headers);

        $insertados = 0; $actualizados = 0; $errores = [];
        $linea = 1;
        while (($row = fgetcsv($fh)) !== false) {
            $linea++;
            try {
                $fecha = trim($row[$idx['fecha']] ?? '');
                $nombre = trim($row[$idx['nombre']] ?? '');
                $irre  = !empty($row[$idx['irrenunciable']] ?? null) ? 1 : 0;
                $tipo  = isset($idx['tipo']) ? ($row[$idx['tipo']] ?? null) : null;
                if (!$fecha || !$nombre) {
                    throw new RuntimeException('fecha y nombre son obligatorios');
                }
                $exists = $this->CI->FeriadoRepo
                    ->where('fecha', $fecha)->first();
                if ($exists) {
                    $this->CI->FeriadoRepo->update((int)$exists['id'], [
                        'nombre' => $nombre, 'irrenunciable' => $irre, 'tipo' => $tipo,
                    ]);
                    $actualizados++;
                } else {
                    $this->CI->FeriadoRepo->create([
                        'fecha' => $fecha, 'nombre' => $nombre,
                        'irrenunciable' => $irre, 'tipo' => $tipo,
                    ]);
                    $insertados++;
                }
            } catch (Throwable $e) {
                $errores[] = "Línea {$linea}: " . $e->getMessage();
            }
        }
        fclose($fh);
        $this->CI->audit->log('feriado.importar_csv', 'gmc_feriados', 0, null, [
            'insertados' => $insertados, 'actualizados' => $actualizados,
            'errores' => count($errores),
        ]);
        return [$insertados, $actualizados, $errores];
    }
}
