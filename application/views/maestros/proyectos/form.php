<?php $isEdit = !empty($is_edit); $p = $proyecto; ?>
<div class="d-flex align-items-center justify-content-between mb-3">
  <h4 class="mb-0">
    <i class="bi bi-<?= $isEdit ? 'pencil' : 'plus-lg' ?> me-2"></i>
    <?= $isEdit ? 'Editar' : 'Nuevo' ?> proyecto
  </h4>
  <a class="btn btn-outline-secondary" href="<?= base_url('maestros/proyectos') ?>"><i class="bi bi-arrow-left me-1"></i>Volver</a>
</div>

<form method="post" action="<?= base_url($isEdit ? "maestros/proyectos/editar/{$p['id']}" : 'maestros/proyectos/crear') ?>" class="card shadow-sm">
  <?= form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()) ?>
  <div class="card-body">

    <?php if (!$isEdit): ?>
      <div class="alert alert-info small mb-3">
        <i class="bi bi-info-circle me-1"></i>
        Al crear, se asigna automáticamente un código <code>OBR-AAAA-NNN</code> y se crea el centro de costo <code>ADM-OBR</code> del proyecto.
      </div>
    <?php endif; ?>

    <h6 class="text-muted text-uppercase small">Datos del proyecto</h6>
    <div class="row g-3 mb-3">
      <?php if ($isEdit): ?>
        <div class="col-md-3">
          <label class="form-label">Código</label>
          <input type="text" class="form-control" value="<?= e($p['codigo']) ?>" disabled>
        </div>
      <?php endif; ?>
      <div class="col-md-<?= $isEdit ? 9 : 8 ?>">
        <label class="form-label">Nombre del proyecto *</label>
        <input type="text" name="nombre" class="form-control" maxlength="180" required value="<?= e($p['nombre'] ?? '') ?>">
      </div>
      <?php if (!$isEdit): ?>
        <div class="col-md-4">
          <label class="form-label">Estado</label>
          <input type="text" class="form-control" value="En planificación" disabled>
        </div>
      <?php else: ?>
        <div class="col-md-4">
          <label class="form-label">Estado</label>
          <select name="estado_id" class="form-select">
            <?php foreach ($estados as $st): ?>
              <option value="<?= (int)$st['id'] ?>" <?= ((int)($p['estado_id'] ?? 0)===(int)$st['id'])?'selected':'' ?>><?= e($st['nombre']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      <?php endif; ?>

      <div class="col-md-6">
        <label class="form-label">Cliente *</label>
        <select name="cliente_id" class="form-select select2" required>
          <option value="">— Seleccionar —</option>
          <?php foreach ($clientes as $c): ?>
            <option value="<?= (int)$c['id'] ?>" <?= ((int)($p['cliente_id'] ?? 0)===(int)$c['id'])?'selected':'' ?>>
              <?= e(formatear_rut($c['rut']) . ' · ' . $c['razon_social']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-md-6">
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

      <div class="col-12">
        <label class="form-label">Dirección</label>
        <input type="text" name="direccion" class="form-control" maxlength="255" value="<?= e($p['direccion'] ?? '') ?>">
      </div>
    </div>

    <h6 class="text-muted text-uppercase small">Equipo</h6>
    <div class="row g-3 mb-3">
      <div class="col-md-6">
        <label class="form-label">Jefe de Proyecto</label>
        <select name="jefe_proyecto_id" class="form-select select2">
          <option value="">— Seleccionar —</option>
          <?php foreach ($usuarios as $u): ?>
            <option value="<?= (int)$u['id'] ?>" <?= ((int)($p['jefe_proyecto_id'] ?? 0)===(int)$u['id'])?'selected':'' ?>>
              <?= e($u['nombres'] . ' ' . $u['apellidos'] . ' (' . $u['email'] . ')') ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-6">
        <label class="form-label">Administrador de Obra</label>
        <select name="administrador_obra_id" class="form-select select2">
          <option value="">— Seleccionar —</option>
          <?php foreach ($usuarios as $u): ?>
            <option value="<?= (int)$u['id'] ?>" <?= ((int)($p['administrador_obra_id'] ?? 0)===(int)$u['id'])?'selected':'' ?>>
              <?= e($u['nombres'] . ' ' . $u['apellidos'] . ' (' . $u['email'] . ')') ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <h6 class="text-muted text-uppercase small">Plazos y económicos</h6>
    <div class="row g-3 mb-3">
      <div class="col-md-3">
        <label class="form-label">Fecha inicio</label>
        <input type="date" name="fecha_inicio" class="form-control" value="<?= e($p['fecha_inicio'] ?? '') ?>">
      </div>
      <div class="col-md-3">
        <label class="form-label">Fecha término estimada</label>
        <input type="date" name="fecha_termino_estimada" class="form-control" value="<?= e($p['fecha_termino_estimada'] ?? '') ?>">
      </div>
      <div class="col-md-3">
        <label class="form-label">Valor UF referencia</label>
        <input type="number" step="0.0001" name="valor_uf_referencia" class="form-control" value="<?= e($p['valor_uf_referencia'] ?? '') ?>">
      </div>
    </div>

    <h6 class="text-muted text-uppercase small">Calendario laboral</h6>
    <?php
        $diasSel    = $p['dias_laborales'] ?? 'lun_vie';
        $diasCustom = $p['dias_laborales_custom'] ?? '';
        $trabajaFer = (int)($p['trabaja_feriados'] ?? 0);
        $diasMap    = ['L'=>'L','Ma'=>'M','Mi'=>'X','J'=>'J','V'=>'V','S'=>'S','D'=>'D'];
        $custSet    = array_filter(array_map('trim', explode(',', $diasCustom)));
    ?>
    <div class="row g-3 mb-3">
      <div class="col-md-4">
        <label class="form-label">Días laborales</label>
        <select name="dias_laborales" id="dias_laborales" class="form-select"
                onchange="document.getElementById('bloque_custom').style.display = this.value === 'personalizado' ? '' : 'none';">
          <option value="lun_vie"      <?= $diasSel === 'lun_vie'      ? 'selected' : '' ?>>Lunes a Viernes</option>
          <option value="lun_sab"      <?= $diasSel === 'lun_sab'      ? 'selected' : '' ?>>Lunes a Sábado</option>
          <option value="lun_dom"      <?= $diasSel === 'lun_dom'      ? 'selected' : '' ?>>Lunes a Domingo</option>
          <option value="personalizado"<?= $diasSel === 'personalizado'? 'selected' : '' ?>>Personalizado</option>
        </select>
      </div>
      <div class="col-md-5" id="bloque_custom" style="<?= $diasSel === 'personalizado' ? '' : 'display:none;' ?>">
        <label class="form-label">Días personalizados</label>
        <div class="d-flex flex-wrap gap-2">
          <?php foreach ($diasMap as $key => $label): ?>
            <div class="form-check">
              <input class="form-check-input" type="checkbox"
                     name="dias_laborales_custom[]"
                     value="<?= $key ?>"
                     id="dia_<?= $key ?>"
                     <?= in_array($key, $custSet, true) ? 'checked' : '' ?>>
              <label class="form-check-label" for="dia_<?= $key ?>"><?= $label ?></label>
            </div>
          <?php endforeach; ?>
        </div>
        <small class="text-muted">Ej. <code>L,Mi,V,S</code></small>
      </div>
      <div class="col-md-3 d-flex align-items-end">
        <div class="form-check">
          <input class="form-check-input" type="checkbox" name="trabaja_feriados" id="trabaja_feriados" value="1"
                 <?= $trabajaFer ? 'checked' : '' ?>>
          <label class="form-check-label" for="trabaja_feriados">Trabaja feriados</label>
        </div>
      </div>
    </div>

    <div class="row g-3">
      <div class="col-12">
        <label class="form-label">Observaciones</label>
        <textarea name="observaciones" rows="3" class="form-control" maxlength="2000"><?= e($p['observaciones'] ?? '') ?></textarea>
      </div>
    </div>
  </div>

  <div class="card-footer text-end">
    <button class="btn btn-primary"><i class="bi bi-check2 me-1"></i><?= $isEdit ? 'Guardar cambios' : 'Crear proyecto' ?></button>
  </div>
</form>
