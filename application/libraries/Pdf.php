<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Pdf — wrapper simple sobre mPDF para generar PDFs desde HTML.
 *
 * Requiere mpdf/mpdf vía composer.
 *
 * Uso:
 *   $this->load->library('Pdf');
 *   $this->pdf->renderHtml($html, 'cierre-OBR-001.pdf');                       // descarga
 *   $this->pdf->renderHtml($html, 'cierre-OBR-001.pdf', 'inline');             // muestra inline
 *   $bytes = $this->pdf->renderHtml($html, 'cierre.pdf', 'string');            // string para guardar
 */
class Pdf
{
    protected $defaultConfig = [
        'mode'          => 'utf-8',
        'format'        => 'A4',
        'margin_left'   => 15,
        'margin_right'  => 15,
        'margin_top'    => 18,
        'margin_bottom' => 18,
        'margin_header' => 8,
        'margin_footer' => 8,
        'tempDir'       => null,
    ];

    public function __construct()
    {
        if (!class_exists('\Mpdf\Mpdf')) {
            log_message('error', 'mPDF no instalado. composer require mpdf/mpdf');
        }
    }

    /**
     * @param string $html
     * @param string $filename
     * @param string $output  'D'=descarga (default), 'I'=inline, 'S'=string
     * @return string|null
     */
    public function renderHtml(string $html, string $filename = 'documento.pdf', string $output = 'D'): ?string
    {
        if (!class_exists('\Mpdf\Mpdf')) {
            throw new RuntimeException('mPDF no está instalado. Ejecuta composer install.');
        }
        $cfg = $this->defaultConfig;
        $cfg['tempDir'] = sys_get_temp_dir() . '/mpdf';
        if (!is_dir($cfg['tempDir'])) @mkdir($cfg['tempDir'], 0700, true);

        $mpdf = new \Mpdf\Mpdf($cfg);
        $mpdf->WriteHTML($html);

        $modeMap = ['D' => \Mpdf\Output\Destination::DOWNLOAD, 'I' => \Mpdf\Output\Destination::INLINE,
                    'S' => \Mpdf\Output\Destination::STRING_RETURN, 'F' => \Mpdf\Output\Destination::FILE];
        $mode = $modeMap[strtoupper($output)] ?? \Mpdf\Output\Destination::DOWNLOAD;

        if ($mode === \Mpdf\Output\Destination::STRING_RETURN) {
            return $mpdf->Output($filename, $mode);
        }
        $mpdf->Output($filename, $mode);
        return null;
    }
}
