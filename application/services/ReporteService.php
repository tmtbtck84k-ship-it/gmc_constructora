<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * ReporteService — Consultas analíticas para los 3 reportes Fase 1.
 *
 * Consolida en CLP (moneda funcional) usando monto_total_clp snapshot
 * que viene desde SDP/Compra/Rinde.
 */
class ReporteService
{
    /** @var CI_Controller */
    protected $CI;

    public function __construct()
    {
        $this->CI =& get_instance();
    }

    // ============================================================
    // 1) ESTADO DE PAGOS — listado de SDPs con filtros
    // ============================================================
    public function estadoPagos(array $f = []): array
    {
        $sql = "SELECT s.id, s.numero, s.fecha_emision, s.fecha_programada, s.fecha_pago,
                       p.codigo AS proyecto_codigo, p.nombre AS proyecto_nombre,
                       cc.codigo AS cc_codigo, cc.nombre AS cc_nombre,
                       pr.razon_social AS proveedor, pr.rut AS proveedor_rut,
                       tg.codigo AS tg_codigo,
                       m.codigo AS moneda, m.simbolo AS moneda_simbolo, m.decimales AS moneda_decimales,
                       s.monto_total, s.tipo_cambio_clp, s.monto_total_clp,
                       e.codigo AS estado_codigo, e.nombre AS estado_nombre, e.color AS estado_color,
                       s.forma_pago
                  FROM gmc_solicitudes_pago s
                  LEFT JOIN gmc_proyectos p     ON p.id = s.proyecto_id
                  LEFT JOIN gmc_centros_costo cc ON cc.id = s.centro_costo_id
                  LEFT JOIN gmc_proveedores pr   ON pr.id = s.proveedor_id
                  LEFT JOIN gmc_tipos_gasto tg   ON tg.id = s.tipo_gasto_id
                  LEFT JOIN gmc_monedas m        ON m.id  = s.moneda_id
                  LEFT JOIN gmc_estados e        ON e.id  = s.estado_id
                 WHERE s.deleted_at IS NULL";

        $params = [];
        if (!empty($f['proyecto_id']))  { $sql .= ' AND s.proyecto_id = ?';  $params[] = (int)$f['proyecto_id']; }
        if (!empty($f['proveedor_id'])) { $sql .= ' AND s.proveedor_id = ?'; $params[] = (int)$f['proveedor_id']; }
        if (!empty($f['estado_id']))    { $sql .= ' AND s.estado_id = ?';    $params[] = (int)$f['estado_id']; }
        if (!empty($f['desde']))        { $sql .= ' AND s.fecha_emision >= ?'; $params[] = $f['desde']; }
        if (!empty($f['hasta']))        { $sql .= ' AND s.fecha_emision <= ?'; $params[] = $f['hasta']; }
        $sql .= ' ORDER BY s.fecha_emision DESC, s.id DESC';

        $rows = $this->CI->db->query($sql, $params)->result_array();

        // Totales por estado (en CLP)
        $totales = [];
        foreach ($rows as $r) {
            $k = $r['estado_codigo'];
            if (!isset($totales[$k])) $totales[$k] = ['nombre' => $r['estado_nombre'], 'color' => $r['estado_color'], 'count' => 0, 'monto_clp' => 0];
            $totales[$k]['count']++;
            $totales[$k]['monto_clp'] += (float)($r['monto_total_clp'] ?? 0);
        }
        return ['rows' => $rows, 'totales' => $totales];
    }

