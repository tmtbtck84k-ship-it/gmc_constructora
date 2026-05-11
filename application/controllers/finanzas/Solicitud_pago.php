<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'services/SolicitudPagoService.php';

class Solicitud_pago extends MY_AuthController
{
    /** @var SolicitudPagoService */
    protected $sdp;

    public function __construct()
    {
        parent::__construct();
        $this->load->model([
            'SolicitudPagoRepo','SdpEstadoLogRepo',
            'ProyectoRepo','ProveedorRepo','TipoGastoRepo',
            'CentroCostoRepo','MonedaRepo','TipoCambioRepo',
        ]);
        $this->load->library('Uploader');
        $this->sdp = new SolicitudPagoService();
    }

    // ============================================================
    // BANDEJA (S2-09)
    // ============================================================
    public function index()
    {
        $this->require_permission('finanzas.sdp.ver');

        $page  = max(1, (int)$this->input->get('page'));
        $limit = 50;
        $offset = ($page - 1) * $limit;

        $filters = [
            'q'             => trim((string)$this->input->get('q')),
            'proyecto_id'   => $this->input->get('proyecto_id'),
            'proveedor_id'  => $this->input->get('proveedor_id'),
            'estado_id'     => $this->input->get('estado_id'),
            'cc_id'         => $this->input->get('cc_id'),
            'desde'         => $this->input->get('desde'),
            'hasta'         => $this->input->get('hasta'),
        ];
        $result = $this->SolicitudPagoRepo->bandeja($filters, $limit, $offset);

        $this->view('finanzas/solicitud_pago/index', [
            'rows'       => $result['rows'],
            'total'      => $result['total'],
            'page'       => $page,
            'totalPages' => (int)ceil($result['total'] / $limit),
            'filters'    => $filters,
            'proyectos'  => $this->ProyectoRepo->activos(),
            'proveedores'=> $this->ProveedorRepo->activos(),
            'estados'    => $this->db->where('dominio','solicitud_pago')->order_by('orden')->get('gmc_estados')->result_array(),
        ]);
    }

    // ============================================================
    // VER (S2-07)
    // ============================================================
    public function ver(int $id)
    {
        $this->require_permission('finanzas.sdp.ver');
        $sdp = $this->SolicitudPagoRepo->findFull($id);
        if (!$sdp) show_404();

        $timeline = $this->SdpEstadoLogRepo->timeline($id);
        $adjuntos = $this->uploader->listFor('solicitud_pago', $id);

        $this->view('finanzas/solicitud_pago/ver', [
            'sdp'      => $sdp,
            'timeline' => $timeline,
            'adjuntos' => $adjuntos,
        ]);
    }

    // ============================================================
    // CREAR (S2-01)
    // ============================================================
    public function crear()
    {
        $this->require_permission('finanzas.sdp.crear');
        if ($this->input->method() !== 'post') {
            return $this->_renderForm(null);
        }

        try {
            $input = $this->input->post();
            // Cálculo del monto_total a partir de neto+iva (para asegurar consistencia)
            $input['monto_total'] = (float)($input['monto_neto'] ?? 0) + (float)($input['monto_iva'] ?? 0);

            $sdpId = $this->sdp->crear($input, $this->user_id());

            // Adjuntos opcionales en el mismo POST
            if (!empty($_FILES['archivo']['name'])) {
                $this->uploader->store($_FILES['archivo'], 'solicitud_pago', $sdpId, $input['categoria_adjunto'] ?? 'factura');
            }

            $this->flash('success', 'Solicitud de Pago creada correctamente.');
            redirect(base_url("finanzas/sdp/{$sdpId}"));
        } catch (\Throwable $e) {
            log_message('error', 'SDP crear: ' . $e->getMessage());
            $this->flash('error', 'Error al crear: ' . $e->getMessage());
            redirect(base_url('finanzas/sdp/crear'));
        }
    }

