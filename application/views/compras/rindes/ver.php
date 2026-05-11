<?php $r = $rinde; $est = $r['estado_codigo']; ?>
<div class="d-flex align-items-center justify-content-between mb-3">
  <div>
    <h4 class="mb-0"><i class="bi bi-receipt me-2"></i><?= e($r['numero']) ?></h4>
    <small class="text-muted">
      <?= e(trim(($r['usuario_nombres']?:'') . ' ' . ($r['usuario_apellidos']?:''))) ?> ·
      <?= e($r['proyecto_codigo'] ?: 'Administración') ?> ·
      <span class="badge bg-<?= e($r['estado_color']) ?>"><?= e($r['estado_nombre']) ?></span>
    </small>
  </div>
  <div class="d-flex gap-2 flex-wrap">
    <a class="btn btn-outline-secondary" href="<?= base_url('compras/rindes') ?>"><i class="bi bi-arrow-left"></i> Volver</a>
    <?php if ($est === 'borrador'): ?>
      <?php if (can('compras.rinde.editar')): ?>
        <a class="btn btn-outline-primary" href="<?= base_url("compras/rindes/{$r['id']}/editar") ?>"><i class="bi bi-pencil"></i> Editar</a>
      <?php endif; ?>
      <?php if (can('compras.rinde.enviar')): ?>
        <a class="btn btn-warning js-confirm" data-confirm="¿Enviar este rinde para aprobación?" href="<?= base_url("compras/rindes/{$r['id']}/enviar") ?>"><i class="bi bi-send"></i> Enviar</a>
      <?php endif; ?>
    <?php endif; ?>
    <?php if ($puede_aprobar): ?>
      <a class="btn btn-success js-confirm" data-confirm="¿Aprobar este rinde?" href="<?= base_url("compras/rindes/{$r['id']}/aprobar") ?>"><i class="bi bi-check2-circle"></i> Aprobar</a>
      <a class="btn btn-outline-danger" href="<?= base_url("compras/rindes/{$r['id']}/rechazar") ?>"><i class="bi bi-x-circle"></i> Rechazar</a>
    <?php endif; ?>
    <?php if ($est === 'aprobada' && !$r['solicitud_pago_id'] && can('compras.rinde.aprobar')): ?>
      <a class="btn btn-primary" href="<?= base_url("compras/rindes/{$r['id']}/generar-sdp") ?>"><i class="bi bi-cash-coin"></i> Generar SDP de pago</a>
    <?php endif; ?>
  </div>
</div>

<div class="row g-3">
  <div class="col-md-8">
    <div class="card shadow-sm mb-3">
      <div class="card-header bg-white fw-semibold">Datos generales</div>
      <div class="card-body">
        <div class="row g-2">
          <div class="col-md-4 text-muted small">Proyecto</div><div class="col-md-8"><?= $r['proyecto_codigo'] ? '<code>' . e($r['proyecto_codigo']) . '</code> · ' . e($r['proyecto_nombre']) : '<span class="text-muted">Administración</span>' ?></div>
          <div class="col-md-4 text-muted small">Centro de costo</div><div class="col-md-8"><code><?= e($r['cc_codigo']) ?></code> · <?= e($r['cc_nombre']) ?></div>
          <div class="col-md-4 text-muted small">Solicitante</div><div class="col-md-8"><?= e(trim(($r['usuario_nombres']?:'') . ' ' . ($r['usuario_apellidos']?:''))) ?> <span class="text-muted small">· <?= e($r['usuario_email']) ?></span></div>
          <div class="col-md-4 text-muted small">Fecha rendición</div><div class="col-md-8"><?= e(format_date($r['fecha_rendicion'])) ?></div>
          <?php if ($r['observaciones']): ?>
            <div class="col-md-4 text-muted small">Observaciones</div><div class="col-md-8"><?= nl2br(e($r['observaciones'])) ?></div>
          <?php endif; ?>
          <?php if ($r['motivo_rechazo']): ?>
            <div class="col-md-4 text-muted small">Motivo rechazo</div><div class="col-md-8 text-danger"><?= nl2br(e($r['motivo_rechazo'])) ?></div>
          <?php endif; ?>
          <?php if ($r['sdp_numero']): ?>
            <div class="col-md-4 text-muted small">SDP de pago</div><div class="col-md-8"><a href="<?= base_url('finanzas/sdp/' . (int)$r['solicitud_pago_id']) ?>"><?= e($r['sdp_numero']) ?></a></div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div class="card shadow-sm">
      <div class="card-header bg-white fw-semibold">Ítems</div>
      <div class="table-responsive">
        <table class="table table-sm mb-0">
          <thead class="table-light"><tr><th>Fecha</th><th>Tipo</th><th>Descripción</th><th>Doc</th><th class="text-end">Monto</th></tr></thead>
          <tbody>
            <?php foreach ($items as $it): ?>
              <tr>
                <td class="small"><?= e(format_date($it['fecha'])) ?></td>
                <td class="small"><?= e($it['tg_codigo']) ?></td>
                <td><?= e($it['descripcion']) ?></td>
                <td class="small"><?= e($it['documento_tipo']) ?> <?= e($it['documento_numero']) ?></td>
                <td class="text-end small"><?= e($r['moneda_simbolo']) ?> <?= number_format((float)$it['monto'], (int)$r['moneda_decimales'], ',', '.') ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
          <tfoot>
            <tr class="table-light fw-bold"><th colspan="4" class="text-end">Total</th><th class="text-end"><?= e($r['moneda_simbolo']) ?> <?= number_format((float)$r['monto_total'], (int)$r['moneda_decimales'], ',', '.') ?></th></tr>
            <?php if ($r['moneda'] !== 'CLP' && $r['monto_total_clp']): ?>
              <tr><th colspan="4" class="text-end text-primary">Equivalente CLP (TC <?= number_format((float)$r['tipo_cambio_clp'], 4, ',', '.') ?>)</th><th class="text-end text-primary fw-bold">$ <?= number_format((float)$r['monto_total_clp'], 0, ',', '.') ?></th></tr>
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
