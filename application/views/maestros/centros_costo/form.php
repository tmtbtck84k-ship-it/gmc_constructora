<?php $isEdit = !empty($is_edit); $cc = $centro; ?>
<div class="d-flex align-items-center justify-content-between mb-3">
  <h4 class="mb-0"><i class="bi bi-<?= $isEdit ? 'pencil' : 'plus-lg' ?> me-2"></i><?= $isEdit ? 'Editar' : 'Nuevo' ?> centro de costo</h4>
  <a class="btn btn-outline-secondary" href="<?= base_url('maestros/centros-costo' . ($proyecto_id ? '?proyecto_id=' . $proyecto_id : '')) ?>"><i class="bi bi-arrow-left me-1"></i>Volver</a>
</div>

<form method="post" action="<?= base_url($isEdit ? "maestros/centros-costo/editar/{$cc['id']}" : 'maestros/centros-costo/crear') ?>" class="card shadow-sm">
  <?= form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()) ?>
  <div class="card-body">
    <div class="row g-3">
      <div class="col-md-6">
        <label class="form-label">Proyecto</label>
        <select name="proyecto_id" class="form-select select2" <?= $isEdit ? 'disabled' : '' ?>>
          <option value="">— Sin proyecto (general) —</option>
          <?php foreach ($proyectos as $pr): ?>
            <option value="<?= (int)$pr['id'] ?>" <?= ((int)$proyecto_id===(int)$pr['id'])?'selected':'' ?>>
              <?= e($pr['codigo'] . ' · ' . $pr['nombre']) ?>
            </option>
          <?php endforeach; ?>
        </select>
        <?php if ($isEdit): ?>
          <input type="hidden" name="proyecto_id" value="<?= (int)$proyecto_id ?>">
          <div class="form-text">El proyecto del CC no se puede cambiar después de crearlo.</div>
        <?php endif; ?>
      </div>

      <div class="col-md-3">
        <label class="form-label">Código *</label>
        <input type="text" name="codigo" class="form-control text-uppercase" maxlength="30" required
               pattern="[A-Z0-9_-]{1,30}" value="<?= e($cc['codigo'] ?? '') ?>">
        <div class="form-text">Mayúsculas, números y - _</div>
      </div>

      <div class="col-md-3">
        <label class="form-label">Estado</label>
        <select name="activo" class="form-select">
          <option value="1" <?= ($cc['activo'] ?? 1) ? 'selected' : '' ?>>Activo</option>
          <option value="0" <?= !($cc['activo'] ?? 1) ? 'selected' : '' ?>>Inactivo</option>
        </select>
      </div>

      <div class="col-12">
        <label class="form-label">Nombre *</label>
        <input type="text" name="nombre" class="form-control" maxlength="120" required value="<?= e($cc['nombre'] ?? '') ?>">
      </div>
    </div>
  </div>
  <div class="card-footer text-end">
    <button class="btn btn-primary"><i class="bi bi-check2 me-1"></i><?= $isEdit ? 'Guardar' : 'Crear' ?></button>
  </div>
</form>
