<div class="d-flex align-items-center justify-content-between mb-3">
  <div>
    <h4 class="mb-0"><i class="bi bi-people me-2"></i>Usuarios</h4>
    <small class="text-muted"><?= count($usuarios) ?> registros</small>
  </div>
  <?php if (can('admin.usuario.crear')): ?>
    <a href="<?= base_url('admin/usuarios/crear') ?>" class="btn btn-primary">
      <i class="bi bi-person-plus me-1"></i> Nuevo usuario
    </a>
  <?php endif; ?>
</div>

<form method="get" class="card card-body shadow-sm mb-3">
  <div class="row g-2">
    <div class="col-md-6">
      <input type="text" name="q" class="form-control" placeholder="Buscar por RUT, nombre, apellido o email"
             value="<?= e($filters['q']) ?>">
    </div>
    <div class="col-md-3">
      <select name="activo" class="form-select">
        <option value="">Todos</option>
        <option value="1" <?= $filters['activo']==='1'?'selected':'' ?>>Activos</option>
        <option value="0" <?= $filters['activo']==='0'?'selected':'' ?>>Inactivos</option>
      </select>
    </div>
    <div class="col-md-3 d-flex gap-2">
      <button class="btn btn-outline-primary flex-grow-1"><i class="bi bi-search"></i> Filtrar</button>
      <a href="<?= base_url('admin/usuarios') ?>" class="btn btn-outline-secondary"><i class="bi bi-x"></i></a>
    </div>
  </div>
</form>

<div class="card shadow-sm">
  <div class="table-responsive">
    <table class="table table-hover mb-0 datatable">
      <thead class="table-light">
        <tr>
          <th>RUT</th>
          <th>Nombre</th>
          <th>Email</th>
          <th>Roles</th>
          <th class="text-center">Estado</th>
          <th>Último login</th>
          <th class="text-end">Acciones</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($usuarios as $u): ?>
        <tr>
          <td><?= e(formatear_rut($u['rut'])) ?></td>
          <td><strong><?= e($u['nombres'] . ' ' . $u['apellidos']) ?></strong></td>
          <td><?= e($u['email']) ?></td>
          <td><span class="text-muted small"><?= e($u['roles'] ?? '—') ?></span></td>
          <td class="text-center">
            <?php if ((int)$u['activo'] === 1): ?>
              <span class="badge bg-success">Activo</span>
            <?php else: ?>
              <span class="badge bg-secondary">Inactivo</span>
            <?php endif; ?>
          </td>
          <td class="small text-muted"><?= e(format_datetime($u['ultimo_login_at'])) ?></td>
          <td class="text-end">
            <a class="btn btn-sm btn-outline-secondary" href="<?= base_url('admin/usuarios/' . (int)$u['id']) ?>" title="Ver"><i class="bi bi-eye"></i></a>
            <?php if (can('admin.usuario.editar')): ?>
              <a class="btn btn-sm btn-outline-primary" href="<?= base_url('admin/usuarios/' . (int)$u['id'] . '/editar') ?>" title="Editar"><i class="bi bi-pencil"></i></a>
            <?php endif; ?>
            <?php if (can('admin.usuario.eliminar') && (int)$u['id'] !== (int)$_user['id']): ?>
              <a class="btn btn-sm btn-outline-danger js-confirm" data-confirm="¿Eliminar este usuario?"
                 href="<?= base_url('admin/usuarios/' . (int)$u['id'] . '/eliminar') ?>" title="Eliminar"><i class="bi bi-trash"></i></a>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
