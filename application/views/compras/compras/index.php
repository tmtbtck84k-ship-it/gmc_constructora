<div class="d-flex align-items-center justify-content-between mb-3">
  <div>
    <h4 class="mb-0"><i class="bi bi-cart me-2"></i>Compras / Recepciones</h4>
    <small class="text-muted"><?= number_format($total, 0, ',', '.') ?> registros</small>
  </div>
  <?php if (can('compras.compra.crear')): ?>
    <a class="btn btn-primary" href="<?= base_url('compras/compras/crear') ?>"><i class="bi bi-plus-lg me-1"></i> Nueva compra</a>
  <?php endif; ?>
</div>

<form method="get" class="card card-body shadow-sm mb-3">
  <div class="row g-2">
    <div class="col-md-3"><input type="text" name="q" class="form-control" placeholder="Nº, doc, proveedor" value="<?= e($filters['q']) ?>"></div>
    <div class="col-md-2">
      <select name="proyecto_id" class="form-select select2"><option value="">Proyecto</option>
        <?php foreach ($proyectos as $p): ?><option value="<?= (int)$p['id'] ?>" <?= $filters['proyecto_id']==$p['id']?'selected':'' ?>><?= e($p['codigo']) ?></option><?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-2">
      <select name="estado_id" class="form-select"><option value="">Estado</option>
        <?php foreach ($estados as $st): ?><option value="<?= (int)$st['id'] ?>" <?= $filters['estado_id']==$st['id']?'selected':'' ?>><?= e($st['nombre']) ?></option><?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-2"><input type="date" name="desde" class="form-control" placeholder="Desde" value="<?= e($filters['desde']) ?>"></div>
    <div class="col-md-2"><input type="date" name="hasta" class="form-control" placeholder="Hasta" value="<?= e($filters['hasta']) ?>"></div>
    <div class="col-md-1 d-flex"><button class="btn btn-outline-primary w-100"><i class="bi bi-search"></i></button></div>
  </div>
</form>

<div class="card shadow-sm">
  <div class="table-responsive">
    <table class="table table-hover table-sm mb-0">
      <thead class="table-light">
        <tr>
          <th>Nº</th><th>Fecha</th><th>Proyecto</th><th>Proveedor</th><th>Doc</th>
          <th class="text-end">Monto</th><th class="text-end">CLP</th>
          <th class="text-center">Estado</th><th class="text-center">Vinc.</th><th class="text-end">Acc.</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><a href="<?= base_url('compras/compras/' . (int)$r['id']) ?>" class="fw-semibold"><?= e($r['numero']) ?></a></td>
          <td class="small"><?= e(format_date($r['fecha_recepcion'])) ?></td>
          <td class="small"><?= e($r['proyecto_codigo'] ?: '—') ?></td>
          <td class="small"><?= e($r['proveedor']) ?></td>
          <td class="small"><?= e($r['documento_tipo']) ?> <?= e($r['documento_numero']) ?></td>
          <td class="text-end small"><?= e($r['moneda_simbolo']) ?> <?= number_format((float)$r['monto_total'], (int)$r['moneda_decimales'], ',', '.') ?></td>
          <td class="text-end small"><?= $r['monto_total_clp'] ? '$ ' . number_format((float)$r['monto_total_clp'], 0, ',', '.') : '—' ?></td>
          <td class="text-center"><span class="badge bg-<?= e($r['estado_color']) ?>"><?= e($r['estado_nombre']) ?></span></td>
          <td class="text-center small">
            <?php if ($r['sdp_numero']): ?><span class="badge bg-info" title="SDP"><i class="bi bi-cash-coin"></i> <?= e($r['sdp_numero']) ?></span><?php endif; ?>
            <?php if ($r['rinde_numero']): ?><span class="badge bg-warning text-dark" title="Rinde"><i class="bi bi-receipt"></i> <?= e($r['rinde_numero']) ?></span><?php endif; ?>
          </td>
          <td class="text-end"><a class="btn btn-sm btn-outline-secondary" href="<?= base_url('compras/compras/' . (int)$r['id']) ?>"><i class="bi bi-eye"></i></a></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$rows): ?><tr><td colspan="10" class="text-center text-muted py-4">Sin resultados.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
  <?php if ($totalPages > 1): ?>
    <nav class="card-footer">
      <ul class="pagination pagination-sm mb-0 justify-content-center">
        <?php for ($p = max(1, $page-4); $p <= min($totalPages, $page+4); $p++): ?>
          <li class="page-item <?= $p===$page?'active':'' ?>"><a class="page-link" href="?<?= http_build_query(array_merge($filters, ['page'=>$p])) ?>"><?= $p ?></a></li>
        <?php endfor; ?>
      </ul>
    </nav>
  <?php endif; ?>
</div>
