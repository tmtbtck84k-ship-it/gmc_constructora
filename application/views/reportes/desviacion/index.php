<div class="d-flex align-items-center justify-content-between mb-3">
  <div>
    <h4 class="mb-0"><i class="bi bi-bar-chart me-2"></i>Desviación presupuestaria</h4>
    <?php if ($proyecto): ?><small class="text-muted"><?= e($proyecto['codigo']) ?> · <?= e($proyecto['nombre']) ?></small><?php endif; ?>
  </div>
  <?php if ($proyecto && can('reportes.desviacion.exportar')): ?>
    <a class="btn btn-outline-success" href="<?= base_url('reportes/desviacion/exportar?proyecto_id=' . (int)$proyecto['id']) ?>"><i class="bi bi-file-earmark-spreadsheet me-1"></i> Exportar CSV</a>
  <?php endif; ?>
</div>

<form method="get" class="card card-body shadow-sm mb-3">
  <div class="row g-2">
    <div class="col-md-6">
      <label class="form-label small text-muted">Proyecto *</label>
      <select name="proyecto_id" class="form-select select2" onchange="this.form.submit()" required>
        <option value="">— Seleccionar —</option>
        <?php foreach ($proyectos as $p): ?>
          <option value="<?= (int)$p['id'] ?>" <?= ($proyecto && (int)$proyecto['id']===(int)$p['id'])?'selected':'' ?>>
            <?= e($p['codigo'] . ' · ' . $p['nombre']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>
</form>

<?php if (!$proyecto): ?>
  <div class="alert alert-info">Selecciona un proyecto para ver el análisis de desviación.</div>
<?php elseif (!$data['presupuesto']): ?>
  <div class="alert alert-warning">
    <i class="bi bi-exclamation-triangle me-1"></i>
    Este proyecto aún no tiene un presupuesto inicial vigente.
    <a href="<?= base_url('obras/presupuesto?proyecto_id=' . (int)$proyecto['id']) ?>">Cargar presupuesto →</a>
  </div>
<?php else: ?>
  <!-- Tarjetas resumen -->
  <div class="row g-3 mb-3">
    <div class="col-md-3">
      <div class="card border-0 shadow-sm">
        <div class="card-body">
          <div class="text-muted small text-uppercase">Presupuestado</div>
          <div class="fs-4 fw-bold">$ <?= number_format($data['totales']['presupuestado'], 0, ',', '.') ?></div>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card border-0 shadow-sm">
        <div class="card-body">
          <div class="text-muted small text-uppercase">Gasto real (CLP)</div>
          <div class="fs-4 fw-bold">$ <?= number_format($data['totales']['real'], 0, ',', '.') ?></div>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <?php $color = $data['totales']['desv'] > 0 ? 'danger' : ($data['totales']['desv'] < 0 ? 'success' : 'secondary'); ?>
      <div class="card border-0 shadow-sm">
        <div class="card-body">
          <div class="text-muted small text-uppercase">Desviación</div>
          <div class="fs-4 fw-bold text-<?= $color ?>">
            <?= ($data['totales']['desv'] >= 0 ? '+' : '') . '$ ' . number_format(abs($data['totales']['desv']), 0, ',', '.') ?>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card border-0 shadow-sm">
        <div class="card-body">
          <div class="text-muted small text-uppercase">Desviación %</div>
          <div class="fs-4 fw-bold text-<?= $color ?>">
            <?= ($data['totales']['desv_pct'] >= 0 ? '+' : '') . $data['totales']['desv_pct'] ?>%
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="card shadow-sm">
    <div class="card-header bg-white fw-semibold">Desglose por línea presupuestaria</div>
    <div class="table-responsive">
      <table class="table table-hover table-sm mb-0">
        <thead class="table-light">
          <tr>
            <th>CC</th><th>Tipo gasto</th><th>Descripción</th>
            <th class="text-end">Presupuestado</th>
            <th class="text-end">Real CLP</th>
            <th class="text-end">Desviación</th>
            <th class="text-end">%</th>
            <th class="text-center">Estado</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($data['lineas'] as $l): ?>
            <tr>
              <td class="small"><code><?= e($l['cc_codigo']) ?></code></td>
              <td class="small"><code><?= e($l['tg_codigo']) ?></code></td>
              <td class="small"><?= $l['descripcion'] ?></td>
              <td class="text-end small">$ <?= number_format((float)$l['presupuestado'], 0, ',', '.') ?></td>
              <td class="text-end small">$ <?= number_format((float)$l['real_clp'], 0, ',', '.') ?></td>
              <td class="text-end small text-<?= $l['semaforo'] ?>"><?= ($l['desv'] >= 0 ? '+' : '') . '$ ' . number_format(abs($l['desv']), 0, ',', '.') ?></td>
              <td class="text-end small text-<?= $l['semaforo'] ?>"><?= ($l['desv_pct'] >= 0 ? '+' : '') . $l['desv_pct'] ?>%</td>
              <td class="text-center">
                <?php if ($l['semaforo'] === 'success'): ?><i class="bi bi-circle-fill text-success" title="Dentro del presupuesto"></i>
                <?php elseif ($l['semaforo'] === 'warning'): ?><i class="bi bi-circle-fill text-warning" title="Atención: 0-5% sobrepaso"></i>
                <?php else: ?><i class="bi bi-circle-fill text-danger" title="Sobre 5% de desviación"></i>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
<?php endif; ?>
