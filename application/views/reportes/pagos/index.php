<div class="d-flex align-items-center justify-content-between mb-3">
  <div>
    <h4 class="mb-0"><i class="bi bi-graph-up me-2"></i>Estado de Pagos</h4>
    <small class="text-muted"><?= number_format(count($rows), 0, ',', '.') ?> SDPs</small>
  </div>
  <?php if (can('reportes.pagos.exportar')): ?>
    <a class="btn btn-outline-success" href="<?= base_url('reportes/pagos/exportar?' . http_build_query($filters)) ?>"><i class="bi bi-file-earmark-spreadsheet me-1"></i> Exportar CSV</a>
  <?php endif; ?>
</div>

<form method="get" class="card card-body shadow-sm mb-3">
  <div class="row g-2">
    <div class="col-md-3">
      <label class="form-label small text-muted">Proyecto</label>
      <select name="proyecto_id" class="form-select select2"><option value="">Todos</option>
        <?php foreach ($proyectos as $p): ?><option value="<?= (int)$p['id'] ?>" <?= $filters['proyecto_id']==$p['id']?'selected':'' ?>><?= e($p['codigo']) ?></option><?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-3">
      <label class="form-label small text-muted">Proveedor</label>
      <select name="proveedor_id" class="form-select select2"><option value="">Todos</option>
        <?php foreach ($proveedores as $pr): ?><option value="<?= (int)$pr['id'] ?>" <?= $filters['proveedor_id']==$pr['id']?'selected':'' ?>><?= e($pr['razon_social']) ?></option><?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-2">
      <label class="form-label small text-muted">Estado</label>
      <select name="estado_id" class="form-select"><option value="">Todos</option>
        <?php foreach ($estados as $e): ?><option value="<?= (int)$e['id'] ?>" <?= $filters['estado_id']==$e['id']?'selected':'' ?>><?= e($e['nombre']) ?></option><?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-2"><label class="form-label small text-muted">Desde</label><input type="date" name="desde" class="form-control" value="<?= e($filters['desde']) ?>"></div>
    <div class="col-md-2"><label class="form-label small text-muted">Hasta</label><input type="date" name="hasta" class="form-control" value="<?= e($filters['hasta']) ?>"></div>
  </div>
  <div class="mt-2"><button class="btn btn-outline-primary"><i class="bi bi-search"></i> Filtrar</button></div>
</form>

<!-- Resumen por estado -->
<?php if ($totales): ?>
  <div class="row g-3 mb-3">
    <?php foreach ($totales as $t): ?>
      <div class="col-md-3 col-sm-6">
        <div class="card border-0 shadow-sm">
          <div class="card-body p-3">
            <div class="d-flex align-items-center justify-content-between">
              <div>
                <span class="badge bg-<?= e($t['color']) ?>"><?= e($t['nombre']) ?></span>
                <div class="fs-5 fw-bold mt-1"><?= number_format($t['count'], 0, ',', '.') ?></div>
              </div>
              <div class="text-end small text-muted">
                CLP<br>
                <strong>$ <?= number_format($t['monto_clp'], 0, ',', '.') ?></strong>
              </div>
            </div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<div class="card shadow-sm">
  <div class="table-responsive">
    <table class="table table-hover table-sm mb-0">
      <thead class="table-light">
        <tr>
          <th>Nº</th><th>F. emisión</th><th>F. pago</th><th>Proyecto</th><th>Proveedor</th>
          <th class="text-end">Monto</th><th class="text-end">CLP</th>
          <th class="text-center">Estado</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><a href="<?= base_url('finanzas/sdp/' . (int)$r['id']) ?>" class="fw-semibold"><?= e($r['numero']) ?></a></td>
          <td class="small"><?= e(format_date($r['fecha_emision'])) ?></td>
          <td class="small"><?= e(format_date($r['fecha_pago'])) ?: '—' ?></td>
          <td class="small"><?= e($r['proyecto_codigo'] ?: '—') ?></td>
          <td class="small"><?= e($r['proveedor']) ?></td>
          <td class="text-end small"><?= e($r['moneda_simbolo']) ?> <?= number_format((float)$r['monto_total'], (int)$r['moneda_decimales'], ',', '.') ?></td>
          <td class="text-end small">$ <?= number_format((float)($r['monto_total_clp'] ?? 0), 0, ',', '.') ?></td>
          <td class="text-center"><span class="badge bg-<?= e($r['estado_color']) ?>"><?= e($r['estado_nombre']) ?></span></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$rows): ?><tr><td colspan="8" class="text-center text-muted py-4">Sin resultados.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
