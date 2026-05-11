<div class="d-flex align-items-center justify-content-between mb-3">
  <div>
    <h4 class="mb-0"><i class="bi bi-graph-up-arrow me-2"></i>Gastos por obra y CC</h4>
    <small class="text-muted">Total CLP global: <strong>$ <?= number_format($total_global, 0, ',', '.') ?></strong></small>
  </div>
  <?php if (can('reportes.gastos.exportar')): ?>
    <a class="btn btn-outline-success" href="<?= base_url('reportes/gastos/exportar?' . http_build_query($filters)) ?>"><i class="bi bi-file-earmark-spreadsheet me-1"></i> Exportar CSV</a>
  <?php endif; ?>
</div>

<form method="get" class="card card-body shadow-sm mb-3">
  <div class="row g-2">
    <div class="col-md-4">
      <label class="form-label small text-muted">Proyecto</label>
      <select name="proyecto_id" class="form-select select2"><option value="">Todas las obras</option>
        <?php foreach ($proyectos as $p): ?><option value="<?= (int)$p['id'] ?>" <?= $filters['proyecto_id']==$p['id']?'selected':'' ?>><?= e($p['codigo'] . ' · ' . $p['nombre']) ?></option><?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-3"><label class="form-label small text-muted">Desde</label><input type="date" name="desde" class="form-control" value="<?= e($filters['desde']) ?>"></div>
    <div class="col-md-3"><label class="form-label small text-muted">Hasta</label><input type="date" name="hasta" class="form-control" value="<?= e($filters['hasta']) ?>"></div>
    <div class="col-md-2 d-flex align-items-end"><button class="btn btn-outline-primary w-100"><i class="bi bi-search"></i> Filtrar</button></div>
  </div>
</form>

<div class="card shadow-sm">
  <div class="table-responsive">
    <table class="table table-hover table-sm mb-0">
      <thead class="table-light">
        <tr>
          <th>Proyecto</th><th>Centro de costo</th><th>Tipo gasto</th><th class="text-center">Origen</th>
          <th class="text-end">Docs</th><th class="text-end">Total CLP</th>
        </tr>
      </thead>
      <tbody>
      <?php
      $lastProy = null; $lastCc = null; $subProy = 0; $subCc = 0;
      $totalProy = []; $totalCc = [];
      foreach ($rows as $r) {
        $key = $r['proyecto_codigo'] ?: '—';
        $kc = $key . '|' . ($r['cc_codigo'] ?: '—');
        $totalProy[$key] = ($totalProy[$key] ?? 0) + (float)$r['total_clp'];
        $totalCc[$kc]    = ($totalCc[$kc] ?? 0) + (float)$r['total_clp'];
      }
      ?>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td class="small"><?= e($r['proyecto_codigo'] ?: '<i class="text-muted">Administración</i>') ?></td>
          <td class="small"><code><?= e($r['cc_codigo']) ?></code> <?= e($r['cc_nombre']) ?></td>
          <td class="small"><?= e($r['tg_codigo'] ?: '—') ?></td>
          <td class="text-center"><span class="badge bg-secondary"><?= e($r['origen']) ?></span></td>
          <td class="text-end small"><?= (int)$r['docs'] ?></td>
          <td class="text-end fw-semibold">$ <?= number_format((float)$r['total_clp'], 0, ',', '.') ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$rows): ?><tr><td colspan="6" class="text-center text-muted py-4">Sin gastos en el período.</td></tr><?php endif; ?>
      </tbody>
      <tfoot>
        <tr class="table-light fw-bold"><td colspan="5" class="text-end">Total general</td><td class="text-end">$ <?= number_format($total_global, 0, ',', '.') ?></td></tr>
      </tfoot>
    </table>
  </div>
</div>
