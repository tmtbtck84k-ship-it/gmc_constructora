<div class="d-flex align-items-center justify-content-between mb-3">
  <div>
    <h4 class="mb-0"><i class="bi bi-tag me-2"></i>Centros de Costo</h4>
    <small class="text-muted">
      <?php if ($proyecto): ?>
        Proyecto <code><?= e($proyecto['codigo']) ?></code> · <?= e($proyecto['nombre']) ?>
      <?php else: ?>
        Generales (sin proyecto)
      <?php endif; ?>
      · <?= count($centros) ?> registros
    </small>
  </div>
  <?php if (can('maestros.cc.crear')): ?>
    <a class="btn btn-primary" href="<?= base_url('maestros/centros-costo/crear' . ($proyecto ? '?proyecto_id=' . (int)$proyecto['id'] : '')) ?>">
      <i class="bi bi-plus-lg me-1"></i> Nuevo CC
    </a>
  <?php endif; ?>
</div>

<form method="get" class="card card-body shadow-sm mb-3">
  <div class="row g-2 align-items-end">
    <div class="col-md-6">
      <label class="form-label small text-muted">Filtrar por proyecto</label>
      <select name="proyecto_id" class="form-select select2" onchange="this.form.submit()">
        <option value="">— Generales (sin proyecto) —</option>
        <?php foreach ($proyectos as $pr): ?>
          <option value="<?= (int)$pr['id'] ?>" <?= ($proyecto && (int)$proyecto['id']===(int)$pr['id'])?'selected':'' ?>>
            <?= e($pr['codigo'] . ' · ' . $pr['nombre']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>
</form>

<div class="card shadow-sm">
  <div class="table-responsive">
    <table class="table table-hover mb-0 datatable">
      <thead class="table-light">
        <tr>
          <th>Código</th>
          <th>Nombre</th>
          <th class="text-center">Tipo</th>
          <th class="text-center">Estado</th>
          <th class="text-end">Acciones</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($centros as $cc): ?>
        <?php $bloqueado = ((int)$cc['es_administracion']===1 && $cc['proyecto_id']===null); ?>
        <tr>
          <td><code><?= e($cc['codigo']) ?></code></td>
          <td><?= e($cc['nombre']) ?></td>
          <td class="text-center">
            <?php if ((int)$cc['es_administracion']===1): ?>
              <span class="badge bg-info">Administración</span>
            <?php else: ?>
              <span class="text-muted">Operativo</span>
            <?php endif; ?>
          </td>
          <td class="text-center">
            <?= (int)$cc['activo']===1 ? '<span class="badge bg-success">Activo</span>' : '<span class="badge bg-secondary">Inactivo</span>' ?>
          </td>
          <td class="text-end">
            <?php if ($bloqueado): ?>
              <span class="text-muted small"><i class="bi bi-lock"></i> CC raíz no editable</span>
            <?php else: ?>
              <?php if (can('maestros.cc.editar')): ?>
                <a class="btn btn-sm btn-outline-primary" href="<?= base_url('maestros/centros-costo/editar/' . (int)$cc['id']) ?>"><i class="bi bi-pencil"></i></a>
              <?php endif; ?>
              <?php if (can('maestros.cc.eliminar') && (int)$cc['es_administracion']!==1): ?>
                <a class="btn btn-sm btn-outline-danger js-confirm" data-confirm="¿Eliminar este centro de costo?" href="<?= base_url('maestros/centros-costo/eliminar/' . (int)$cc['id']) ?>"><i class="bi bi-trash"></i></a>
              <?php endif; ?>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
