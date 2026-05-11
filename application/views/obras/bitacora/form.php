<?php $isEdit = !empty($is_edit); ?>
<div class="d-flex align-items-center justify-content-between mb-3">
  <h4 class="mb-0"><i class="bi bi-<?= $isEdit?'pencil':'plus-lg' ?> me-2"></i><?= $isEdit ? 'Editar entrada' : 'Nueva entrada de bitácora' ?></h4>
  <a class="btn btn-outline-secondary" href="<?= $isEdit ? base_url("obras/bitacora/{$b['id']}") : base_url('obras/bitacora?proyecto_id=' . (int)$proyecto_id) ?>"><i class="bi bi-arrow-left me-1"></i>Volver</a>
</div>

<form method="post" enctype="multipart/form-data"
      action="<?= $isEdit ? base_url("obras/bitacora/{$b['id']}/editar") : base_url('obras/bitacora/crear') ?>"
      class="card shadow-sm">
  <?= form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()) ?>
  <div class="card-body">
    <div class="row g-3">
      <div class="col-md-6">
        <label class="form-label">Proyecto *</label>
        <select name="proyecto_id" class="form-select select2" required <?= $isEdit ? 'disabled' : '' ?>>
          <option value="">— Seleccionar —</option>
          <?php foreach ($proyectos as $p): ?>
            <option value="<?= (int)$p['id'] ?>" <?= ((int)($b['proyecto_id'] ?? $proyecto_id) === (int)$p['id'])?'selected':'' ?>>
              <?= e($p['codigo'] . ' · ' . $p['nombre']) ?>
            </option>
          <?php endforeach; ?>
        </select>
        <?php if ($isEdit): ?><input type="hidden" name="proyecto_id" value="<?= (int)$b['proyecto_id'] ?>"><?php endif; ?>
      </div>
      <div class="col-md-3">
        <label class="form-label">Fecha del evento *</label>
        <input type="date" name="fecha_evento" class="form-control" required value="<?= e($b['fecha_evento'] ?? date('Y-m-d')) ?>">
      </div>
      <div class="col-md-3">
        <label class="form-label">Tipo *</label>
        <select name="tipo_evento" class="form-select" required>
          <?php foreach (['avance','observacion','incidencia','otro'] as $t): ?>
            <option value="<?= $t ?>" <?= (($b['tipo_evento'] ?? 'avance')===$t)?'selected':'' ?>><?= ucfirst($t) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-12">
        <label class="form-label">Título *</label>
        <input type="text" name="titulo" class="form-control" maxlength="180" required value="<?= e($b['titulo'] ?? '') ?>">
      </div>
      <div class="col-12">
        <label class="form-label">Detalle *</label>
        <textarea name="detalle" rows="6" class="form-control" required minlength="10"><?= e($b['detalle'] ?? '') ?></textarea>
      </div>
      <?php if (!$isEdit): ?>
        <div class="col-12">
          <label class="form-label">Adjuntos (opcional)</label>
          <input type="file" name="archivo" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
        </div>
      <?php endif; ?>
    </div>
  </div>
  <div class="card-footer text-end">
    <button class="btn btn-primary"><i class="bi bi-check2 me-1"></i><?= $isEdit ? 'Guardar' : 'Crear entrada' ?></button>
  </div>
</form>
