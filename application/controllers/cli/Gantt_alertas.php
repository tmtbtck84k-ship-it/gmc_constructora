<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * CLI: Alertas de actividades retrasadas.
 *
 * Detecta actividades cuya fecha_termino_planificada < hoy y porcentaje_avance < 100,
 * envía un correo al responsable + jefe de proyecto, y encola una notificación.
 *
 * Uso (cron diario 8:00 AM):
 *   0 8 * * *  /usr/bin/php /var/www/gmc/public/index.php cli/gantt_alertas >> /var/log/gmc-gantt-alertas.log 2>&1
 *
 * En XAMPP local:
 *   /Applications/XAMPP/xamppfiles/bin/php public/index.php cli/gantt_alertas
 */
class Gantt_alertas extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        if (!$this->input->is_cli_request()) {
            show_error('Este comando sólo puede ejecutarse desde CLI.', 403);
        }
        $this->load->library(['Notifier']);
        $this->load->database();
    }

    public function index()
    {
        $hoy = date('Y-m-d');
        echo "[" . date('Y-m-d H:i:s') . "] Buscando actividades retrasadas (término < {$hoy} y avance < 100)...\n";

        $rows = $this->db
            ->select('a.id, a.codigo, a.nombre, a.fecha_termino_planificada, a.porcentaje_avance, '
                . 'p.id AS proyecto_id, p.codigo AS proyecto_codigo, p.nombre AS proyecto_nombre, '
                . 'p.jefe_proyecto_id, '
                . 'u.id AS responsable_id, u.email AS responsable_email, '
                . 'u.nombres AS responsable_nombres, u.apellidos AS responsable_apellidos, '
                . 'jp.email AS jp_email, jp.nombres AS jp_nombres, jp.apellidos AS jp_apellidos')
            ->from('gmc_actividades a')
            ->join('gmc_proyectos p', 'p.id = a.proyecto_id')
            ->join('gmc_usuarios u', 'u.id = a.responsable_id', 'left')
            ->join('gmc_usuarios jp', 'jp.id = p.jefe_proyecto_id', 'left')
            ->where('a.fecha_termino_planificada <', $hoy)
            ->where('a.porcentaje_avance <', 100)
            ->where('a.deleted_at IS NULL', null, false)
            ->where('p.deleted_at IS NULL', null, false)
            ->order_by('a.fecha_termino_planificada', 'ASC')
            ->get()->result_array();

        if (!$rows) {
            echo "Sin actividades retrasadas. Nada que enviar.\n";
            return;
        }

        // Agrupar por destinatario para mandar un correo consolidado por persona
        $porDestinatario = [];
        foreach ($rows as $r) {
            $diasRetraso = (strtotime($hoy) - strtotime($r['fecha_termino_planificada'])) / 86400;
            $r['dias_retraso'] = (int)$diasRetraso;

            if (!empty($r['responsable_email'])) {
                $porDestinatario[$r['responsable_email']]['nombre'] = trim($r['responsable_nombres'] . ' ' . $r['responsable_apellidos']);
                $porDestinatario[$r['responsable_email']]['rows'][] = $r;
            }
            if (!empty($r['jp_email']) && $r['jp_email'] !== ($r['responsable_email'] ?? null)) {
                $porDestinatario[$r['jp_email']]['nombre'] = trim($r['jp_nombres'] . ' ' . $r['jp_apellidos']);
                $porDestinatario[$r['jp_email']]['rows'][]  = $r;
            }
        }

        $enviados = 0; $fallidos = 0;
        foreach ($porDestinatario as $email => $data) {
            $html = $this->_buildEmail($data['nombre'] ?? 'estimado(a)', $data['rows']);
            try {
                $this->notifier->send(
                    $email,
                    "[GMC ERP] " . count($data['rows']) . " actividad(es) con retraso",
                    $html
                );
                echo "  -> {$email}: " . count($data['rows']) . " actividades enviadas\n";
                $enviados++;
            } catch (Throwable $e) {
                echo "  XX {$email}: error " . $e->getMessage() . "\n";
                $fallidos++;
            }
        }

        // Encolar notificaciones internas (gmc_notificaciones)
        if ($this->db->table_exists('gmc_notificaciones')) {
            foreach ($rows as $r) {
                if (empty($r['responsable_id'])) continue;
                $this->db->insert('gmc_notificaciones', [
                    'usuario_id' => (int)$r['responsable_id'],
                    'tipo'       => 'gantt.actividad_retrasada',
                    'titulo'     => 'Actividad retrasada: ' . $r['codigo'],
                    'cuerpo'     => 'La actividad "' . $r['nombre'] . '" del proyecto ' .
                                    $r['proyecto_codigo'] . ' lleva ' . $r['dias_retraso'] . ' día(s) de retraso.',
                    'url'        => 'obras/actividades/' . $r['id'] . '/editar',
                    'leida'      => 0,
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
            }
        }

        echo "\nResumen: {$enviados} correos enviados, {$fallidos} fallidos. Actividades atrasadas: " . count($rows) . ".\n";
    }

    private function _buildEmail(string $destinatario, array $rows): string
    {
        $filas = '';
        foreach ($rows as $r) {
            $filas .= '<tr>'
                . '<td><code>' . htmlspecialchars($r['codigo']) . '</code></td>'
                . '<td>' . htmlspecialchars($r['nombre']) . '</td>'
                . '<td>' . htmlspecialchars($r['proyecto_codigo']) . '</td>'
                . '<td>' . htmlspecialchars($r['fecha_termino_planificada']) . '</td>'
                . '<td style="text-align:center;color:#dc3545;"><strong>' . $r['dias_retraso'] . ' días</strong></td>'
                . '<td style="text-align:right;">' . number_format((float)$r['porcentaje_avance'], 0) . '%</td>'
                . '</tr>';
        }
        return '
<!doctype html><html><body style="font-family:Arial,sans-serif;color:#212529;">
<h2 style="color:#dc3545;">Actividades con retraso</h2>
<p>Hola ' . htmlspecialchars($destinatario) . ',</p>
<p>Las siguientes actividades del Gantt están atrasadas (fecha de término planificada vencida y avance &lt; 100%):</p>
<table cellpadding="6" cellspacing="0" border="1" style="border-collapse:collapse;font-size:13px;">
<thead style="background:#f8f9fa;"><tr>
  <th>Código</th><th>Actividad</th><th>Proyecto</th><th>Término plan.</th><th>Retraso</th><th>Avance</th>
</tr></thead>
<tbody>' . $filas . '</tbody>
</table>
<p style="margin-top:20px;">Por favor, ingrese al sistema y actualice los avances o reprograme las actividades.</p>
<p style="font-size:11px;color:#6c757d;margin-top:30px;">— ERP GMC · Notificación automática diaria</p>
</body></html>';
    }
}
