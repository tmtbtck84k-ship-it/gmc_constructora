<?php $isEdit = !empty($is_edit); $p = $proveedor; ?>
<div class="d-flex align-items-center justify-content-between mb-3">
  <h4 class="mb-0"><i class="bi bi-<?= $isEdit ? 'pencil' : 'plus-lg' ?> me-2"></i><?= $isEdit ? 'Editar' : 'Nuevo' ?> proveedor</h4>
  <a class="btn btn-outline-secondary" href="<?= base_url('maestros/proveedores') ?>"><i class="bi bi-arrow-left me-1"></i>Volver</a>
</div>

<form method="post" action="<?= base_url($isEdit ? "maestros/proveedores/editar/{$p['id']}" : 'maestros/proveedores/crear') ?>" class="card shadow-sm">
  <?= form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()) ?>
  <div class="card-body">
    <h6 class="text-muted text-uppercase small mt-2">Identificación</h6>
    <div class="row g-3 mb-3">
      <div class="col-md-3">
        <label class="form-label">RUT *</label>
        <input type="text" name="rut" class="form-control" placeholder="12.345.678-9" value="<?= e($isEdit ? formatear_rut($p['rut']) : '') ?>" required>
      </div>
      <div class="col-md-6">
        <label class="form-label">Razón social *</label>
        <input type="text" name="razon_social" class="form-control" maxlength="180" required value="<?= e($p['razon_social'] ?? '') ?>">
      </div>
      <div class="col-md-3">
        <label class="form-label">Estado</label>
        <select name="activo" class="form-select">
          <option value="1" <?= ($p['activo'] ?? 1) ? 'selected' : '' ?>>Activo</option>
          <option value="0" <?= !($p['activo'] ?? 1) ? 'selected' : '' ?>>Inactivo</option>
        </select>
      </div>

      <div class="col-md-4">
        <label class="form-label">Nombre de fantasía</label>
        <input type="text" name="nombre_fantasia" class="form-control" maxlength="180" value="<?= e($p['nombre_fantasia'] ?? '') ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label">Giro</label>
        <input type="text" name="giro" class="form-control" maxlength="180" value="<?= e($p['giro'] ?? '') ?>">
      </div>
      <div class="col-md-2">
        <label class="form-label">Categoría</label>
        <input type="text" name="categoria" class="form-control" maxlength="80" placeholder="ej: Áridos" value="<?= e($p['categoria'] ?? '') ?>">
      </div>
      <div class="col-md-2 d-flex align-items-end">
        <div class="form-check">
          <input type="hidden" name="es_subcontratista" value="0">
          <input class="form-check-input" type="checkbox" id="es_subc" name="es_subcontratista" value="1" <?= !empty($p['es_subcontratista']) ? 'checked' : '' ?>>
          <label class="form-check-label" for="es_subc">Subcontratista</label>
        </div>
      </div>
    </div>

    <h6 class="text-muted text-uppercase small">Dirección y contacto</h6>
    <div class="row g-3 mb-3">
      <div class="col-md-7">
        <label class="form-label">Dirección</label>
        <input type="text" name="direccion" class="form-control" maxlength="180" value="<?= e($p['direccion'] ?? '') ?>">
      </div>
      <div class="col-md-5">
        <label class="form-label">Comuna</label>
        <select name="comuna_id" class="form-select select2">
          <option value="">— Seleccionar —</option>
          <?php $lastReg = null; foreach ($comunas as $row): if ($row['region_id'] !== $lastReg): ?>
            <?php if ($lastReg !== null) echo '</optgroup>'; ?>
            <optgroup label="<?= e($row['region']) ?>">
            <?php $lastReg = $row['region_id']; endif; ?>
            <option value="<?= (int)$row['id'] ?>" <?= (isset($p['comuna_id']) && (int)$p['comuna_id'] === (int)$row['id']) ? 'selected' : '' ?>>
              <?= e($row['comuna']) ?>
            </option>
          <?php endforeach; if ($lastReg !== null) echo '</optgroup>'; ?>
        </select>
      </div>
      <div class="col-md-6">
        <label class="form-label">Email</label>
        <input type="email" name="email" class="form-control" maxlength="120" value="<?= e($p['email'] ?? '') ?>">
      </div>
      <div class="col-md-6">
        <label class="form-label">Teléfono</label>
        <input type="text" name="telefono" class="form-control" maxlength="30" value="<?= e($p['telefono'] ?? '') ?>">
      </div>
    </div>

    <h6 class="text-muted text-uppercase small">Persona de contacto</h6>
    <div class="row g-3">
      <div class="col-md-4">
        <label class="form-label">Nombre</label>
        <input type="text" name="contacto_nombre" class="form-control" maxlength="120" value="<?= e($p['contacto_nombre'] ?? '') ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label">Email</label>
        <input type="email" name="contacto_email" class="form-control" maxlength="120" value="<?= e($p['contacto_email'] ?? '') ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label">Teléfono</label>
        <input type="text" name="contacto_telefono" class="form-control" maxlength="30" value="<?= e($p['contacto_telefono'] ?? '') ?>">
      </div>
    </div>
  </div>
  <div class="card-footer text-end">
    <button class="btn btn-primary"><i class="bi bi-check2 me-1"></i><?= $isEdit ? 'Guardar' : 'Crear' ?></button>
  </div>
</form>