    // ============================================================
    // 2) GASTOS POR OBRA Y CC — consolidado SDP + Compras + Rindes
    // ============================================================
    public function gastosPorObra(array $f = []): array
    {
        $whereDesde = !empty($f['desde']) ? " AND fecha_ref >= '" . $this->CI->db->escape_str($f['desde']) . "'" : '';
        $whereHasta = !empty($f['hasta']) ? " AND fecha_ref <= '" . $this->CI->db->escape_str($f['hasta']) . "'" : '';
        $whereProy  = !empty($f['proyecto_id']) ? " AND proyecto_id = " . (int)$f['proyecto_id'] : '';

        // UNION: SDP pagadas + Compras recibidas + Rindes pagados (todos en CLP)
        $sql = "
            SELECT proyecto_id, proyecto_codigo, proyecto_nombre,
                   cc_id, cc_codigo, cc_nombre,
                   tg_codigo, tg_nombre,
                   origen,
                   SUM(monto_clp) AS total_clp,
                   COUNT(*) AS docs
            FROM (
                -- SDPs pagadas
                SELECT s.proyecto_id, p.codigo AS proyecto_codigo, p.nombre AS proyecto_nombre,
                       s.centro_costo_id AS cc_id, cc.codigo AS cc_codigo, cc.nombre AS cc_nombre,
                       tg.codigo AS tg_codigo, tg.nombre AS tg_nombre,
                       'SDP' AS origen,
                       COALESCE(s.monto_total_clp, s.monto_total) AS monto_clp,
                       s.fecha_pago AS fecha_ref
                  FROM gmc_solicitudes_pago s
                  LEFT JOIN gmc_proyectos p    ON p.id = s.proyecto_id
                  LEFT JOIN gmc_centros_costo cc ON cc.id = s.centro_costo_id
                  LEFT JOIN gmc_tipos_gasto tg ON tg.id = s.tipo_gasto_id
                  LEFT JOIN gmc_estados e ON e.id = s.estado_id
                 WHERE s.deleted_at IS NULL
                   AND e.codigo = 'pagada'
                   AND s.fecha_pago IS NOT NULL

                UNION ALL

                -- Compras recibidas (consolida items por compra)
                SELECT c.proyecto_id, p.codigo AS proyecto_codigo, p.nombre AS proyecto_nombre,
                       c.centro_costo_id AS cc_id, cc.codigo AS cc_codigo, cc.nombre AS cc_nombre,
                       NULL AS tg_codigo, NULL AS tg_nombre,
                       'Compra' AS origen,
                       COALESCE(c.monto_total_clp, c.monto_total) AS monto_clp,
                       c.fecha_recepcion AS fecha_ref
                  FROM gmc_compras c
                  LEFT JOIN gmc_proyectos p     ON p.id = c.proyecto_id
                  LEFT JOIN gmc_centros_costo cc ON cc.id = c.centro_costo_id
                  LEFT JOIN gmc_estados e ON e.id = c.estado_id
                 WHERE c.deleted_at IS NULL
                   AND e.codigo = 'recibida'

                UNION ALL

                -- Rindes pagados
                SELECT r.proyecto_id, p.codigo AS proyecto_codigo, p.nombre AS proyecto_nombre,
                       r.centro_costo_id AS cc_id, cc.codigo AS cc_codigo, cc.nombre AS cc_nombre,
                       NULL AS tg_codigo, NULL AS tg_nombre,
                       'Rinde' AS origen,
                       COALESCE(r.monto_total_clp, r.monto_total) AS monto_clp,
                       r.fecha_rendicion AS fecha_ref
                  FROM gmc_rindes_gastos r
                  LEFT JOIN gmc_proyectos p     ON p.id = r.proyecto_id
                  LEFT JOIN gmc_centros_costo cc ON cc.id = r.centro_costo_id
                  LEFT JOIN gmc_estados e ON e.id = r.estado_id
                 WHERE r.deleted_at IS NULL
                   AND e.codigo IN ('pagada','aprobada')
            ) gastos
            WHERE 1=1 {$whereDesde} {$whereHasta} {$whereProy}
            GROUP BY proyecto_id, proyecto_codigo, proyecto_nombre,
                     cc_id, cc_codigo, cc_nombre,
                     tg_codigo, tg_nombre, origen
            ORDER BY proyecto_codigo, cc_codigo, origen
        ";
        $rows = $this->CI->db->query($sql)->result_array();

        // Totales globales
        $totalGlobal = 0;
        foreach ($rows as $r) $totalGlobal += (float)$r['total_clp'];

        return ['rows' => $rows, 'total_global' => $totalGlobal];
    }

