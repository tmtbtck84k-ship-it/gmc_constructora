<?php
$colorSem = ['verde'=>'success','amarillo'=>'warning','rojo'=>'danger'];
$totalRows = count($rows);
$counts = ['verde'=>0,'amarillo'=>0,'rojo'=>0];
foreach ($rows as $r) $counts[$r['semaforo']]++;

// Promedios para cuadro de resumen
$promPlan = $totalRows ? round(array_sum(array_column($rows, 'plan_al_dia')) / $totalRows, 1) : 0;
$promReal = $totalRows ? round(array_sum(array_column($rows, 'porcentaje_avance')) / $totalRows, 1) : 0;
$desvProm = round($promReal - $promPlan, 1);
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="h4 mb-0">
        <i class="bi bi-table"></i> Reporte de Avance vs Planificado
        <small class="text-muted">— <?= htmlspecialchars($proyecto['codigo']) ?> · <?= htmlspecialchars($proyecto['nombre']) ?></small>
    </h2>
    <div class="d-flex gap-2">
        <a class="btn btn-outline-secondary btn-sm"
           href="<?= site_url('obras/gantt?proyecto_id=' . $proyecto['id']) ?>">
            <i class="bi bi-arrow-left"></i> Volver al Gantt
        </a>
        <button class="btn btn-outline-primary btn-sm" onclick="window.print()">
            <i class="bi bi-printer"></i> Imprimir
        </button>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <div class="text-muted small">Total actividades</div>
                <div class="display-6"><?= $totalRows ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center border-success">
            <div class="card-body">
                <div class="text-success small">A tiempo</div>
                <div class="display-6 text-success"><?= $counts['verde'] ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center border-warning">
            <div class="card-body">
                <div class="text-warning small">Levemente atrasadas</div>
                <div class="display-6 text-warning"><?= $counts['amarillo'] ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center border-danger">
            <div class="card-body">
                <div class="text-danger small">Atrasadas</div>
                <div class="display-6 text-danger"><?= $counts['rojo'] ?></div>
            </div>
        </div>
    </div>
</div>

<div class="alert alert-light border mb-3">
    <strong>Promedio del proyecto al <?= htmlspecialchars($hoy) ?>:</strong>
    Planificado <span class="badge bg-secondary"><?= $promPlan ?>%</span> ·
    Real <span class="badge bg-info"><?= $promReal ?>%</span> ·
    Desviación
    <span class="badge bg-<?= $desvProm >= 0 ? 'success' : ($desvProm < -10 ? 'danger' : 'warning') ?>">
        <?= ($desvProm > 0 ? '+' : '') . $desvProm ?>%
    </span>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-striped table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>Código</th>
                    <th>Actividad</th>
                    <th>Hito</th>
                    <th>Inicio plan.</th>
                    <th>Término plan.</th>
                    <th class="text-center">Plan @ hoy</th>
                    <th class="text-center">Real</th>
                    <th class="text-center">Desv. %</th>
                    <th class="text-center">Desv. días</th>
                    <th class="text-center">Estado</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($rows)): ?>
                <tr><td colspan="10" class="text-center text-muted py-4">El proyecto no tiene actividades registradas.</td></tr>
            <?php else: foreach ($rows as $r): ?>
                <tr>
                    <td><code><?= htmlspecialchars($r['codigo']) ?></code></td>
                    <td>
                        <?= htmlspecialchars($r['nombre']) ?>
                        <?php if ((int)$r['es_critica'] === 1): ?>
                            <span class="badge bg-danger" title="Ruta crítica"><i class="bi bi-fire"></i></span>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($r['hito_codigo'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($r['fecha_inicio_planificada']) ?></td>
                    <td><?= htmlspecialchars($r['fecha_termino_planificada']) ?></td>
                    <td class="text-center"><?= number_format($r['plan_al_dia'], 0) ?>%</td>
                    <td class="text-center"><strong><?= number_format((float)$r['porcentaje_avance'], 0) ?>%</strong></td>
                    <td class="text-center">
                        <span class="badge bg-<?= $r['desviacion_pct'] >= 0 ? 'success' : ($r['desviacion_pct'] < -10 ? 'danger' : 'warning text-dark') ?>">
                            <?= ($r['desviacion_pct'] > 0 ? '+' : '') . $r['desviacion_pct'] ?>%
                        </span>
                    </td>
                    <td class="text-center">
                        <?php if ($r['desviacion_dias'] != 0): ?>
                            <span class="text-<?= $r['desviacion_dias'] > 0 ? 'danger' : 'success' ?>">
                                <?= ($r['desviacion_dias'] > 0 ? '+' : '') . $r['desviacion_dias'] ?>d
                            </span>
                        <?php else: ?>
                            <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-center">
                        <span class="badge bg-<?= $colorSem[$r['semaforo']] ?>">
                            <?= ucfirst($r['semaforo']) ?>
                        </span>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<style>
@media print {
    .btn, header, .sidebar, nav, footer { display: none !important; }
    .card { border: 1px solid #dee2e6 !important; box-shadow: none !important; }
}
</style>
