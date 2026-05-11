<div class="d-flex align-items-center justify-content-between mb-3">
  <div>
    <h4 class="mb-0"><i class="bi bi-truck me-2"></i>Proveedores</h4>
    <small class="text-muted"><?= count($proveedores) ?> registros</small>
  </div>
  <?php if (can('maestros.proveedor.crear')): ?>
    <a href="<?= base_url('maestros/proveedores/crear') ?>" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i> Nuevo proveedor</a>
  <?php endif; ?>
</div>

<form method="get" class="card card-body shadow-sm mb-3">
  <div class="row g-2">
    <div class="col-md-5">
      <input type="text" name="q" class="form-control" placeholder="Buscar por RUT, razón social o email" value="<?= e($filters['q']) ?>">
    </div>
    <div class="col-md-2">
      <select name="activo" class="form-select">
        <option value="">Todos</option>
        <option value="1" <?= $filters['activo']==='1'?'selected':'' ?>>Activos</option>
        <option value="0" <?= $filters['activo']==='0'?'selected':'' ?>>Inactivos</option>
      </select>
    </div>
    <div class="col-md-2">
      <select name="subc" class="form-select">
        <option value="">Todos</option>
        <option value="1" <?= $filters['subc']==='1'?'selected':'' ?>>Subcontratistas</option>
        <option value="0" <?= $filters['subc']==='0'?'selected':'' ?>>No subcontratistas</option>
      </select>
    </div>
    <div class="col-md-3 d-flex gap-2">
      <button class="btn btn-outline-primary flex-grow-1"><i class="bi bi-search"></i> Filtrar</button>
      <a href="<?= base_url('maestros/proveedores') ?>" class="btn btn-outline-secondary"><i class="bi bi-x"></i></a>
    </div>
  </div>
</form>

<div class="card shadow-sm">
  <div class="table-responsive">
    <table class="table table-hover mb-0 datatable">
      <thead class="table-light">
        <tr>
          <th>RUT</th><th>Razón social</th><th>Comuna</th><th>Categoría</th>
          <th class="text-center">Subc.</th><th class="text-center">Estado</th>
          <th class="text-end">Acciones</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($proveedores as $p): ?>
        <tr>
          <td><?= e(formatear_rut($p['rut'])) ?></td>
          <td>
            <strong><?= e($p['razon_social']) ?></strong>
            <?php if (!empty($p['nombre_fantasia'])): ?><div class="small text-muted"><?= e($p['nombre_fantasia']) ?></div><?php endif; ?>
          </td>
          <td><?= e($p['comuna_nombre'] ?? '—') ?></td>
          <td><?= e($p['categoria'] ?? '—') ?></td>
          <td class="text-center">
            <?= (int)$p['es_subcontratista']===1 ? '<span class="badge bg-info">Subc.</span>' : '<span class="text-muted">—</span>' ?>
          </td>
          <td class="text-center">
            <?= (int)$p['activo']===1 ? '<span class="badge bg-success">Activo</span>' : '<span class="badge bg-secondary">Inactivo</span>' ?>
          </td>
          <td class="text-end">
            <?php if (can('maestros.proveedor.editar')): ?>
              <a class="btn btn-sm btn-outline-primary" href="<?= base_url('maestros/proveedores/editar/' . (int)$p['id']) ?>"><i class="bi bi-pencil"></i></a>
            <?php endif; ?>
            <?php if (can('maestros.proveedor.eliminar')): ?>
              <a class="btn btn-sm btn-outline-danger js-confirm" data-confirm="¿Eliminar este proveedor?" href="<?= base_url('maestros/proveedores/eliminar/' . (int)$p['id']) ?>"><i class="bi bi-trash"></i></a>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