    // ============================================================
    // 3) DESVIACIÓN — presupuesto vigente vs gasto real
    // ============================================================
    public function desviacion(int $proyectoId): array
    {
        // Presupuesto vigente
        $presupuesto = $this->CI->db->query("
            SELECT pr.*, m.codigo AS moneda
              FROM gmc_presupuestos_obra pr
              LEFT JOIN gmc_monedas m ON m.id = pr.moneda_id
             WHERE pr.proyecto_id = ? AND pr.vigente = 1 AND pr.deleted_at IS NULL
             LIMIT 1
        ", [$proyectoId])->row_array();

        if (!$presupuesto) {
            return [
                'presupuesto' => null,
                'lineas' => [],
                'totales' => ['presupuestado' => 0, 'real' => 0, 'desv' => 0, 'desv_pct' => 0],
            ];
        }

        // Líneas de presupuesto
        $lineas = $this->CI->db->query("
            SELECT pi.id, pi.centro_costo_id, pi.tipo_gasto_id,
                   cc.codigo AS cc_codigo, cc.nombre AS cc_nombre,
                   tg.codigo AS tg_codigo, tg.nombre AS tg_nombre,
                   pi.descripcion, pi.monto AS presupuestado
              FROM gmc_presupuestos_obra_items pi
              LEFT JOIN gmc_centros_costo cc ON cc.id = pi.centro_costo_id
              LEFT JOIN gmc_tipos_gasto tg ON tg.id = pi.tipo_gasto_id
             WHERE pi.presupuesto_id = ?
        ", [$presupuesto['id']])->result_array();

        // Real ejecutado por CC + tipo de gasto (consolidado SDP+Compras+Rindes en CLP)
        $real = $this->CI->db->query("
            SELECT cc_id, tg_id, SUM(monto_clp) AS real_clp
            FROM (
                SELECT s.centro_costo_id AS cc_id, s.tipo_gasto_id AS tg_id,
                       COALESCE(s.monto_total_clp, s.monto_total) AS monto_clp
                  FROM gmc_solicitudes_pago s
                  LEFT JOIN gmc_estados e ON e.id = s.estado_id
                 WHERE s.proyecto_id = ?
                   AND s.deleted_at IS NULL
                   AND e.codigo = 'pagada'

                UNION ALL

                SELECT ci.compra_id AS dummy_drop, NULL AS tg_id_drop, NULL AS monto_drop
                FROM gmc_compras_items ci LIMIT 0
            ) t
            GROUP BY cc_id, tg_id
        ", [$proyectoId])->result_array();

        // Indexar real por (cc_id, tg_id)
        $realMap = [];
        foreach ($real as $r) {
            $key = ($r['cc_id'] ?? '0') . '_' . ($r['tg_id'] ?? '0');
            $realMap[$key] = (float)$r['real_clp'];
        }

        // Componer salida: cada línea de presupuesto + real correspondiente
        $totalPres = 0;
        $totalReal = 0;
        $out = [];
        foreach ($lineas as $l) {
            $key = $l['centro_costo_id'] . '_' . $l['tipo_gasto_id'];
            $r = $realMap[$key] ?? 0;
            $desv = $r - (float)$l['presupuestado'];
            $desvPct = $l['presupuestado'] > 0 ? round(($desv / $l['presupuestado']) * 100, 1) : 0;

            $semaforo = 'success';
            if ($desvPct > 0 && $desvPct <= 5)   $semaforo = 'warning';
            elseif ($desvPct > 5)                 $semaforo = 'danger';

            $out[] = array_merge($l, [
                'real_clp' => $r,
                'desv'     => $desv,
                'desv_pct' => $desvPct,
                'semaforo' => $semaforo,
            ]);
            $totalPres += (float)$l['presupuestado'];
            $totalReal += $r;
            unset($realMap[$key]);
        }

        // Líneas de gasto sin presupuesto (sobrepasos)
        foreach ($realMap as $key => $monto) {
            [$ccId, $tgId] = explode('_', $key);
            $cc = $this->CI->db->query('SELECT codigo, nombre FROM gmc_centros_costo WHERE id = ?', [(int)$ccId])->row_array();
            $tg = $this->CI->db->query('SELECT codigo, nombre FROM gmc_tipos_gasto WHERE id = ?', [(int)$tgId])->row_array();
            $out[] = [
                'cc_codigo' => $cc['codigo'] ?? '?', 'cc_nombre' => $cc['nombre'] ?? '?',
                'tg_codigo' => $tg['codigo'] ?? '?', 'tg_nombre' => $tg['nombre'] ?? '?',
                'descripcion' => '<gasto sin presupuestar>',
                'presupuestado' => 0,
                'real_clp' => $monto,
                'desv' => $monto,
                'desv_pct' => 100,
                'semaforo' => 'danger',
            ];
            $totalReal += $monto;
        }

        $desvGlobal = $totalReal - $totalPres;
        $desvGlobalPct = $totalPres > 0 ? round(($desvGlobal / $totalPres) * 100, 1) : 0;

        return [
            'presupuesto' => $presupuesto,
            'lineas'      => $out,
            'totales'     => [
                'presupuestado' => $totalPres,
                'real'          => $totalReal,
                'desv'          => $desvGlobal,
                'desv_pct'      => $desvGlobalPct,
            ],
        ];
    }

    // ============================================================
    // 4) KPIs DEL DASHBOARD
    // ============================================================
    public function kpisDashboard(): array
    {
        $cnt = function (string $codigo) {
            $r = $this->CI->db->query("
                SELECT COUNT(*) AS c FROM gmc_solicitudes_pago s
                JOIN gmc_estados e ON e.id = s.estado_id AND e.dominio = 'solicitud_pago' AND e.codigo = ?
                WHERE s.deleted_at IS NULL", [$codigo])->row();
            return (int)$r->c;
        };

        $pagadasMes = $this->CI->db->query("
            SELECT COUNT(*) AS c, COALESCE(SUM(monto_total_clp),0) AS total
              FROM gmc_solicitudes_pago s
              JOIN gmc_estados e ON e.id = s.estado_id AND e.codigo = 'pagada'
             WHERE s.deleted_at IS NULL
               AND YEAR(s.fecha_pago) = YEAR(CURDATE())
               AND MONTH(s.fecha_pago) = MONTH(CURDATE())
        ")->row();

        $proyectosActivos = $this->CI->db->query("
            SELECT COUNT(*) AS c FROM gmc_proyectos p
              JOIN gmc_estados e ON e.id = p.estado_id
             WHERE p.deleted_at IS NULL
               AND e.codigo IN ('planificacion','en_ejecucion','pausado')
        ")->row();

        $gastoMes = $this->CI->db->query("
            SELECT COALESCE(SUM(t.monto_clp),0) AS total FROM (
                SELECT COALESCE(s.monto_total_clp, s.monto_total) AS monto_clp
                  FROM gmc_solicitudes_pago s
                  JOIN gmc_estados e ON e.id = s.estado_id AND e.codigo = 'pagada'
                 WHERE s.deleted_at IS NULL
                   AND YEAR(s.fecha_pago)=YEAR(CURDATE())
                   AND MONTH(s.fecha_pago)=MONTH(CURDATE())
                UNION ALL
                SELECT COALESCE(c.monto_total_clp, c.monto_total)
                  FROM gmc_compras c
                  JOIN gmc_estados e ON e.id = c.estado_id AND e.codigo = 'recibida'
                 WHERE c.deleted_at IS NULL
                   AND YEAR(c.fecha_recepcion)=YEAR(CURDATE())
                   AND MONTH(c.fecha_recepcion)=MONTH(CURDATE())
            ) t
        ")->row();

        return [
            'sdp_pendientes'  => $cnt('pendiente'),
            'sdp_validadas'   => $cnt('validada'),
            'sdp_programadas' => $cnt('programada'),
            'sdp_pagadas_mes' => (int)$pagadasMes->c,
            'monto_pagadas_mes_clp' => (float)$pagadasMes->total,
            'proyectos_activos' => (int)$proyectosActivos->c,
            'gasto_mes_clp'   => (float)$gastoMes->total,
        ];
    }

    /** Serie mensual de gasto (últimos 12 meses) para gráfico de dashboard. */
    public function gastoUltimos12Meses(): array
    {
        $rows = $this->CI->db->query("
            SELECT mes, SUM(monto_clp) AS total
            FROM (
                SELECT DATE_FORMAT(s.fecha_pago, '%Y-%m') AS mes, COALESCE(s.monto_total_clp, s.monto_total) AS monto_clp
                  FROM gmc_solicitudes_pago s
                  JOIN gmc_estados e ON e.id = s.estado_id AND e.codigo = 'pagada'
                 WHERE s.deleted_at IS NULL
                   AND s.fecha_pago >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
                UNION ALL
                SELECT DATE_FORMAT(c.fecha_recepcion, '%Y-%m'), COALESCE(c.monto_total_clp, c.monto_total)
                  FROM gmc_compras c
                  JOIN gmc_estados e ON e.id = c.estado_id AND e.codigo = 'recibida'
                 WHERE c.deleted_at IS NULL
                   AND c.fecha_recepcion >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
            ) t
            GROUP BY mes ORDER BY mes
        ")->result_array();

        // Llenar meses sin data con 0
        $serie = [];
        for ($i = 11; $i >= 0; $i--) {
            $m = date('Y-m', strtotime("-{$i} months"));
            $serie[$m] = 0;
        }
        foreach ($rows as $r) $serie[$r['mes']] = (float)$r['total'];
        return $serie;
    }
}
