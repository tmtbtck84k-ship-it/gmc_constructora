<?php $u = $usuario; $isEdit = !empty($is_edit); ?>
<div class="d-flex align-items-center justify-content-between mb-3">
  <h4 class="mb-0">
    <i class="bi bi-<?= $isEdit ? 'pencil' : 'person-plus' ?> me-2"></i>
    <?= $isEdit ? 'Editar usuario' : 'Nuevo usuario' ?>
  </h4>
  <a href="<?= base_url('admin/usuarios') ?>" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Volver</a>
</div>

<form method="post" action="<?= base_url($isEdit ? "admin/usuarios/{$u['id']}/editar" : 'admin/usuarios/crear') ?>" class="card shadow-sm">
  <?= form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()) ?>

  <div class="card-body">
    <div class="row g-3">
      <div class="col-md-4">
        <label class="form-label">RUT</label>
        <input type="text" name="rut" class="form-control" placeholder="12.345.678-9"
               value="<?= e($isEdit ? formatear_rut($u['rut']) : '') ?>" required>
      </div>
      <div class="col-md-4">
        <label class="form-label">Nombres</label>
        <input type="text" name="nombres" class="form-control" maxlength="80"
               value="<?= e($u['nombres'] ?? '') ?>" required>
      </div>
      <div class="col-md-4">
        <label class="form-label">Apellidos</label>
        <input type="text" name="apellidos" class="form-control" maxlength="80"
               value="<?= e($u['apellidos'] ?? '') ?>" required>
      </div>

      <div class="col-md-6">
        <label class="form-label">Email</label>
        <input type="email" name="email" class="form-control" maxlength="120"
               value="<?= e($u['email'] ?? '') ?>" required>
      </div>
      <div class="col-md-3">
        <label class="form-label">Teléfono</label>
        <input type="text" name="telefono" class="form-control" maxlength="20"
               value="<?= e($u['telefono'] ?? '') ?>">
      </div>
      <div class="col-md-3">
        <label class="form-label">Estado</label>
        <select name="activo" class="form-select">
          <option value="1" <?= ($u['activo'] ?? 1) ? 'selected' : '' ?>>Activo</option>
          <option value="0" <?= !($u['activo'] ?? 1) ? 'selected' : '' ?>>Inactivo</option>
        </select>
      </div>

      <div class="col-12">
        <label class="form-label">Roles asignados</label>
        <select name="roles[]" class="form-select select2" multiple required>
          <?php foreach ($roles as $r): ?>
            <option value="<?= (int)$r['id'] ?>" <?= in_array((int)$r['id'], $rolesUser, true) ? 'selected' : '' ?>>
              <?= e($r['nombre']) ?> (<?= e($r['codigo']) ?>)
            </option>
          <?php endforeach; ?>
        </select>
        <div class="form-text">Puedes asignar más de un rol al mismo usuario.</div>
      </div>

      <?php if (!$isEdit): ?>
        <div class="col-12">
          <div class="alert alert-info small mb-0">
            <i class="bi bi-info-circle me-1"></i>
            Se generará una clave temporal segura. El usuario será obligado a cambiarla al primer ingreso, y recibirá un correo con sus credenciales.
          </div>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <div class="card-footer d-flex justify-content-between">
    <?php if ($isEdit && can('admin.usuario.editar')): ?>
      <a href="<?= base_url("admin/usuarios/{$u['id']}/reset") ?>"
         class="btn btn-outline-warning js-confirm"
         data-confirm="¿Restablecer la contraseña a una clave temporal?">
        <i class="bi bi-key me-1"></i> Restablecer contraseña
      </a>
    <?php else: ?>
      <span></span>
    <?php endif; ?>
    <button class="btn btn-primary"><i class="bi bi-check2 me-1"></i><?= $isEdit ? 'Guardar cambios' : 'Crear usuario' ?></button>
  </div>
</form>
