<?php $s = $sdp; $est = $s['estado_codigo']; $isFinal = (int)$s['estado_es_final'] === 1; ?>
<div class="d-flex align-items-center justify-content-between mb-3">
  <div>
    <h4 class="mb-0"><i class="bi bi-cash-coin me-2"></i><?= e($s['numero']) ?></h4>
    <small class="text-muted">
      <?= e($s['proyecto_codigo'] ?: 'Administración general') ?> ·
      <?= e($s['proveedor']) ?> ·
      <span class="badge bg-<?= e($s['estado_color']) ?>"><?= e($s['estado_nombre']) ?></span>
    </small>
  </div>
  <div class="d-flex gap-2 flex-wrap">
    <a class="btn btn-outline-secondary" href="<?= base_url('finanzas/sdp') ?>"><i class="bi bi-arrow-left"></i> Volver</a>

    <?php if ($est === 'pendiente'): ?>
      <?php if (can('finanzas.sdp.editar')): ?>
        <a class="btn btn-outline-primary" href="<?= base_url("finanzas/sdp/{$s['id']}/editar") ?>"><i class="bi bi-pencil"></i> Editar</a>
      <?php endif; ?>
      <?php if (can('finanzas.sdp.validar')): ?>
        <a class="btn btn-info text-white js-confirm" data-confirm="¿Validar esta SDP?" href="<?= base_url("finanzas/sdp/{$s['id']}/validar") ?>"><i class="bi bi-check2-circle"></i> Validar</a>
      <?php endif; ?>
    <?php endif; ?>

    <?php if ($est === 'validada' && can('finanzas.sdp.programar')): ?>
      <a class="btn btn-primary" href="<?= base_url("finanzas/sdp/{$s['id']}/programar") ?>"><i class="bi bi-calendar-event"></i> Programar</a>
    <?php endif; ?>

    <?php if ($est === 'programada' && can('finanzas.sdp.pagar')): ?>
      <a class="btn btn-success" href="<?= base_url("finanzas/sdp/{$s['id']}/pagar") ?>"><i class="bi bi-check2-all"></i> Marcar pagada</a>
    <?php endif; ?>

    <?php if (!$isFinal && can('finanzas.sdp.rechazar')): ?>
      <a class="btn btn-outline-danger" href="<?= base_url("finanzas/sdp/{$s['id']}/rechazar") ?>"><i class="bi bi-x-circle"></i> Rechazar</a>
    <?php endif; ?>

    <?php if (in_array($est, ['pendiente','rechazada'], true) && can('finanzas.sdp.eliminar')): ?>
      <a class="btn btn-outline-danger js-confirm" data-confirm="¿Eliminar esta SDP? (soft delete)" href="<?= base_url("finanzas/sdp/{$s['id']}/eliminar") ?>"><i class="bi bi-trash"></i></a>
    <?php endif; ?>
  </div>
</div>