    // ============================================================
    // EDITAR (S2-02) — sólo Pendiente
    // ============================================================
    public function editar(int $id)
    {
        $this->require_permission('finanzas.sdp.editar');
        $sdp = $this->SolicitudPagoRepo->findFull($id);
        if (!$sdp) show_404();
        if ($sdp['estado_codigo'] !== 'pendiente') {
            $this->flash('error', 'Sólo se pueden editar SDPs en estado Pendiente.');
            redirect(base_url("finanzas/sdp/{$id}"));
        }

        if ($this->input->method() !== 'post') {
            return $this->_renderForm($sdp);
        }

        try {
            $input = $this->input->post();
            $input['monto_total'] = (float)($input['monto_neto'] ?? 0) + (float)($input['monto_iva'] ?? 0);
            $this->sdp->editar($id, $input, $this->user_id());
            $this->flash('success', 'SDP actualizada.');
            redirect(base_url("finanzas/sdp/{$id}"));
        } catch (\Throwable $e) {
            log_message('error', 'SDP editar: ' . $e->getMessage());
            $this->flash('error', 'Error al editar: ' . $e->getMessage());
            redirect(base_url("finanzas/sdp/{$id}/editar"));
        }
    }

    // ============================================================
    // TRANSICIONES (S2-03..06)
    // ============================================================
    public function validar(int $id)
    {
        $this->require_permission('finanzas.sdp.validar');
        $this->_runTransicion($id, fn() => $this->sdp->validar($id, $this->user_id()), 'validada');
    }

    public function programar(int $id)
    {
        $this->require_permission('finanzas.sdp.programar');
        if ($this->input->method() !== 'post') {
            $sdp = $this->SolicitudPagoRepo->findFull($id);
            if (!$sdp) show_404();
            return $this->view('finanzas/solicitud_pago/programar', ['sdp' => $sdp]);
        }
        $fecha = (string)$this->input->post('fecha_programada');
        $forma = (string)$this->input->post('forma_pago');
        $this->_runTransicion($id, fn() => $this->sdp->programar($id, $this->user_id(), $fecha, $forma), 'programada');
    }

    public function pagar(int $id)
    {
        $this->require_permission('finanzas.sdp.pagar');
        if ($this->input->method() !== 'post') {
            $sdp = $this->SolicitudPagoRepo->findFull($id);
            if (!$sdp) show_404();
            return $this->view('finanzas/solicitud_pago/pagar', ['sdp' => $sdp]);
        }
        $fecha = (string)$this->input->post('fecha_pago');
        $this->_runTransicion($id, fn() => $this->sdp->pagar($id, $this->user_id(), $fecha), 'pagada');
    }

    public function rechazar(int $id)
    {
        $this->require_permission('finanzas.sdp.rechazar');
        if ($this->input->method() !== 'post') {
            $sdp = $this->SolicitudPagoRepo->findFull($id);
            if (!$sdp) show_404();
            return $this->view('finanzas/solicitud_pago/rechazar', ['sdp' => $sdp]);
        }
        $motivo = (string)$this->input->post('motivo');
        $this->_runTransicion($id, fn() => $this->sdp->rechazar($id, $this->user_id(), $motivo), 'rechazada');
    }

    public function eliminar(int $id)
    {
        $this->require_permission('finanzas.sdp.eliminar');
        try {
            $this->sdp->eliminar($id, $this->user_id());
            $this->flash('success', 'SDP eliminada.');
        } catch (\Throwable $e) {
            $this->flash('error', $e->getMessage());
        }
        redirect(base_url('finanzas/sdp'));
    }

    // ============================================================
    // EXPORTAR (S2-10 CSV / S2-11 Excel)
    // ============================================================
    public function exportar()
    {
        $this->require_permission('finanzas.sdp.exportar');

        $filters = [
            'q'             => trim((string)$this->input->get('q')),
            'proyecto_id'   => $this->input->get('proyecto_id'),
            'proveedor_id'  => $this->input->get('proveedor_id'),
            'estado_id'     => $this->input->get('estado_id'),
            'cc_id'         => $this->input->get('cc_id'),
            'desde'         => $this->input->get('desde'),
            'hasta'         => $this->input->get('hasta'),
        ];
        $formato = $this->input->get('formato') === 'xlsx' ? 'xlsx' : 'csv';

        $result = $this->SolicitudPagoRepo->bandeja($filters, 50000, 0);
        $rows = $result['rows'];

        $headers = [
            'Nº SDP','Fecha emisión','Fecha vencimiento','Fecha programada','Fecha pago',
            'Proyecto','Proyecto nombre','Centro Costo','Tipo Gasto',
            'Proveedor RUT','Proveedor','Documento tipo','Documento Nº',
            'Moneda','Monto Neto','IVA','Monto Total','TC CLP','Total CLP',
            'Estado','Forma de pago','Descripción','Motivo rechazo',
        ];

        $body = [];
        foreach ($rows as $r) {
            $body[] = [
                $r['numero'],
                $r['fecha_emision'],
                $r['fecha_vencimiento'],
                $r['fecha_programada'],
                $r['fecha_pago'],
                $r['proyecto_codigo'] ?? '',
                $r['proyecto_nombre'] ?? '',
                $r['cc_codigo'] ?? '',
                $r['tg_codigo'] ?? '',
                $r['proveedor_rut'] ?? '',
                $r['proveedor'] ?? '',
                $r['documento_tipo'] ?? '',
                $r['documento_numero'] ?? '',
                $r['moneda'] ?? '',
                $r['monto_neto'],
                $r['monto_iva'],
                $r['monto_total'],
                $r['tipo_cambio_clp'] ?? '',
                $r['monto_total_clp'] ?? '',
                $r['estado_nombre'] ?? '',
                $r['forma_pago'] ?? '',
                $r['descripcion'] ?? '',
                $r['motivo_rechazo'] ?? '',
            ];
        }

        $this->audit->log('sdp.exportar', 'gmc_solicitudes_pago', null, null, [
            'formato' => $formato, 'filtros' => $filters, 'filas' => count($body),
        ]);

        if ($formato === 'xlsx') {
            $this->_exportXlsx($headers, $body);
        } else {
            $this->_exportCsv($headers, $body);
        }
    }

