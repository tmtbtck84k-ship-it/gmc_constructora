<div class="d-flex align-items-center justify-content-between mb-3">
  <div>
    <h4 class="mb-0"><i class="bi bi-receipt me-2"></i>Rindes de Gastos</h4>
    <small class="text-muted"><?= number_format($total, 0, ',', '.') ?> registros</small>
  </div>
  <?php if (can('compras.rinde.crear')): ?>
    <a class="btn btn-primary" href="<?= base_url('compras/rindes/crear') ?>"><i class="bi bi-plus-lg me-1"></i> Nuevo rinde</a>
  <?php endif; ?>
</div>

<form method="get" class="card card-body shadow-sm mb-3">
  <div class="row g-2">
    <div class="col-md-4"><input type="text" name="q" class="form-control" placeholder="Nº, usuario" value="<?= e($filters['q']) ?>"></div>
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
  </div>
  <div class="mt-2"><button class="btn btn-outline-primary"><i class="bi bi-search"></i> Filtrar</button></div>
</form>

<div class="card shadow-sm">
  <div class="table-responsive">
    <table class="table table-hover table-sm mb-0">
      <thead class="table-light">
        <tr>
          <th>Nº</th><th>Fecha</th><th>Usuario</th><th>Proyecto</th><th>CC</th>
          <th class="text-end">Monto</th><th class="text-end">CLP</th>
          <th class="text-center">Estado</th><th class="text-end">Acc.</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><a href="<?= base_url('compras/rindes/' . (int)$r['id']) ?>" class="fw-semibold"><?= e($r['numero']) ?></a></td>
          <td class="small"><?= e(format_date($r['fecha_rendicion'])) ?></td>
          <td class="small"><?= e(trim(($r['usuario_nombres']?:'') . ' ' . ($r['usuario_apellidos']?:''))) ?></td>
          <td class="small"><?= e($r['proyecto_codigo'] ?: '—') ?></td>
          <td class="small"><?= e($r['cc_codigo']) ?></td>
          <td class="text-end small"><?= e($r['moneda_simbolo']) ?> <?= number_format((float)$r['monto_total'], (int)$r['moneda_decimales'], ',', '.') ?></td>
          <td class="text-end small"><?= $r['monto_total_clp'] ? '$ ' . number_format((float)$r['monto_total_clp'], 0, ',', '.') : '—' ?></td>
          <td class="text-center"><span class="badge bg-<?= e($r['estado_color']) ?>"><?= e($r['estado_nombre']) ?></span></td>
          <td class="text-end"><a class="btn btn-sm btn-outline-secondary" href="<?= base_url('compras/rindes/' . (int)$r['id']) ?>"><i class="bi bi-eye"></i></a></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$rows): ?><tr><td colspan="9" class="text-center text-muted py-4">Sin resultados.</td></tr><?php endif; ?>
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
