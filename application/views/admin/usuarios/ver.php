<div class="d-flex align-items-center justify-content-between mb-3">
  <h4 class="mb-0"><i class="bi bi-person me-2"></i><?= e($usuario['nombres'] . ' ' . $usuario['apellidos']) ?></h4>
  <div class="d-flex gap-2">
    <a href="<?= base_url('admin/usuarios') ?>" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Volver</a>
    <?php if (can('admin.usuario.editar')): ?>
      <a href="<?= base_url("admin/usuarios/{$usuario['id']}/editar") ?>" class="btn btn-primary"><i class="bi bi-pencil"></i> Editar</a>
    <?php endif; ?>
  </div>
</div>

<div class="row g-3">
  <div class="col-md-8">
    <div class="card shadow-sm"><div class="card-body">
      <div class="row g-2">
        <div class="col-md-4 text-muted small">RUT</div><div class="col-md-8"><?= e(formatear_rut($usuario['rut'])) ?></div>
        <div class="col-md-4 text-muted small">Email</div><div class="col-md-8"><?= e($usuario['email']) ?></div>
        <div class="col-md-4 text-muted small">Teléfono</div><div class="col-md-8"><?= e($usuario['telefono'] ?? '—') ?></div>
        <div class="col-md-4 text-muted small">Estado</div>
        <div class="col-md-8">
          <?= (int)$usuario['activo']===1 ? '<span class="badge bg-success">Activo</span>' : '<span class="badge bg-secondary">Inactivo</span>' ?>
        </div>
        <div class="col-md-4 text-muted small">Último login</div>
        <div class="col-md-8"><?= e(format_datetime($usuario['ultimo_login_at'] ?? '')) ?> <?php if ($usuario['ultimo_login_ip']) echo '<small class="text-muted">desde ' . e($usuario['ultimo_login_ip']) . '</small>'; ?></div>
        <div class="col-md-4 text-muted small">Creado</div><div class="col-md-8"><?= e(format_datetime($usuario['created_at'])) ?></div>
        <div class="col-md-4 text-muted small">Fuerza cambio de clave</div>
        <div class="col-md-8"><?= (int)$usuario['force_password_change']===1 ? '<span class="badge bg-warning text-dark">Sí</span>' : '<span class="text-muted">No</span>' ?></div>
      </div>
    </div></div>
  </div>

  <div class="col-md-4">
    <div class="card shadow-sm"><div class="card-body">
      <h6 class="text-muted small text-uppercase">Roles</h6>
      <?php if (!$roles): ?>
        <span class="text-danger">Sin roles asignados</span>
      <?php else: foreach ($roles as $rn): ?>
        <span class="badge bg-primary me-1 mb-1"><?= e($rn) ?></span>
      <?php endforeach; endif; ?>
    </div></div>
  </div>
</div>
