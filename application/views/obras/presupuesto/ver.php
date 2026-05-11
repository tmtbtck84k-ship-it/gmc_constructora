<div class="d-flex align-items-center justify-content-between mb-3">
  <div>
    <h4 class="mb-0"><i class="bi bi-calculator me-2"></i>Presupuesto v<?= (int)$p['version'] ?> · <?= e($p['proyecto_codigo']) ?></h4>
    <small class="text-muted"><?= e($p['proyecto_nombre']) ?> · <?= (int)$p['vigente']===1 ? '<span class="badge bg-success">Vigente</span>' : '<span class="badge bg-secondary">Anterior</span>' ?></small>
  </div>
  <div class="d-flex gap-2">
    <a class="btn btn-outline-secondary" href="<?= base_url('obras/presupuesto?proyecto_id=' . (int)$p['proyecto_id']) ?>"><i class="bi bi-arrow-left"></i> Volver</a>
    <?php if ((int)$p['vigente']===1 && can('obras.presupuesto.editar')): ?>
      <a class="btn btn-primary" href="<?= base_url('obras/presupuesto/' . (int)$p['id'] . '/editar') ?>"><i class="bi bi-pencil"></i> Editar</a>
    <?php endif; ?>
  </div>
</div>

<div class="card shadow-sm">
  <div class="table-responsive">
    <table class="table table-sm mb-0">
      <thead class="table-light"><tr><th>Centro de costo</th><th>Tipo de gasto</th><th>Descripción</th><th class="text-end">Monto</th></tr></thead>
      <tbody>
        <?php foreach ($items as $it): ?>
          <tr>
            <td><code><?= e($it['cc_codigo']) ?></code> · <?= e($it['cc_nombre']) ?></td>
            <td><code><?= e($it['tg_codigo']) ?></code></td>
            <td><?= e($it['descripcion']) ?></td>
            <td class="text-end"><?= e($p['moneda_simbolo']) ?> <?= number_format((float)$it['monto'], (int)$p['moneda_decimales'], ',', '.') ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
      <tfoot>
        <tr class="table-light fw-bold"><th colspan="3" class="text-end">Total</th><th class="text-end"><?= e($p['moneda_simbolo']) ?> <?= number_format((float)$p['monto_total'], (int)$p['moneda_decimales'], ',', '.') ?></th></tr>
      </tfoot>
    </table>
  </div>
</div>
