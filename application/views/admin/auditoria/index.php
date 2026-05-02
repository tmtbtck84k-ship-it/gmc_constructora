<div class="d-flex align-items-center justify-content-between mb-3">
  <div>
    <h4 class="mb-0"><i class="bi bi-clipboard-data me-2"></i>Auditoría</h4>
    <small class="text-muted"><?= number_format($total, 0, ',', '.') ?> registros</small>
  </div>
  <a class="btn btn-outline-success" href="<?= base_url('admin/auditoria/exportar?' . http_build_query($filters)) ?>">
    <i class="bi bi-file-earmark-spreadsheet me-1"></i> Exportar CSV
  </a>
</div>

<form method="get" class="card card-body shadow-sm mb-3">
  <div class="row g-2">
    <div class="col-md-3">
      <label class="form-label small text-muted">Acción contiene</label>
      <input type="text" name="accion" class="form-control" placeholder="sdp.validar, login..." value="<?= e($filters['accion']) ?>">
    </div>
    <div class="col-md-2">
      <label class="form-label small text-muted">Entidad</label>
      <input type="text" name="entidad" class="form-control" placeholder="gmc_solicitudes_pago" value="<?= e($filters['entidad']) ?>">
    </div>
    <div class="col-md-2">
      <label class="form-label small text-muted">Usuario ID</label>
      <input type="number" name="usuario_id" class="form-control" value="<?= e($filters['usuario_id']) ?>">
    </div>
    <div class="col-md-2">
      <label class="form-label small text-muted">Desde</label>
      <input type="date" name="desde" class="form-control" value="<?= e($filters['desde']) ?>">
    </div>
    <div class="col-md-2">
      <label class="form-label small text-muted">Hasta</label>
      <input type="date" name="hasta" class="form-control" value="<?= e($filters['hasta']) ?>">
    </div>
    <div class="col-md-1 d-flex align-items-end">
      <button class="btn btn-primary w-100"><i class="bi bi-search"></i></button>
    </div>
  </div>
</form>

<div class="card shadow-sm">
  <div class="table-responsive">
    <table class="table table-hover table-sm mb-0">
      <thead class="table-light">
        <tr>
          <th>Fecha</th>
          <th>Usuario</th>
          <th>Acción</th>
          <th>Entidad</th>
          <th>ID</th>
          <th>IP</th>
          <th>Detalle</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td class="text-nowrap small"><?= e(format_datetime($r['created_at'])) ?></td>
          <td class="small">
            <?= e(trim(($r['nombres'] ?? '') . ' ' . ($r['apellidos'] ?? '')) ?: '—') ?>
            <div class="text-muted small"><?= e($r['usuario_email'] ?? '') ?></div>
          </td>
          <td><code class="small"><?= e($r['accion']) ?></code></td>
          <td class="small text-muted"><?= e($r['entidad'] ?? '—') ?></td>
          <td class="small"><?= e($r['entidad_id'] ?? '') ?></td>
          <td class="small text-muted"><?= e($r['ip'] ?? '') ?></td>
          <td>
            <?php if ($r['estado_anterior'] || $r['estado_nuevo']): ?>
              <button type="button" class="btn btn-sm btn-link p-0" data-bs-toggle="collapse" data-bs-target="#det-<?= (int)$r['id'] ?>">Ver</button>
              <div class="collapse mt-2" id="det-<?= (int)$r['id'] ?>">
                <pre class="bg-light p-2 small mb-0" style="max-height:200px;overflow:auto"><?php
                  if ($r['estado_anterior']) echo "Antes:\n"  . e(json_encode(json_decode($r['estado_anterior']), JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE)) . "\n\n";
                  if ($r['estado_nuevo'])    echo "Después:\n" . e(json_encode(json_decode($r['estado_nuevo']),    JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
                ?></pre>
              </div>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
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
