<div class="d-flex align-items-center justify-content-between mb-3">
  <div>
    <h4 class="mb-0"><i class="bi bi-list-ul me-2"></i>Tipos de Gasto</h4>
    <small class="text-muted"><?= count($tipos) ?> registros</small>
  </div>
  <?php if (can('maestros.tipo_gasto.editar')): ?>
    <a href="<?= base_url('maestros/tipos-gasto/crear') ?>" class="btn btn-primary">
      <i class="bi bi-plus-lg me-1"></i> Nuevo
    </a>
  <?php endif; ?>
</div>

<div class="card shadow-sm">
  <div class="table-responsive">
    <table class="table table-hover mb-0 datatable">
      <thead class="table-light">
        <tr>
          <th>Código</th>
          <th>Nombre</th>
          <th class="text-center">Estado</th>
          <th class="text-end">Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($tipos as $t): ?>
          <tr>
            <td><code><?= e($t['codigo']) ?></code></td>
            <td><?= e($t['nombre']) ?></td>
            <td class="text-center">
              <?= (int)$t['activo']===1
                ? '<span class="badge bg-success">Activo</span>'
                : '<span class="badge bg-secondary">Inactivo</span>' ?>
            </td>
            <td class="text-end">
              <?php if (can('maestros.tipo_gasto.editar')): ?>
                <a class="btn btn-sm btn-outline-primary" href="<?= base_url('maestros/tipos-gasto/editar/' . (int)$t['id']) ?>"><i class="bi bi-pencil"></i></a>
                <a class="btn btn-sm btn-outline-danger js-confirm" data-confirm="¿Eliminar este tipo de gasto?" href="<?= base_url('maestros/tipos-gasto/eliminar/' . (int)$t['id']) ?>"><i class="bi bi-trash"></i></a>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
