<?php $isEdit = !empty($is_edit); $c = $compra; ?>
<div class="d-flex align-items-center justify-content-between mb-3">
  <h4 class="mb-0"><i class="bi bi-<?= $isEdit?'pencil':'plus-lg' ?> me-2"></i><?= $isEdit ? 'Editar compra ' . e($c['numero']) : 'Nueva compra / recepción' ?></h4>
  <a class="btn btn-outline-secondary" href="<?= base_url('compras/compras' . ($isEdit ? '/' . (int)$c['id'] : '')) ?>"><i class="bi bi-arrow-left me-1"></i>Volver</a>
</div>

<form method="post" enctype="multipart/form-data"
      action="<?= base_url($isEdit ? "compras/compras/{$c['id']}/editar" : 'compras/compras/crear') ?>" class="card shadow-sm">
  <?= form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()) ?>
  <div class="card-body">
    <h6 class="text-muted text-uppercase small">Datos generales</h6>
    <div class="row g-3 mb-3">
      <div class="col-md-4">
        <label class="form-label">Proyecto</label>
        <select name="proyecto_id" id="proyecto_id" class="form-select select2">
          <option value="">— Sin proyecto (Administración) —</option>
          <?php foreach ($proyectos as $p): ?>
            <option value="<?= (int)$p['id'] ?>" <?= ((int)($c['proyecto_id'] ?? 0)===(int)$p['id'])?'selected':'' ?>>
              <?= e($p['codigo'] . ' · ' . $p['nombre']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-4">
        <label class="form-label">Centro de costo *</label>
        <select name="centro_costo_id" id="centro_costo_id" class="form-select select2" required>
          <option value="">— Seleccionar —</option>
          <?php foreach ($centros as $cc): ?>
            <option value="<?= (int)$cc['id'] ?>" data-proyecto="<?= e($cc['proyecto_id'] ?: '') ?>" <?= ((int)($c['centro_costo_id'] ?? 0)===(int)$cc['id'])?'selected':'' ?>>
              <?= e($cc['codigo'] . ' · ' . $cc['nombre']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-4">
        <label class="form-label">Proveedor *</label>
        <select name="proveedor_id" class="form-select select2" required>
          <option value="">— Seleccionar —</option>
          <?php foreach ($proveedores as $pr): ?>
            <option value="<?= (int)$pr['id'] ?>" <?= ((int)($c['proveedor_id'] ?? 0)===(int)$pr['id'])?'selected':'' ?>>
              <?= e(formatear_rut($pr['rut']) . ' · ' . $pr['razon_social']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-md-3">
        <label class="form-label">Fecha recepción *</label>
        <input type="date" name="fecha_recepcion" class="form-control" required value="<?= e($c['fecha_recepcion'] ?? date('Y-m-d')) ?>">
      </div>
      <div class="col-md-3">
        <label class="form-label">Tipo doc.</label>
        <select name="documento_tipo" class="form-select">
          <option value="">—</option>
          <?php foreach (['factura','boleta','guia','otro'] as $t): ?>
            <option value="<?= $t ?>" <?= (($c['documento_tipo'] ?? '')===$t)?'selected':'' ?>><?= ucfirst($t) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label">Nº doc.</label>
        <input type="text" name="documento_numero" class="form-control" maxlength="40" value="<?= e($c['documento_numero'] ?? '') ?>">
      </div>
      <div class="col-md-3">
        <label class="form-label">Moneda *</label>
        <select name="moneda_id" class="form-select" required>
          <?php foreach ($monedas as $m): $sel = ((int)($c['moneda_id'] ?? 0)===(int)$m['id']) ? 'selected' : ($c ? '' : ($m['codigo']==='CLP' ? 'selected' : '')); ?>
            <option value="<?= (int)$m['id'] ?>" <?= $sel ?>><?= e($m['codigo']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <h6 class="text-muted text-uppercase small">Ítems</h6>
    <div class="table-responsive mb-3">
      <table class="table table-sm" id="items-table">
        <thead class="table-light">
          <tr>
            <th style="width:25%">Descripción</th>
            <th style="width:15%">Tipo gasto</th>
            <th class="text-end" style="width:10%">Cant.</th>
            <th style="width:10%">Unidad</th>
            <th class="text-end" style="width:15%">Precio unit.</th>
            <th class="text-end" style="width:15%">Total línea</th>
            <th style="width:5%"></th>
          </tr>
        </thead>
        <tbody id="items-body">
          <?php if ($items): foreach ($items as $i => $it): ?>
            <tr class="item-row">
              <td><input type="text" name="items[<?= $i ?>][descripcion]" class="form-control form-control-sm" required value="<?= e($it['descripcion']) ?>"></td>
              <td>
                <select name="items[<?= $i ?>][tipo_gasto_id]" class="form-select form-select-sm">
                  <option value="">—</option>
                  <?php foreach ($tipos_gasto as $tg): ?>
                    <option value="<?= (int)$tg['id'] ?>" <?= ((int)($it['tipo_gasto_id'] ?? 0)===(int)$tg['id'])?'selected':'' ?>><?= e($tg['codigo']) ?></option>
                  <?php endforeach; ?>
                </select>
              </td>
              <td><input type="number" name="items[<?= $i ?>][cantidad]" class="form-control form-control-sm text-end item-cant" step="0.001" min="0" value="<?= e($it['cantidad']) ?>"></td>
              <td><input type="text" name="items[<?= $i ?>][unidad]" class="form-control form-control-sm" value="<?= e($it['unidad']) ?>" placeholder="un, m2, kg"></td>
              <td><input type="number" name="items[<?= $i ?>][precio_unitario]" class="form-control form-control-sm text-end item-precio" step="0.01" min="0" value="<?= e($it['precio_unitario']) ?>"></td>
              <td><input type="text" class="form-control form-control-sm text-end item-total" readonly value="<?= e(number_format((float)$it['total_linea'], 2, ',', '.')) ?>"></td>
              <td><button type="button" class="btn btn-sm btn-outline-danger item-del"><i class="bi bi-trash"></i></button></td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
      <button type="button" class="btn btn-sm btn-outline-primary" id="add-item"><i class="bi bi-plus"></i> Agregar ítem</button>
    </div>

    <div class="row g-3 mb-3">
      <div class="col-md-3 offset-md-6">
        <label class="form-label">Monto neto</label>
        <input type="text" id="monto_neto_display" class="form-control text-end" readonly>
      </div>
      <div class="col-md-3">
        <label class="form-label">IVA</label>
        <input type="number" name="monto_iva" id="monto_iva" class="form-control text-end" step="0.01" min="0" value="<?= e($c['monto_iva'] ?? 0) ?>">
      </div>
      <div class="col-md-3 offset-md-9">
        <label class="form-label fw-bold">Total</label>
        <input type="text" id="monto_total_display" class="form-control text-end fw-bold" readonly>
      </div>
    </div>

    <h6 class="text-muted text-uppercase small">Vínculos opcionales</h6>
    <div class="row g-3 mb-3">
      <div class="col-md-6">
        <label class="form-label">Vincular a SDP</label>
        <select name="solicitud_pago_id" class="form-select select2">
          <option value="">— Sin vincular —</option>
          <?php foreach ($sdps as $s): ?>
            <option value="<?= (int)$s['id'] ?>" <?= ((int)($c['solicitud_pago_id'] ?? 0)===(int)$s['id'])?'selected':'' ?>>
              <?= e($s['numero']) ?> · <?= e(format_date($s['fecha_emision'])) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-6">
        <label class="form-label">Vincular a Rinde</label>
        <select name="rinde_id" class="form-select select2">
          <option value="">— Sin vincular —</option>
          <?php foreach ($rindes as $r): ?>
            <option value="<?= (int)$r['id'] ?>" <?= ((int)($c['rinde_id'] ?? 0)===(int)$r['id'])?'selected':'' ?>>
              <?= e($r['numero']) ?> · <?= e(format_date($r['fecha_rendicion'])) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <div class="row g-3">
      <div class="col-12">
        <label class="form-label">Observaciones</label>
        <textarea name="observaciones" rows="2" class="form-control" maxlength="2000"><?= e($c['observaciones'] ?? '') ?></textarea>
      </div>
      <?php if (!$isEdit): ?>
        <div class="col-12">
          <label class="form-label">Adjuntar factura/boleta</label>
          <input type="file" name="archivo" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
        </div>
      <?php endif; ?>
    </div>
  </div>
  <div class="card-footer text-end">
    <button class="btn btn-primary"><i class="bi bi-check2 me-1"></i><?= $isEdit ? 'Guardar' : 'Crear borrador' ?></button>
  </div>
</form>

<template id="item-template">
  <tr class="item-row">
    <td><input type="text" name="items[__I__][descripcion]" class="form-control form-control-sm" required></td>
    <td>
      <select name="items[__I__][tipo_gasto_id]" class="form-select form-select-sm">
        <option value="">—</option>
        <?php foreach ($tipos_gasto as $tg): ?><option value="<?= (int)$tg['id'] ?>"><?= e($tg['codigo']) ?></option><?php endforeach; ?>
      </select>
    </td>
    <td><input type="number" name="items[__I__][cantidad]" class="form-control form-control-sm text-end item-cant" step="0.001" min="0" value="1"></td>
    <td><input type="text" name="items[__I__][unidad]" class="form-control form-control-sm" placeholder="un"></td>
    <td><input type="number" name="items[__I__][precio_unitario]" class="form-control form-control-sm text-end item-precio" step="0.01" min="0"></td>
    <td><input type="text" class="form-control form-control-sm text-end item-total" readonly></td>
    <td><button type="button" class="btn btn-sm btn-outline-danger item-del"><i class="bi bi-trash"></i></button></td>
  </tr>
</template>

<script>
(function () {
  var i = <?= max(1, count($items)) ?>;

  function recalc() {
    var neto = 0;
    document.querySelectorAll('#items-body .item-row').forEach(function (tr) {
      var c = parseFloat(tr.querySelector('.item-cant').value || 0);
      var p = parseFloat(tr.querySelector('.item-precio').value || 0);
      var t = c * p;
      tr.querySelector('.item-total').value = t.toLocaleString('es-CL', {minimumFractionDigits:2, maximumFractionDigits:2});
      neto += t;
    });
    var iva = parseFloat(document.getElementById('monto_iva').value || 0);
    document.getElementById('monto_neto_display').value = neto.toLocaleString('es-CL', {minimumFractionDigits:2});
    document.getElementById('monto_total_display').value = (neto + iva).toLocaleString('es-CL', {minimumFractionDigits:2});
  }

  document.getElementById('add-item').addEventListener('click', function () {
    var html = document.getElementById('item-template').innerHTML.replace(/__I__/g, i++);
    document.getElementById('items-body').insertAdjacentHTML('beforeend', html);
  });

  document.addEventListener('input', function (e) {
    if (e.target.matches('.item-cant, .item-precio, #monto_iva')) recalc();
  });
  document.addEventListener('click', function (e) {
    if (e.target.closest('.item-del')) {
      e.target.closest('tr').remove();
      recalc();
    }
  });

  // Filtro CCs por proyecto
  var proyectoSel = document.getElementById('proyecto_id');
  var ccSel = document.getElementById('centro_costo_id');
  function filtrarCcs() {
    var pid = proyectoSel.value || '';
    Array.from(ccSel.options).forEach(function (opt) {
      if (!opt.value) return;
      var pp = opt.dataset.proyecto || '';
      opt.hidden = pid ? !(pp === '' || pp === pid) : (pp !== '');
    });
    if (ccSel.selectedOptions[0] && ccSel.selectedOptions[0].hidden) ccSel.value = '';
    if (window.jQuery) jQuery(ccSel).trigger('change.select2');
  }
  proyectoSel.addEventListener('change', filtrarCcs);
  filtrarCcs();
  recalc();
  if (i === 1 && !document.querySelector('#items-body .item-row')) document.getElementById('add-item').click();
})();
</script>