<div class="row g-3">
  <div class="col-md-8">
    <div class="card shadow-sm mb-3">
      <div class="card-header bg-white fw-semibold">Datos de la SDP</div>
      <div class="card-body">
        <div class="row g-2">
          <div class="col-md-4 text-muted small">Proyecto</div>
          <div class="col-md-8"><?= $s['proyecto_codigo'] ? '<code>' . e($s['proyecto_codigo']) . '</code> · ' . e($s['proyecto_nombre']) : '<span class="text-muted">Administración general</span>' ?></div>

          <div class="col-md-4 text-muted small">Centro de costo</div>
          <div class="col-md-8"><code><?= e($s['cc_codigo']) ?></code> · <?= e($s['cc_nombre']) ?> <?= (int)$s['cc_es_admin']===1?'<span class="badge bg-info ms-1">ADM</span>':'' ?></div>

          <div class="col-md-4 text-muted small">Proveedor</div>
          <div class="col-md-8"><?= e(formatear_rut($s['proveedor_rut'])) ?> · <?= e($s['proveedor']) ?></div>

          <div class="col-md-4 text-muted small">Tipo de gasto</div>
          <div class="col-md-8"><code><?= e($s['tg_codigo']) ?></code> · <?= e($s['tg_nombre']) ?></div>

          <div class="col-md-4 text-muted small">Documento</div>
          <div class="col-md-8"><?= e(ucfirst((string)$s['documento_tipo'])) ?> <?= e($s['documento_numero']) ?></div>

          <div class="col-md-4 text-muted small">Fecha emisión</div>
          <div class="col-md-8"><?= e(format_date($s['fecha_emision'])) ?></div>

          <?php if ($s['fecha_vencimiento']): ?>
            <div class="col-md-4 text-muted small">Vencimiento</div>
            <div class="col-md-8"><?= e(format_date($s['fecha_vencimiento'])) ?></div>
          <?php endif; ?>

          <?php if ($s['fecha_programada']): ?>
            <div class="col-md-4 text-muted small">Programada</div>
            <div class="col-md-8"><?= e(format_date($s['fecha_programada'])) ?> <?php if ($s['forma_pago']): ?><span class="text-muted small">· <?= e($s['forma_pago']) ?></span><?php endif; ?></div>
          <?php endif; ?>

          <?php if ($s['fecha_pago']): ?>
            <div class="col-md-4 text-muted small">Pagada</div>
            <div class="col-md-8"><?= e(format_date($s['fecha_pago'])) ?></div>
          <?php endif; ?>

          <?php if ($s['descripcion']): ?>
            <div class="col-md-4 text-muted small">Descripción</div>
            <div class="col-md-8"><?= nl2br(e($s['descripcion'])) ?></div>
          <?php endif; ?>

          <?php if ($s['motivo_rechazo']): ?>
            <div class="col-md-4 text-muted small">Motivo rechazo</div>
            <div class="col-md-8"><span class="text-danger"><?= nl2br(e($s['motivo_rechazo'])) ?></span></div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div class="card shadow-sm">
      <div class="card-header bg-white fw-semibold">Montos</div>
      <div class="card-body">
        <div class="row g-2">
          <div class="col-md-4 text-muted small">Moneda</div>
          <div class="col-md-8"><strong><?= e($s['moneda']) ?></strong></div>

          <div class="col-md-4 text-muted small">Neto</div>
          <div class="col-md-8"><?= e($s['moneda_simbolo']) ?> <?= number_format((float)$s['monto_neto'], (int)$s['moneda_decimales'], ',', '.') ?></div>

          <div class="col-md-4 text-muted small">IVA</div>
          <div class="col-md-8"><?= e($s['moneda_simbolo']) ?> <?= number_format((float)$s['monto_iva'], (int)$s['moneda_decimales'], ',', '.') ?></div>

          <div class="col-md-4 text-muted small fw-bold">Total</div>
          <div class="col-md-8 fw-bold"><?= e($s['moneda_simbolo']) ?> <?= number_format((float)$s['monto_total'], (int)$s['moneda_decimales'], ',', '.') ?></div>

          <?php if ($s['moneda'] !== 'CLP' && $s['monto_total_clp'] !== null): ?>
            <div class="col-md-4 text-muted small">TC aplicado</div>
            <div class="col-md-8">$ <?= number_format((float)$s['tipo_cambio_clp'], 4, ',', '.') ?> CLP por 1 <?= e($s['moneda']) ?> <span class="text-muted small">(snapshot al crear)</span></div>

            <div class="col-md-4 text-muted small fw-bold">Equivalente CLP</div>
            <div class="col-md-8 fw-bold text-primary">$ <?= number_format((float)$s['monto_total_clp'], 0, ',', '.') ?></div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <div class="col-md-4">
    <div class="card shadow-sm mb-3">
      <div class="card-header bg-white fw-semibold"><i class="bi bi-clock-history me-1"></i>Línea de tiempo</div>
      <div class="card-body">
        <?php foreach ($timeline as $t): ?>
          <div class="d-flex mb-3">
            <div class="me-2">
              <span class="badge bg-<?= e($t['estado_nuevo_color'] ?: 'secondary') ?>"><?= e($t['estado_nuevo_nombre']) ?></span>
            </div>
            <div class="flex-grow-1 small">
              <div class="fw-semibold"><?= e(trim(($t['nombres'] ?? '') . ' ' . ($t['apellidos'] ?? '')) ?: 'Sistema') ?></div>
              <div class="text-muted"><?= e(format_datetime($t['created_at'])) ?></div>
              <?php if ($t['comentario']): ?>
                <div class="mt-1"><?= nl2br(e($t['comentario'])) ?></div>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="card shadow-sm">
      <div class="card-header bg-white fw-semibold d-flex justify-content-between">
        <span><i class="bi bi-paperclip me-1"></i>Adjuntos (<?= count($adjuntos) ?>)</span>
      </div>
      <ul class="list-group list-group-flush">
        <?php foreach ($adjuntos as $a): ?>
          <li class="list-group-item d-flex justify-content-between align-items-center small">
            <span>
              <i class="bi bi-file-earmark me-1"></i>
              <a href="<?= base_url('adjuntos/' . (int)$a['id'] . '/descargar') ?>"><?= e($a['nombre_original']) ?></a>
              <?php if ($a['categoria']): ?><span class="badge bg-light text-dark ms-1"><?= e($a['categoria']) ?></span><?php endif; ?>
            </span>
            <span class="text-muted"><?= e(round($a['tamano_bytes']/1024)) ?> KB</span>
          </li>
        <?php endforeach; ?>
        <?php if (!$adjuntos): ?>
          <li class="list-group-item text-center text-muted small py-3">Sin adjuntos</li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</div>
