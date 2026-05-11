<?php $isEdit = !empty($is_edit); $s = $sdp; ?>
<div class="d-flex align-items-center justify-content-between mb-3">
  <h4 class="mb-0">
    <i class="bi bi-<?= $isEdit ? 'pencil' : 'plus-lg' ?> me-2"></i>
    <?= $isEdit ? 'Editar SDP ' . e($s['numero']) : 'Nueva Solicitud de Pago' ?>
  </h4>
  <a class="btn btn-outline-secondary" href="<?= base_url('finanzas/sdp' . ($isEdit ? '/' . (int)$s['id'] : '')) ?>"><i class="bi bi-arrow-left me-1"></i>Volver</a>
</div>

<form method="post" action="<?= base_url($isEdit ? "finanzas/sdp/{$s['id']}/editar" : 'finanzas/sdp/crear') ?>"
      enctype="multipart/form-data" class="card shadow-sm">
  <?= form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()) ?>
  <div class="card-body">

    <h6 class="text-muted text-uppercase small">Asignación</h6>
    <div class="row g-3 mb-3">
      <div class="col-md-6">
        <label class="form-label">Proyecto <span class="text-muted small">(opcional si es Administración general)</span></label>
        <select name="proyecto_id" id="proyecto_id" class="form-select select2">
          <option value="">— Sin proyecto (Administración general) —</option>
          <?php foreach ($proyectos as $p): ?>
            <option value="<?= (int)$p['id'] ?>" <?= ((int)($s['proyecto_id'] ?? 0)===(int)$p['id'])?'selected':'' ?>>
              <?= e($p['codigo'] . ' · ' . $p['nombre']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-6">
        <label class="form-label">Centro de costo *</label>
        <select name="centro_costo_id" id="centro_costo_id" class="form-select select2" required>
          <option value="">— Seleccionar —</option>
          <?php foreach ($centros as $cc): ?>
            <option value="<?= (int)$cc['id'] ?>"
                    data-proyecto="<?= e($cc['proyecto_id'] ?: '') ?>"
                    <?= ((int)($s['centro_costo_id'] ?? 0)===(int)$cc['id'])?'selected':'' ?>>
              <?= e($cc['codigo'] . ' · ' . $cc['nombre']) ?>
              <?php if (!$cc['proyecto_id']): ?> (general)<?php endif; ?>
            </option>
          <?php endforeach; ?>
        </select>
        <div class="form-text">Si el proyecto está vacío, sólo verás el CC ADM general.</div>
      </div>

      <div class="col-md-6">
        <label class="form-label">Proveedor *</label>
        <select name="proveedor_id" class="form-select select2" required>
          <option value="">— Seleccionar —</option>
          <?php foreach ($proveedores as $pr): ?>
            <option value="<?= (int)$pr['id'] ?>" <?= ((int)($s['proveedor_id'] ?? 0)===(int)$pr['id'])?'selected':'' ?>>
              <?= e(formatear_rut($pr['rut']) . ' · ' . $pr['razon_social']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-6">
        <label class="form-label">Tipo de gasto *</label>
        <select name="tipo_gasto_id" class="form-select select2" required>
          <option value="">— Seleccionar —</option>
          <?php foreach ($tipos_gasto as $tg): ?>
            <option value="<?= (int)$tg['id'] ?>" <?= ((int)($s['tipo_gasto_id'] ?? 0)===(int)$tg['id'])?'selected':'' ?>>
              <?= e($tg['codigo'] . ' · ' . $tg['nombre']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <h6 class="text-muted text-uppercase small">Documento</h6>
    <div class="row g-3 mb-3">
      <div class="col-md-3">
        <label class="form-label">Tipo doc.</label>
        <select name="documento_tipo" class="form-select">
          <option value="">—</option>
          <?php foreach (['factura','boleta','honorarios','nota_credito','otro'] as $t): ?>
            <option value="<?= e($t) ?>" <?= (($s['documento_tipo'] ?? '')===$t)?'selected':'' ?>><?= e(ucfirst(str_replace('_',' ',$t))) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label">Nº documento</label>
        <input type="text" name="documento_numero" class="form-control" maxlength="40" value="<?= e($s['documento_numero'] ?? '') ?>">
      </div>
      <div class="col-md-3">
        <label class="form-label">Fecha emisión *</label>
        <input type="date" name="fecha_emision" class="form-control" required value="<?= e($s['fecha_emision'] ?? date('Y-m-d')) ?>">
      </div>
      <div class="col-md-3">
        <label class="form-label">Fecha vencimiento</label>
        <input type="date" name="fecha_vencimiento" class="form-control" value="<?= e($s['fecha_vencimiento'] ?? '') ?>">
      </div>
    </div>

    <h6 class="text-muted text-uppercase small">Montos</h6>
    <div class="row g-3 mb-3">
      <div class="col-md-3">
        <label class="form-label">Moneda *</label>
        <select name="moneda_id" id="moneda_id" class="form-select" required>
          <?php foreach ($monedas as $m):
            $sel = ((int)($s['moneda_id'] ?? 0)===(int)$m['id']) ? 'selected' : ($s ? '' : ($m['codigo']==='CLP' ? 'selected' : '')); ?>
            <option value="<?= (int)$m['id'] ?>" data-codigo="<?= e($m['codigo']) ?>" <?= $sel ?>><?= e($m['codigo'] . ' · ' . $m['nombre']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label">Monto neto *</label>
        <input type="number" step="0.01" min="0" name="monto_neto" id="monto_neto" class="form-control text-end" required value="<?= e($s['monto_neto'] ?? 0) ?>">
      </div>
      <div class="col-md-3">
        <label class="form-label">IVA</label>
        <input type="number" step="0.01" min="0" name="monto_iva" id="monto_iva" class="form-control text-end" value="<?= e($s['monto_iva'] ?? 0) ?>">
      </div>
      <div class="col-md-3">
        <label class="form-label">Total <span class="text-muted small">(calculado)</span></label>
        <input type="text" id="monto_total_display" class="form-control text-end fw-bold" readonly value="0,00">
      </div>
    </div>

    <h6 class="text-muted text-uppercase small">Detalle</h6>
    <div class="row g-3 mb-3">
      <div class="col-md-12">
        <label class="form-label">Descripción</label>
        <textarea name="descripcion" rows="2" class="form-control" maxlength="2000"><?= e($s['descripcion'] ?? '') ?></textarea>
      </div>
      <div class="col-md-12">
        <label class="form-label">Comentarios internos</label>
        <textarea name="comentarios" rows="2" class="form-control" maxlength="2000"><?= e($s['comentarios'] ?? '') ?></textarea>
      </div>
    </div>

    <?php if (!$isEdit): ?>
      <h6 class="text-muted text-uppercase small">Adjunto de respaldo</h6>
      <div class="row g-3">
        <div class="col-md-3">
          <label class="form-label">Categoría</label>
          <select name="categoria_adjunto" class="form-select">
            <option value="factura">Factura</option>
            <option value="boleta">Boleta</option>
            <option value="comprobante">Comprobante</option>
            <option value="otro">Otro</option>
          </select>
        </div>
        <div class="col-md-9">
          <label class="form-label">Archivo (PDF, JPG, PNG, etc · máx 10 MB)</label>
          <input type="file" name="archivo" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx">
        </div>
      </div>
    <?php endif; ?>

  </div>
  <div class="card-footer text-end">
    <button class="btn btn-primary"><i class="bi bi-check2 me-1"></i><?= $isEdit ? 'Guardar cambios' : 'Crear SDP' ?></button>
  </div>
</form>

<script>
(function () {
  // Recalcular total y formatear
  function recalc() {
    var n = parseFloat(document.getElementById('monto_neto').value || 0);
    var i = parseFloat(document.getElementById('monto_iva').value || 0);
    var t = (isNaN(n)?0:n) + (isNaN(i)?0:i);
    document.getElementById('monto_total_display').value = t.toLocaleString('es-CL', {minimumFractionDigits:2, maximumFractionDigits:2});
  }
  document.getElementById('monto_neto').addEventListener('input', recalc);
  document.getElementById('monto_iva').addEventListener('input', recalc);
  recalc();

  // Filtrar CC según proyecto seleccionado
  var proyectoSel = document.getElementById('proyecto_id');
  var ccSel = document.getElementById('centro_costo_id');
  function filtrarCcs() {
    var pid = proyectoSel.value || '';
    Array.from(ccSel.options).forEach(function (opt) {
      if (!opt.value) { opt.hidden = false; return; }
      var pp = opt.dataset.proyecto || '';
      // Mostrar si: es CC general (sin proyecto) o pertenece al proyecto seleccionado
      opt.hidden = pid ? !(pp === '' || pp === pid) : (pp !== '');
    });
    // Si la opción seleccionada quedó oculta, limpiarla
    if (ccSel.selectedOptions[0] && ccSel.selectedOptions[0].hidden) ccSel.value = '';
    if (window.jQuery) jQuery(ccSel).trigger('change.select2');
  }
  proyectoSel.addEventListener('change', filtrarCcs);
  filtrarCcs();
})();
</script>
