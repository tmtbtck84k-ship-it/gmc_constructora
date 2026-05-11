<div class="d-flex align-items-center justify-content-between mb-3">
  <div>
    <h4 class="mb-0"><i class="bi bi-check2-square me-2"></i>Cierres de obra</h4>
    <small class="text-muted"><?= count($rows) ?> proyectos con cierre iniciado</small>
  </div>
</div>

<div class="card shadow-sm">
  <div class="table-responsive">
    <table class="table table-hover mb-0">
      <thead class="table-light">
        <tr><th>Proyecto</th><th>Nombre</th><th>Fecha término real</th><th class="text-center">Estado</th><th class="text-end">Acc.</th></tr>
      </thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><code><?= e($r['proyecto_codigo']) ?></code></td>
          <td><?= e($r['proyecto_nombre']) ?></td>
          <td class="small"><?= e(format_date($r['fecha_termino_real'])) ?></td>
          <td class="text-center"><span class="badge bg-<?= e($r['estado_color']) ?>"><?= e($r['estado_nombre']) ?></span></td>
          <td class="text-end">
            <a class="btn btn-sm btn-outline-secondary" href="<?= base_url('obras/cierre/' . (int)$r['proyecto_id']) ?>"><i class="bi bi-eye"></i></a>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$rows): ?><tr><td colspan="5" class="text-center text-muted py-4">Aún no hay cierres iniciados.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
