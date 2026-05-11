<div class="d-flex align-items-center justify-content-between mb-3">
  <div>
    <h4 class="mb-0"><i class="bi bi-calculator me-2"></i>Presupuesto inicial por obra</h4>
    <?php if ($proyecto): ?><small class="text-muted"><?= e($proyecto['codigo']) ?> · <?= e($proyecto['nombre']) ?></small><?php endif; ?>
  </div>
  <?php if ($proyecto && can('obras.presupuesto.editar')): ?>
    <div class="d-flex gap-2">
      <?php if ($versiones): ?>
        <a class="btn btn-outline-primary" href="<?= base_url("obras/presupuesto/{$proyecto['id']}/nueva-version") ?>"><i class="bi bi-plus-circle me-1"></i> Nueva versión</a>
      <?php else: ?>
        <a class="btn btn-primary" href="<?= base_url('obras/presupuesto/crear?proyecto_id=' . (int)$proyecto['id']) ?>"><i class="bi bi-plus-lg me-1"></i> Crear presupuesto</a>
      <?php endif; ?>
    </div>
  <?php endif; ?>
</div>

<form method="get" class="card card-body shadow-sm mb-3">
  <div class="row g-2">
    <div class="col-md-6">
      <label class="form-label small text-muted">Proyecto</label>
      <select name="proyecto_id" class="form-select select2" onchange="this.form.submit()">
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

<?php if ($proyecto): ?>
  <?php if (!$versiones): ?>
    <div class="alert alert-info">Este proyecto aún no tiene un presupuesto inicial cargado.</div>
  <?php else: ?>
    <div class="card shadow-sm">
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead class="table-light">
            <tr><th>Versión</th><th>Moneda</th><th class="text-end">Monto total</th><th class="text-center">Vigente</th><th>Creado</th><th class="text-end">Acc.</th></tr>
          </thead>
          <tbody>
            <?php foreach ($versiones as $v): ?>
              <tr>
                <td><strong>v<?= (int)$v['version'] ?></strong></td>
                <td><?= e($v['moneda_codigo']) ?></td>
                <td class="text-end"><?= e($v['moneda_simbolo']) ?> <?= number_format((float)$v['monto_total'], (int)$v['moneda_decimales'], ',', '.') ?></td>
                <td class="text-center">
                  <?= (int)$v['vigente']===1 ? '<span class="badge bg-success">Vigente</span>' : '<span class="badge bg-secondary">Anterior</span>' ?>
                </td>
                <td class="small"><?= e(format_datetime($v['created_at'])) ?></td>
                <td class="text-end">
                  <a class="btn btn-sm btn-outline-secondary" href="<?= base_url('obras/presupuesto/' . (int)$v['id']) ?>"><i class="bi bi-eye"></i></a>
                  <?php if ((int)$v['vigente']===1 && can('obras.presupuesto.editar')): ?>
                    <a class="btn btn-sm btn-outline-primary" href="<?= base_url('obras/presupuesto/' . (int)$v['id'] . '/editar') ?>"><i class="bi bi-pencil"></i></a>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  <?php endif; ?>
<?php else: ?>
  <div class="alert alert-info">Selecciona un proyecto para ver/cargar su presupuesto.</div>
<?php endif; ?>
