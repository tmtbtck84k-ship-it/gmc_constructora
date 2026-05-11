<?php
// Calcular rango de fechas para barra horizontal
$minDate = null; $maxDate = null;
foreach ($actividades as $a) {
    if (!$minDate || $a['fecha_inicio_planificada'] < $minDate)   $minDate = $a['fecha_inicio_planificada'];
    if (!$maxDate || $a['fecha_termino_planificada'] > $maxDate)  $maxDate = $a['fecha_termino_planificada'];
}
$totalDias = ($minDate && $maxDate)
    ? max(1, (strtotime($maxDate) - strtotime($minDate)) / 86400 + 1)
    : 1;

// Mapa de hito_id -> color
$paleta = ['#0d6efd','#6f42c1','#fd7e14','#198754','#dc3545','#20c997','#6610f2','#e83e8c'];
$colorByHito = [];
foreach ($hitos as $idx => $h) {
    $colorByHito[(int)$h['id']] = $paleta[$idx % count($paleta)];
}

// Mapa de dependencias por sucesor
$depMap = [];
foreach ($deps as $d) {
    $depMap[(int)$d['actividad_id']][] = $d;
}
?>
<style>
    body { font-family: Arial, sans-serif; font-size: 9pt; color: #212529; }
    h1 { font-size: 16pt; margin: 0 0 4px 0; }
    .meta { color: #6c757d; font-size: 9pt; margin-bottom: 12px; }
    table.gantt-table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
    table.gantt-table th { background: #f8f9fa; padding: 4px; border: 1px solid #dee2e6; font-size: 8pt; }
    table.gantt-table td { padding: 4px; border: 1px solid #dee2e6; font-size: 8pt; vertical-align: middle; }
    .barra-track { background: #e9ecef; height: 16px; border-radius: 2px; position: relative; }
    .barra      { height: 16px; border-radius: 2px; }
    .critica    { background: #dc3545 !important; }
    .legend { font-size: 8pt; margin-top: 12px; }
    .legend .swatch { display: inline-block; width: 12px; height: 12px; vertical-align: middle; margin-right: 4px; border-radius: 2px;}
    .footer { font-size: 7pt; color: #6c757d; margin-top: 16px; text-align: right; }
</style>

<h1>Diagrama de Gantt — <?= htmlspecialchars($proyecto['codigo']) ?></h1>
<div class="meta">
    <?= htmlspecialchars($proyecto['nombre']) ?> ·
    Cliente: <?= htmlspecialchars($proyecto['cliente'] ?? '—') ?> ·
    Estado: <?= htmlspecialchars($proyecto['estado_nombre'] ?? '—') ?> ·
    Calendario: <?= htmlspecialchars($proyecto['dias_laborales'] ?? 'lun_vie') ?>
    <?= !empty($proyecto['trabaja_feriados']) ? ' (trabaja feriados)' : '' ?> ·
    Generado: <?= htmlspecialchars($fecha) ?>
</div>

<?php if (empty($actividades)): ?>
    <p>El proyecto no tiene actividades registradas.</p>
<?php else: ?>

<table class="gantt-table">
    <thead>
        <tr>
            <th width="9%">Código</th>
            <th width="22%">Actividad</th>
            <th width="9%">Hito</th>
            <th width="8%">Inicio</th>
            <th width="8%">Término</th>
            <th width="4%">Días</th>
            <th width="7%">Avance</th>
            <th width="33%">Línea de tiempo (<?= htmlspecialchars($minDate) ?> → <?= htmlspecialchars($maxDate) ?>)</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($actividades as $a):
        $left  = $minDate ? max(0, ((strtotime($a['fecha_inicio_planificada']) - strtotime($minDate))/86400) / $totalDias * 100) : 0;
        $width = $minDate ? max(1, ((strtotime($a['fecha_termino_planificada']) - strtotime($a['fecha_inicio_planificada']))/86400 + 1) / $totalDias * 100) : 0;
        $color = (int)$a['es_critica'] === 1
            ? '#dc3545'
            : ($colorByHito[(int)$a['hito_id']] ?? '#6c757d');
    ?>
        <tr>
            <td><code><?= htmlspecialchars($a['codigo']) ?></code></td>
            <td><?= htmlspecialchars($a['nombre']) ?></td>
            <td><?= htmlspecialchars($a['hito_codigo'] ?? '—') ?></td>
            <td><?= htmlspecialchars($a['fecha_inicio_planificada']) ?></td>
            <td><?= htmlspecialchars($a['fecha_termino_planificada']) ?></td>
            <td style="text-align:center;"><?= (int)$a['duracion_dias'] ?></td>
            <td style="text-align:right;"><?= number_format((float)$a['porcentaje_avance'], 0) ?>%</td>
            <td>
                <div class="barra-track">
                    <div class="barra <?= (int)$a['es_critica'] === 1 ? 'critica' : '' ?>"
                         style="background: <?= $color ?>;
                                width: <?= number_format($width, 2, '.', '') ?>%;
                                margin-left: <?= number_format($left, 2, '.', '') ?>%;">
                    </div>
                </div>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<div class="legend">
    <strong>Leyenda:</strong>
    <?php foreach ($hitos as $h): ?>
        <span style="margin-right:12px;">
            <span class="swatch" style="background: <?= $colorByHito[(int)$h['id']] ?>"></span>
            <?= htmlspecialchars($h['codigo']) ?>
        </span>
    <?php endforeach; ?>
    <span><span class="swatch critica"></span> Ruta crítica</span>
</div>

<?php if (!empty($deps)): ?>
<h3 style="margin-top:14px;">Dependencias (<?= count($deps) ?>)</h3>
<table class="gantt-table">
    <thead><tr>
        <th>Sucesora</th><th>Tipo</th><th>Predecesora</th><th>Lag (días)</th>
    </tr></thead>
    <tbody>
    <?php
        // Construir índice id -> codigo
        $byId = [];
        foreach ($actividades as $a) $byId[(int)$a['id']] = $a['codigo'];
        foreach ($deps as $d):
    ?>
        <tr>
            <td><code><?= htmlspecialchars($byId[(int)$d['actividad_id']] ?? $d['actividad_id']) ?></code></td>
            <td><?= htmlspecialchars($d['tipo']) ?></td>
            <td><code><?= htmlspecialchars($byId[(int)$d['predecesor_id']] ?? $d['predecesor_id']) ?></code></td>
            <td style="text-align:center;"><?= (int)$d['lag_dias'] ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>

<?php endif; ?>

<div class="footer">
    ERP GMC · Constructora · documento generado automáticamente
</div>
