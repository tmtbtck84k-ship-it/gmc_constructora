<?php $c = $compra; $est = $c['estado_codigo']; ?>
<div class="d-flex align-items-center justify-content-between mb-3">
  <div>
    <h4 class="mb-0"><i class="bi bi-cart me-2"></i><?= e($c['numero']) ?></h4>
    <small class="text-muted">
      <?= e($c['proveedor']) ?> · <span class="badge bg-<?= e($c['estado_color']) ?>"><?= e($c['estado_nombre']) ?></span>
    </small>
  </div>
  <div class="d-flex gap-2 flex-wrap">
    <a class="btn btn-outline-secondary" href="<?= base_url('compras/compras') ?>"><i class="bi bi-arrow-left"></i> Volver</a>
    <?php if ($est === 'borrador'): ?>
      <?php if (can('compras.compra.editar')): ?>
        <a class="btn btn-outline-primary" href="<?= base_url("compras/compras/{$c['id']}/editar") ?>"><i class="bi bi-pencil"></i> Editar</a>
        <a class="btn btn-success js-confirm" data-confirm="¿Confirmar recepción de esta compra?" href="<?= base_url("compras/compras/{$c['id']}/confirmar") ?>"><i class="bi bi-check2-circle"></i> Confirmar recepción</a>
      <?php endif; ?>
    <?php endif; ?>
    <?php if ($est !== 'anulada' && can('compras.compra.anular')): ?>
      <a class="btn btn-outline-danger" href="<?= base_url("compras/compras/{$c['id']}/anular") ?>"><i class="bi bi-x-circle"></i> Anular</a>
    <?php endif; ?>
  </div>
</div>

<div class="row g-3">
  <div class="col-md-8">
    <div class="card shadow-sm mb-3">
      <div class="card-header bg-white fw-semibold">Datos generales</div>
      <div class="card-body">
        <div class="row g-2">
          <div class="col-md-4 text-muted small">Proyecto</div><div class="col-md-8"><?= $c['proyecto_codigo'] ? '<code>' . e($c['proyecto_codigo']) . '</code> · ' . e($c['proyecto_nombre']) : '<span class="text-muted">Administración</span>' ?></div>
          <div class="col-md-4 text-muted small">Centro de costo</div><div class="col-md-8"><code><?= e($c['cc_codigo']) ?></code> · <?= e($c['cc_nombre']) ?></div>
          <div class="col-md-4 text-muted small">Proveedor</div><div class="col-md-8"><?= e(formatear_rut($c['proveedor_rut'])) ?> · <?= e($c['proveedor']) ?></div>
          <div class="col-md-4 text-muted small">Documento</div><div class="col-md-8"><?= e(ucfirst((string)$c['documento_tipo'])) ?> <?= e($c['documento_numero']) ?></div>
          <div class="col-md-4 text-muted small">Fecha recepción</div><div class="col-md-8"><?= e(format_date($c['fecha_recepcion'])) ?></div>
          <?php if ($c['observaciones']): ?>
            <div class="col-md-4 text-muted small">Observaciones</div><div class="col-md-8"><?= nl2br(e($c['observaciones'])) ?></div>
          <?php endif; ?>
          <?php if ($c['sdp_numero']): ?>
            <div class="col-md-4 text-muted small">SDP vinculada</div><div class="col-md-8"><a href="<?= base_url('finanzas/sdp/' . (int)$c['solicitud_pago_id']) ?>"><?= e($c['sdp_numero']) ?></a></div>
          <?php endif; ?>
          <?php if ($c['rinde_numero']): ?>
            <div class="col-md-4 text-muted small">Rinde vinculado</div><div class="col-md-8"><a href="<?= base_url('compras/rindes/' . (int)$c['rinde_id']) ?>"><?= e($c['rinde_numero']) ?></a></div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div class="card shadow-sm">
      <div class="card-header bg-white fw-semibold">Ítems</div>
      <div class="table-responsive">
        <table class="table table-sm mb-0">
          <thead class="table-light">
            <tr><th>Descripción</th><th>Tipo gasto</th><th class="text-end">Cant.</th><th>Unid.</th><th class="text-end">Precio</th><th class="text-end">Total</th></tr>
          </thead>
          <tbody>
            <?php foreach ($items as $it): ?>
              <tr>
                <td><?= e($it['descripcion']) ?></td>
                <td class="small"><?= e($it['tg_codigo'] ?: '—') ?></td>
                <td class="text-end small"><?= number_format((float)$it['cantidad'], 3, ',', '.') ?></td>
                <td class="small"><?= e($it['unidad']) ?></td>
                <td class="text-end small"><?= e($c['moneda_simbolo']) ?> <?= number_format((float)$it['precio_unitario'], (int)$c['moneda_decimales'], ',', '.') ?></td>
                <td class="text-end small fw-semibold"><?= e($c['moneda_simbolo']) ?> <?= number_format((float)$it['total_linea'], (int)$c['moneda_decimales'], ',', '.') ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
          <tfoot>
            <tr class="table-light"><th colspan="5" class="text-end">Neto</th><th class="text-end"><?= e($c['moneda_simbolo']) ?> <?= number_format((float)$c['monto_neto'], (int)$c['moneda_decimales'], ',', '.') ?></th></tr>
            <tr class="table-light"><th colspan="5" class="text-end">IVA</th><th class="text-end"><?= e($c['moneda_simbolo']) ?> <?= number_format((float)$c['monto_iva'], (int)$c['moneda_decimales'], ',', '.') ?></th></tr>
            <tr class="table-light fw-bold"><th colspan="5" class="text-end">Total</th><th class="text-end"><?= e($c['moneda_simbolo']) ?> <?= number_format((float)$c['monto_total'], (int)$c['moneda_decimales'], ',', '.') ?></th></tr>
            <?php if ($c['moneda'] !== 'CLP' && $c['monto_total_clp']): ?>
              <tr><th colspan="5" class="text-end text-primary">Equivalente CLP (TC <?= number_format((float)$c['tipo_cambio_clp'], 4, ',', '.') ?>)</th><th class="text-end text-primary fw-bold">$ <?= number_format((float)$c['monto_total_clp'], 0, ',', '.') ?></th></tr>
            <?php endif; ?>
          </tfoot>
        </table>
      </div>
    </div>
  </div>

  <div class="col-md-4">
    <div class="card shadow-sm">
      <div class="card-header bg-white fw-semibold"><i class="bi bi-paperclip me-1"></i>Adjuntos (<?= count($adjuntos) ?>)</div>
      <ul class="list-group list-group-flush">
        <?php foreach ($adjuntos as $a): ?>
          <li class="list-group-item small"><a href="<?= base_url('adjuntos/' . (int)$a['id'] . '/descargar') ?>"><?= e($a['nombre_original']) ?></a></li>
        <?php endforeach; ?>
        <?php if (!$adjuntos): ?><li class="list-group-item text-center text-muted small">Sin adjuntos</li><?php endif; ?>
      </ul>
    </div>
  </div>
</div>
