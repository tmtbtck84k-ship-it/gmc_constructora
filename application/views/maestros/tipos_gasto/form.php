<?php $isEdit = !empty($is_edit); $t = $tipo; ?>
<div class="d-flex align-items-center justify-content-between mb-3">
  <h4 class="mb-0"><i class="bi bi-<?= $isEdit ? 'pencil' : 'plus-lg' ?> me-2"></i><?= $isEdit ? 'Editar' : 'Nuevo' ?> tipo de gasto</h4>
  <a class="btn btn-outline-secondary" href="<?= base_url('maestros/tipos-gasto') ?>"><i class="bi bi-arrow-left me-1"></i>Volver</a>
</div>

<form method="post" action="<?= base_url($isEdit ? "maestros/tipos-gasto/editar/{$t['id']}" : 'maestros/tipos-gasto/crear') ?>" class="card shadow-sm">
  <?= form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()) ?>
  <div class="card-body">
    <div class="row g-3">
      <div class="col-md-3">
        <label class="form-label">Código *</label>
        <input type="text" name="codigo" class="form-control text-uppercase" maxlength="30" required
               pattern="[A-Z0-9_-]{1,30}" value="<?= e($t['codigo'] ?? '') ?>">
        <div class="form-text">Mayúsculas, números y - _</div>
      </div>
      <div class="col-md-7">
        <label class="form-label">Nombre *</label>
        <input type="text" name="nombre" class="form-control" maxlength="120" required value="<?= e($t['nombre'] ?? '') ?>">
      </div>
      <div class="col-md-2">
        <label class="form-label">Estado</label>
        <select name="activo" class="form-select">
          <option value="1" <?= ($t['activo'] ?? 1) ? 'selected' : '' ?>>Activo</option>
          <option value="0" <?= !($t['activo'] ?? 1) ? 'selected' : '' ?>>Inactivo</option>
        </select>
      </div>
    </div>
  </div>
  <div class="card-footer text-end">
    <button class="btn btn-primary"><i class="bi bi-check2 me-1"></i><?= $isEdit ? 'Guardar' : 'Crear' ?></button>
  </div>
</form>
