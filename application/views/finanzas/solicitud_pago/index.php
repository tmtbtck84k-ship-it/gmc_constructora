<div class="d-flex align-items-center justify-content-between mb-3">
  <div>
    <h4 class="mb-0"><i class="bi bi-cash-coin me-2"></i>Solicitudes de Pago</h4>
    <small class="text-muted"><?= number_format($total, 0, ',', '.') ?> registros</small>
  </div>
  <div class="d-flex gap-2">
    <?php if (can('finanzas.sdp.exportar')): ?>
      <div class="dropdown">
        <button class="btn btn-outline-success dropdown-toggle" data-bs-toggle="dropdown"><i class="bi bi-file-earmark-spreadsheet me-1"></i> Exportar</button>
        <ul class="dropdown-menu dropdown-menu-end">
          <li><a class="dropdown-item" href="<?= base_url('finanzas/sdp/exportar?formato=csv&' . http_build_query($filters)) ?>"><i class="bi bi-filetype-csv me-1"></i> CSV (UTF-8)</a></li>
          <li><a class="dropdown-item" href="<?= base_url('finanzas/sdp/exportar?formato=xlsx&' . http_build_query($filters)) ?>"><i class="bi bi-filetype-xlsx me-1"></i> Excel (XLSX)</a></li>
        </ul>
      </div>
    <?php endif; ?>
    <?php if (can('finanzas.sdp.crear')): ?>
      <a class="btn btn-primary" href="<?= base_url('finanzas/sdp/crear') ?>"><i class="bi bi-plus-lg me-1"></i> Nueva SDP</a>
    <?php endif; ?>
  </div>
</div>

<form method="get" class="card card-body shadow-sm mb-3">
  <div class="row g-2">
    <div class="col-md-3">
      <label class="form-label small text-muted">Buscar</label>
      <input type="text" name="q" class="form-control" placeholder="Nº SDP, doc, proveedor" value="<?= e($filters['q']) ?>">
    </div>
    <div class="col-md-2">
      <label class="form-label small text-muted">Proyecto</label>
      <select name="proyecto_id" class="form-select select2">
        <option value="">Todos</option>
        <?php foreach ($proyectos as $p): ?>
          <option value="<?= (int)$p['id'] ?>" <?= ($filters['proyecto_id']==$p['id'])?'selected':'' ?>><?= e($p['codigo'].' · '.$p['nombre']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-2">
      <label class="form-label small text-muted">Proveedor</label>
      <select name="proveedor_id" class="form-select select2">
        <option value="">Todos</option>
        <?php foreach ($proveedores as $pr): ?>
          <option value="<?= (int)$pr['id'] ?>" <?= ($filters['proveedor_id']==$pr['id'])?'selected':'' ?>><?= e($pr['razon_social']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-1">
      <label class="form-label small text-muted">Estado</label>
      <select name="estado_id" class="form-select">
        <option value="">Todos</option>
        <?php foreach ($estados as $est): ?>
          <option value="<?= (int)$est['id'] ?>" <?= ($filters['estado_id']==$est['id'])?'selected':'' ?>><?= e($est['nombre']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-2">
      <label class="form-label small text-muted">Desde</label>
      <input type="date" name="desde" class="form-control" value="<?= e($filters['desde']) ?>">
    </div>
    <div class="col-md-2">
      <label class="form-label small text-muted">Hasta</label>
      <input type="date" name="hasta" class="form-control" value="<?= e($filters['hasta']) ?>">
    </div>
    <div class="col-12 d-flex gap-2">
      <button class="btn btn-outline-primary"><i class="bi bi-search"></i> Filtrar</button>
      <a class="btn btn-outline-secondary" href="<?= base_url('finanzas/sdp') ?>"><i class="bi bi-x"></i> Limpiar</a>
    </div>
  </div>
</form>

<div class="card shadow-sm">
  <div class="table-responsive">
    <table class="table table-hover table-sm mb-0">
      <thead class="table-light">
        <tr>
          <th>Nº</th>
          <th>Fecha emisión</th>
          <th>Proyecto</th>
          <th>Proveedor</th>
          <th>CC / Tipo</th>
          <th class="text-end">Monto</th>
          <th class="text-end">CLP</th>
          <th class="text-center">Estado</th>
          <th class="text-end">Acciones</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><a href="<?= base_url('finanzas/sdp/' . (int)$r['id']) ?>" class="fw-semibold text-decoration-none"><?= e($r['numero']) ?></a></td>
          <td class="small"><?= e(format_date($r['fecha_emision'])) ?></td>
          <td class="small">
            <?= e($r['proyecto_codigo'] ?: '—') ?>
            <?php if ($r['proyecto_codigo']): ?><div class="text-muted small"><?= e($r['proyecto_nombre']) ?></div><?php endif; ?>
          </td>
          <td class="small"><?= e($r['proveedor']) ?></td>
          <td class="small">
            <?= e($r['cc_codigo']) ?>
            <div class="text-muted small"><?= e($r['tg_codigo']) ?></div>
          </td>
          <td class="text-end small">
            <?= e($r['moneda_simbolo'] ?: $r['moneda']) ?>
            <?= number_format((float)$r['monto_total'], (int)$r['moneda_decimales'], ',', '.') ?>
            <?php if ($r['moneda'] !== 'CLP'): ?>
              <div class="text-muted small"><?= e($r['moneda']) ?></div>
            <?php endif; ?>
          </td>
          <td class="text-end small">
            <?= $r['monto_total_clp'] ? '$ ' . number_format((float)$r['monto_total_clp'], 0, ',', '.') : '—' ?>
          </td>
          <td class="text-center"><span class="badge bg-<?= e($r['estado_color'] ?: 'secondary') ?>"><?= e($r['estado_nombre']) ?></span></td>
          <td class="text-end">
            <a class="btn btn-sm btn-outline-secondary" href="<?= base_url('finanzas/sdp/' . (int)$r['id']) ?>" title="Ver"><i class="bi bi-eye"></i></a>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$rows): ?>
        <tr><td colspan="9" class="text-center text-muted py-4">Sin resultados con esos filtros.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>

  <?php if ($totalPages > 1): ?>
    <nav class="card-footer">
      <ul class="pagination pagination-sm mb-0 justify-content-center">
        <?php for ($p = max(1, $page - 4); $p <= min($totalPages, $page + 4); $p++): ?>
          <li class="page-item <?= $p === $page ? 'active' : '' ?>">
            <a class="page-link" href="?<?= http_build_query(array_merge($filters, ['page' => $p])) ?>"><?= $p ?></a>
          </li>
        <?php endfor; ?>
      </ul>
    </nav>
  <?php endif; ?>
</div>