    private function _exportCsv(array $headers, array $body): void
    {
        $filename = 'sdp_' . date('Ymd_His') . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header("Content-Disposition: attachment; filename=\"{$filename}\"");
        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF"); // BOM UTF-8 para Excel
        fputcsv($out, $headers);
        foreach ($body as $row) fputcsv($out, $row);
        fclose($out);
        exit;
    }

    private function _exportXlsx(array $headers, array $body): void
    {
        if (!class_exists('\PhpOffice\PhpSpreadsheet\Spreadsheet')) {
            $this->flash('error', 'PhpSpreadsheet no instalado. Ejecuta composer install.');
            redirect(base_url('finanzas/sdp'));
        }
        $ss = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $ss->getActiveSheet();
        $sheet->setTitle('SDP');

        // Encabezados
        foreach ($headers as $i => $h) {
            $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 1);
            $sheet->setCellValue("{$col}1", $h);
            $sheet->getStyle("{$col}1")->getFont()->setBold(true);
        }

        // Filas
        foreach ($body as $r => $row) {
            foreach ($row as $c => $val) {
                $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c + 1);
                $sheet->setCellValue("{$col}" . ($r + 2), $val);
            }
        }
        $sheet->freezePane('A2');
        foreach (range(1, count($headers)) as $i) {
            $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i);
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = 'sdp_' . date('Ymd_His') . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header("Content-Disposition: attachment; filename=\"{$filename}\"");
        header('Cache-Control: max-age=0');
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($ss);
        $writer->save('php://output');
        exit;
    }

    // ============================================================
    // INTERNOS
    // ============================================================
    private function _runTransicion(int $id, callable $fn, string $estadoLabel): void
    {
        try {
            $fn();
            $this->flash('success', "SDP marcada como {$estadoLabel}.");
            redirect(base_url("finanzas/sdp/{$id}"));
        } catch (\Throwable $e) {
            log_message('error', "SDP transicion {$estadoLabel}: " . $e->getMessage());
            $this->flash('error', $e->getMessage());
            redirect(base_url("finanzas/sdp/{$id}"));
        }
    }

    private function _renderForm(?array $sdp): void
    {
        $proyectos  = $this->ProyectoRepo->activos();
        $proveedores= $this->ProveedorRepo->activos();
        $tiposGasto = $this->TipoGastoRepo->activos();
        $monedas    = $this->MonedaRepo->activas();

        // Centros de costo: si hay proyecto, los del proyecto + el general; si no, sólo el general
        $proyId = $sdp ? ($sdp['proyecto_id'] ? (int)$sdp['proyecto_id'] : null) : null;
        $ccsProyecto = $proyId ? $this->CentroCostoRepo->listByProyecto($proyId) : [];
        $ccGeneral   = $this->CentroCostoRepo->listByProyecto(null);
        $centros     = array_merge($ccsProyecto, $ccGeneral);

        $this->view('finanzas/solicitud_pago/form', [
            'sdp'         => $sdp,
            'is_edit'     => $sdp !== null,
            'proyectos'   => $proyectos,
            'proveedores' => $proveedores,
            'tipos_gasto' => $tiposGasto,
            'monedas'     => $monedas,
            'centros'     => $centros,
        ]);
    }
}
