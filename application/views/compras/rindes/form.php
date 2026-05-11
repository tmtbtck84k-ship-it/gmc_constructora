<?php $isEdit = !empty($is_edit); $r = $rinde; ?>
<div class="d-flex align-items-center justify-content-between mb-3">
  <h4 class="mb-0"><i class="bi bi-<?= $isEdit?'pencil':'plus-lg' ?> me-2"></i><?= $isEdit ? 'Editar rinde ' . e($r['numero']) : 'Nuevo rinde de gastos' ?></h4>
  <a class="btn btn-outline-secondary" href="<?= base_url('compras/rindes' . ($isEdit ? '/' . (int)$r['id'] : '')) ?>"><i class="bi bi-arrow-left me-1"></i>Volver</a>
</div>

<form method="post" enctype="multipart/form-data"
      action="<?= base_url($isEdit ? "compras/rindes/{$r['id']}/editar" : 'compras/rindes/crear') ?>" class="card shadow-sm">
  <?= form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()) ?>
  <div class="card-body">
    <h6 class="text-muted text-uppercase small">Datos generales</h6>
    <div class="row g-3 mb-3">
      <div class="col-md-4">
        <label class="form-label">Proyecto</label>
        <select name="proyecto_id" id="proyecto_id" class="form-select select2">
          <option value="">— Sin proyecto (Administración) —</option>
          <?php foreach ($proyectos as $p): ?>
            <option value="<?= (int)$p['id'] ?>" <?= ((int)($r['proyecto_id'] ?? 0)===(int)$p['id'])?'selected':'' ?>>
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
            <option value="<?= (int)$cc['id'] ?>" data-proyecto="<?= e($cc['proyecto_id'] ?: '') ?>" <?= ((int)($r['centro_costo_id'] ?? 0)===(int)$cc['id'])?'selected':'' ?>>
              <?= e($cc['codigo'] . ' · ' . $cc['nombre']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label">Fecha rendición *</label>
        <input type="date" name="fecha_rendicion" class="form-control" required value="<?= e($r['fecha_rendicion'] ?? date('Y-m-d')) ?>">
      </div>
      <div class="col-md-2">
        <label class="form-label">Moneda *</label>
        <select name="moneda_id" class="form-select" required>
          <?php foreach ($monedas as $m): $sel = ((int)($r['moneda_id'] ?? 0)===(int)$m['id']) ? 'selected' : ($r ? '' : ($m['codigo']==='CLP' ? 'selected' : '')); ?>
            <option value="<?= (int)$m['id'] ?>" <?= $sel ?>><?= e($m['codigo']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <h6 class="text-muted text-uppercase small">Ítems del gasto</h6>
    <div class="table-responsive mb-3">
      <table class="table table-sm" id="items-table">
        <thead class="table-light">
          <tr>
            <th style="width:14%">Fecha</th>
            <th style="width:18%">Tipo gasto</th>
            <th>Descripción</th>
            <th style="width:14%">Tipo doc</th>
            <th style="width:14%">Nº doc</th>
            <th class="text-end" style="width:14%">Monto</th>
            <th style="width:5%"></th>
          </tr>
        </thead>
        <tbody id="items-body">
          <?php if ($items): foreach ($items as $i => $it): ?>
            <tr class="item-row">
              <td><input type="date" name="items[<?= $i ?>][fecha]" class="form-control form-control-sm" value="<?= e($it['fecha']) ?>" required></td>
              <td>
                <select name="items[<?= $i ?>][tipo_gasto_id]" class="form-select form-select-sm" required>
                  <option value="">—</option>
                  <?php foreach ($tipos_gasto as $tg): ?>
                    <option value="<?= (int)$tg['id'] ?>" <?= ((int)($it['tipo_gasto_id'] ?? 0)===(int)$tg['id'])?'selected':'' ?>><?= e($tg['codigo']) ?></option>
                  <?php endforeach; ?>
                </select>
              </td>
              <td><input type="text" name="items[<?= $i ?>][descripcion]" class="form-control form-control-sm" required value="<?= e($it['descripcion']) ?>"></td>
              <td>
                <select name="items[<?= $i ?>][documento_tipo]" class="form-select form-select-sm">
                  <option value="">—</option>
                  <?php foreach (['boleta','factura','ticket','otro'] as $t): ?>
                    <option value="<?= $t ?>" <?= (($it['documento_tipo'] ?? '')===$t)?'selected':'' ?>><?= ucfirst($t) ?></option>
                  <?php endforeach; ?>
                </select>
              </td>
              <td><input type="text" name="items[<?= $i ?>][documento_numero]" class="form-control form-control-sm" value="<?= e($it['documento_numero']) ?>"></td>
              <td><input type="number" name="items[<?= $i ?>][monto]" class="form-control form-control-sm text-end item-monto" step="0.01" min="0" required value="<?= e($it['monto']) ?>"></td>
              <td><button type="button" class="btn btn-sm btn-outline-danger item-del"><i class="bi bi-trash"></i></button></td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
        <tfoot>
          <tr class="table-light fw-bold"><td colspan="5" class="text-end">Total</td><td class="text-end" id="total-display">0,00</td><td></td></tr>
        </tfoot>
      </table>
      <button type="button" class="btn btn-sm btn-outline-primary" id="add-item"><i class="bi bi-plus"></i> Agregar gasto</button>
    </div>

    <div class="row g-3">
      <div class="col-12">
        <label class="form-label">Observaciones</label>
        <textarea name="observaciones" rows="2" class="form-control" maxlength="2000"><?= e($r['observaciones'] ?? '') ?></textarea>
      </div>
      <?php if (!$isEdit): ?>
        <div class="col-12">
          <label class="form-label">Adjuntar comprobantes (opcional)</label>
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
    <td><input type="date" name="items[__I__][fecha]" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>" required></td>
    <td>
      <select name="items[__I__][tipo_gasto_id]" class="form-select form-select-sm" required>
        <option value="">—</option>
        <?php foreach ($tipos_gasto as $tg): ?><option value="<?= (int)$tg['id'] ?>"><?= e($tg['codigo']) ?></option><?php endforeach; ?>
      </select>
    </td>
    <td><input type="text" name="items[__I__][descripcion]" class="form-control form-control-sm" required></td>
    <td>
      <select name="items[__I__][documento_tipo]" class="form-select form-select-sm">
        <option value="">—</option>
        <?php foreach (['boleta','factura','ticket','otro'] as $t): ?><option value="<?= $t ?>"><?= ucfirst($t) ?></option><?php endforeach; ?>
      </select>
    </td>
    <td><input type="text" name="items[__I__][documento_numero]" class="form-control form-control-sm"></td>
    <td><input type="number" name="items[__I__][monto]" class="form-control form-control-sm text-end item-monto" step="0.01" min="0" required></td>
    <td><button type="button" class="btn btn-sm btn-outline-danger item-del"><i class="bi bi-trash"></i></button></td>
  </tr>
</template>

<script>
(function () {
  var i = <?= max(1, count($items)) ?>;

  function recalc() {
    var total = 0;
    document.querySelectorAll('#items-body .item-monto').forEach(function (inp) {
      total += parseFloat(inp.value || 0);
    });
    document.getElementById('total-display').textContent = total.toLocaleString('es-CL', {minimumFractionDigits:2, maximumFractionDigits:2});
  }

  document.getElementById('add-item').addEventListener('click', function () {
    var html = document.getElementById('item-template').innerHTML.replace(/__I__/g, i++);
    document.getElementById('items-body').insertAdjacentHTML('beforeend', html);
  });
  document.addEventListener('input', function (e) {
    if (e.target.matches('.item-monto')) recalc();
  });
  document.addEventListener('click', function (e) {
    if (e.target.closest('.item-del')) { e.target.closest('tr').remove(); recalc(); }
  });

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
  filtrarCcs(); recalc();
  if (i === 1 && !document.querySelector('#items-body .item-row')) document.getElementById('add-item').click();
})();
</script>
