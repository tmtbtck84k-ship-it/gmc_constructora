<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Notifier — encolado y envío de correos vía PHPMailer + cola gmc_notificaciones.
 *
 * Uso:
 *   $this->notifier->encolar('sdp.validada', 'destinatario@ejemplo.cl',
 *                            'Asunto', 'Cuerpo en texto', ['payload'=>'opcional']);
 *
 *   php index.php cli/mailer        // procesa cola pendiente
 */
class Notifier
{
    /** @var CI_Controller */
    protected $CI;

    public function __construct()
    {
        $this->CI =& get_instance();
    }

    /**
     * Encola una notificación de email.
     */
    public function encolar(
        string $tipo,
        string $emailDestinatario,
        string $asunto,
        string $cuerpoTexto,
        array $payload = []
    ): int {
        $cuerpoHtml = $this->_renderHtml($asunto, $cuerpoTexto);

        $userId = $payload['user_id'] ?? null;

        $this->CI->db->insert('gmc_notificaciones', [
            'tipo'                    => substr($tipo, 0, 60),
            'canal'                   => 'email',
            'destinatario_usuario_id' => $userId ? (int)$userId : null,
            'destinatario_email'      => $emailDestinatario,
            'asunto'                  => substr($asunto, 0, 180),
            'cuerpo'                  => $cuerpoHtml,
            'payload'                 => $payload ? json_encode($payload, JSON_UNESCAPED_UNICODE) : null,
            'estado'                  => 'pendiente',
        ]);
        return (int) $this->CI->db->insert_id();
    }

    /**
     * Procesa hasta $batch notificaciones pendientes.
     * Llamado desde cli/mailer.
     *
     * @return array{procesadas:int, fallidas:int}
     */
    public function procesarCola(int $batch = 50, int $maxIntentos = 3): array
    {
        $rows = $this->CI->db
            ->where('estado', 'pendiente')
            ->where('canal', 'email')
            ->where('intentos <', $maxIntentos, false)
            ->order_by('id', 'ASC')
            ->limit($batch)
            ->get('gmc_notificaciones')->result_array();

        $ok = 0; $err = 0;
        foreach ($rows as $r) {
            try {
                $this->_enviar($r);
                $this->CI->db->where('id', $r['id'])->update('gmc_notificaciones', [
                    'estado'     => 'enviada',
                    'enviada_at' => date('Y-m-d H:i:s'),
                    'intentos'   => (int)$r['intentos'] + 1,
                ]);
                $ok++;
            } catch (\Throwable $e) {
                $intentos = (int)$r['intentos'] + 1;
                $estado = ($intentos >= $maxIntentos) ? 'fallida' : 'pendiente';
                $this->CI->db->where('id', $r['id'])->update('gmc_notificaciones', [
                    'estado'        => $estado,
                    'intentos'      => $intentos,
                    'ultimo_error'  => substr($e->getMessage(), 0, 255),
                ]);
                $err++;
                log_message('error', 'Notifier: ' . $e->getMessage());
            }
        }
        return ['procesadas' => $ok, 'fallidas' => $err];
    }

    private function _enviar(array $r): void
    {
        if (!class_exists('\PHPMailer\PHPMailer\PHPMailer')) {
            throw new RuntimeException('PHPMailer no instalado. Ejecutar composer install.');
        }
        if (empty($r['destinatario_email'])) {
            throw new RuntimeException('Notificación sin destinatario.');
        }

        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = (string) $this->CI->config->item('app_smtp_host');
        $mail->Port       = (int)    $this->CI->config->item('app_smtp_port');
        $mail->SMTPAuth   = true;
        $mail->Username   = (string) $this->CI->config->item('app_smtp_user');
        $mail->Password   = (string) $this->CI->config->item('app_smtp_pass');
        $mail->SMTPSecure = (string) $this->CI->config->item('app_smtp_encryption');
        $mail->CharSet    = 'UTF-8';
        $mail->setFrom(
            (string) $this->CI->config->item('app_smtp_from_email'),
            (string) $this->CI->config->item('app_smtp_from_name')
        );
        $mail->addAddress($r['destinatario_email']);
        $mail->Subject = $r['asunto'];
        $mail->Body    = $r['cuerpo'];
        $mail->AltBody = strip_tags($r['cuerpo']);
        $mail->isHTML(true);
        $mail->send();
    }

    private function _renderHtml(string $asunto, string $cuerpoTexto): string
    {
        $body = nl2br(htmlspecialchars($cuerpoTexto, ENT_QUOTES, 'UTF-8'));
        return <<<HTML
<!doctype html>
<html lang="es"><body style="font-family:Arial,sans-serif;background:#f4f6f9;padding:24px;color:#222">
  <table role="presentation" width="100%" style="max-width:560px;margin:0 auto;background:#fff;border-radius:8px;padding:24px">
    <tr><td>
      <h2 style="color:#0d6efd;margin:0 0 16px">{$asunto}</h2>
      <div style="font-size:14px;line-height:1.5">{$body}</div>
      <hr style="border:none;border-top:1px solid #eee;margin:24px 0">
      <p style="font-size:12px;color:#888">ERP GMC — Constructora GMC. Este es un mensaje automático.</p>
    </td></tr>
  </table>
</body></html>
HTML;
    }
}
