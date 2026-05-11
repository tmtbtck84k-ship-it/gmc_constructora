<?php $isEdit = !empty($is_edit); ?>
<div class="d-flex align-items-center justify-content-between mb-3">
  <h4 class="mb-0">
    <i class="bi bi-<?= $isEdit?'pencil':'plus-lg' ?> me-2"></i>
    <?= $isEdit ? 'Editar presupuesto v' . (int)$p['version'] : 'Nuevo presupuesto' ?> ·
    <small class="text-muted"><?= e($proyecto['codigo']) ?> · <?= e($proyecto['nombre']) ?></small>
  </h4>
  <a class="btn btn-outline-secondary" href="<?= base_url("obras/presupuesto?proyecto_id={$proyecto['id']}") ?>"><i class="bi bi-arrow-left me-1"></i>Volver</a>
</div>

<form method="post"
      action="<?= $isEdit ? base_url("obras/presupuesto/{$p['id']}/editar") : base_url('obras/presupuesto/crear') ?>"
      class="card shadow-sm">
  <?= form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()) ?>
  <input type="hidden" name="proyecto_id" value="<?= (int)$proyecto['id'] ?>">
  <div class="card-body">
    <div class="row g-3 mb-3">
      <div class="col-md-3">
        <label class="form-label">Moneda</label>
        <select name="moneda_id" class="form-select">
          <?php foreach ($monedas as $m):
            $sel = $p ? ((int)$p['moneda_id']===(int)$m['id']?'selected':'') : ($m['codigo']==='CLP'?'selected':''); ?>
            <option value="<?= (int)$m['id'] ?>" <?= $sel ?>><?= e($m['codigo']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <h6 class="text-muted text-uppercase small">Líneas presupuestarias</h6>
    <div class="table-responsive mb-3">
      <table class="table table-sm" id="items-table">
        <thead class="table-light">
          <tr>
            <th style="width:25%">Centro de costo</th>
            <th style="width:20%">Tipo de gasto</th>
            <th>Descripción</th>
            <th class="text-end" style="width:15%">Monto</th>
            <th style="width:5%"></th>
          </tr>
        </thead>
        <tbody id="items-body">
          <?php if ($items): foreach ($items as $i => $it): ?>
            <tr class="item-row">
              <td>
                <select name="items[<?= $i ?>][centro_costo_id]" class="form-select form-select-sm" required>
                  <option value="">—</option>
                  <?php foreach ($centros as $cc): ?>
                    <option value="<?= (int)$cc['id'] ?>" <?= ((int)($it['centro_costo_id'] ?? 0)===(int)$cc['id'])?'selected':'' ?>><?= e($cc['codigo'] . ' · ' . $cc['nombre']) ?></option>
                  <?php endforeach; ?>
                </select>
              </td>
              <td>
                <select name="items[<?= $i ?>][tipo_gasto_id]" class="form-select form-select-sm" required>
                  <option value="">—</option>
                  <?php foreach ($tipos_gasto as $tg): ?>
                    <option value="<?= (int)$tg['id'] ?>" <?= ((int)($it['tipo_gasto_id'] ?? 0)===(int)$tg['id'])?'selected':'' ?>><?= e($tg['codigo']) ?></option>
                  <?php endforeach; ?>
                </select>
              </td>
              <td><input type="text" name="items[<?= $i ?>][descripcion]" class="form-control form-control-sm" required value="<?= e($it['descripcion']) ?>"></td>
              <td><input type="number" name="items[<?= $i ?>][monto]" class="form-control form-control-sm text-end item-monto" step="0.01" min="0" required value="<?= e($it['monto']) ?>"></td>
              <td><button type="button" class="btn btn-sm btn-outline-danger item-del"><i class="bi bi-trash"></i></button></td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
        <tfoot>
          <tr class="table-light fw-bold"><td colspan="3" class="text-end">Monto total</td><td class="text-end" id="total-display">0,00</td><td></td></tr>
        </tfoot>
      </table>
      <button type="button" class="btn btn-sm btn-outline-primary" id="add-item"><i class="bi bi-plus"></i> Agregar línea</button>
    </div>
  </div>
  <div class="card-footer text-end">
    <button class="btn btn-primary"><i class="bi bi-check2 me-1"></i><?= $isEdit ? 'Guardar' : 'Crear y marcar vigente' ?></button>
  </div>
</form>

<template id="item-template">
  <tr class="item-row">
    <td>
      <select name="items[__I__][centro_costo_id]" class="form-select form-select-sm" required>
        <option value="">—</option>
        <?php foreach ($centros as $cc): ?><option value="<?= (int)$cc['id'] ?>"><?= e($cc['codigo']) ?></option><?php endforeach; ?>
      </select>
    </td>
    <td>
      <select name="items[__I__][tipo_gasto_id]" class="form-select form-select-sm" required>
        <option value="">—</option>
        <?php foreach ($tipos_gasto as $tg): ?><option value="<?= (int)$tg['id'] ?>"><?= e($tg['codigo']) ?></option><?php endforeach; ?>
      </select>
    </td>
    <td><input type="text" name="items[__I__][descripcion]" class="form-control form-control-sm" required></td>
    <td><input type="number" name="items[__I__][monto]" class="form-control form-control-sm text-end item-monto" step="0.01" min="0" required></td>
    <td><button type="button" class="btn btn-sm btn-outline-danger item-del"><i class="bi bi-trash"></i></button></td>
  </tr>
</template>

<script>
(function () {
  var i = <?= max(1, count($items)) ?>;
  function recalc() {
    var t = 0;
    document.querySelectorAll('#items-body .item-monto').forEach(function (inp) { t += parseFloat(inp.value || 0); });
    document.getElementById('total-display').textContent = t.toLocaleString('es-CL', {minimumFractionDigits:2});
  }
  document.getElementById('add-item').addEventListener('click', function () {
    document.getElementById('items-body').insertAdjacentHTML('beforeend',
      document.getElementById('item-template').innerHTML.replace(/__I__/g, i++));
  });
  document.addEventListener('input', function (e) { if (e.target.matches('.item-monto')) recalc(); });
  document.addEventListener('click', function (e) {
    if (e.target.closest('.item-del')) { e.target.closest('tr').remove(); recalc(); }
  });
  recalc();
  if (i === 1 && !document.querySelector('#items-body .item-row')) document.getElementById('add-item').click();
})();
</script>
