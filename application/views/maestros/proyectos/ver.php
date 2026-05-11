<?php $p = $proyecto; ?>
<div class="d-flex align-items-center justify-content-between mb-3">
  <div>
    <h4 class="mb-0"><i class="bi bi-kanban me-2"></i><?= e($p['codigo']) ?> — <?= e($p['nombre']) ?></h4>
    <small class="text-muted"><?= e($p['cliente'] ?? '') ?></small>
  </div>
  <div class="d-flex gap-2">
    <a href="<?= base_url('maestros/proyectos') ?>" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Volver</a>
    <?php if (can('maestros.proyecto.editar')): ?>
      <a href="<?= base_url('maestros/proyectos/editar/' . (int)$p['id']) ?>" class="btn btn-primary"><i class="bi bi-pencil"></i> Editar</a>
    <?php endif; ?>
  </div>
</div>

<div class="row g-3">
  <div class="col-md-8">
    <div class="card shadow-sm">
      <div class="card-body">
        <div class="row g-2">
          <div class="col-md-4 text-muted small">Estado</div>
          <div class="col-md-8"><span class="badge bg-<?= e($p['estado_color'] ?? 'secondary') ?>"><?= e($p['estado_nombre']) ?></span></div>
          <div class="col-md-4 text-muted small">Dirección</div>
          <div class="col-md-8"><?= e($p['direccion'] ?? '—') ?></div>
          <div class="col-md-4 text-muted small">Moneda base</div>
          <div class="col-md-8"><?= e($p['moneda_codigo'] ?? '—') ?> <?php if ($p['valor_uf_referencia']): ?><span class="text-muted">· UF ref: <?= e(format_uf($p['valor_uf_referencia'])) ?></span><?php endif; ?></div>
          <div class="col-md-4 text-muted small">Fecha inicio</div>
          <div class="col-md-8"><?= e(format_date($p['fecha_inicio'])) ?: '—' ?></div>
          <div class="col-md-4 text-muted small">Fecha término estimada</div>
          <div class="col-md-8"><?= e(format_date($p['fecha_termino_estimada'])) ?: '—' ?></div>
          <?php if ($p['fecha_termino_real']): ?>
            <div class="col-md-4 text-muted small">Fecha término real</div>
            <div class="col-md-8"><?= e(format_date($p['fecha_termino_real'])) ?></div>
          <?php endif; ?>
          <?php if ($p['observaciones']): ?>
            <div class="col-md-4 text-muted small">Observaciones</div>
            <div class="col-md-8"><?= nl2br(e($p['observaciones'])) ?></div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <div class="col-md-4">
    <div class="card shadow-sm">
      <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
        <span><i class="bi bi-tag me-1"></i>Centros de Costo</span>
        <?php if (can('maestros.cc.crear')): ?>
          <a class="btn btn-sm btn-outline-primary" href="<?= base_url('maestros/centros-costo?proyecto_id=' . (int)$p['id']) ?>"><i class="bi bi-plus-lg"></i></a>
        <?php endif; ?>
      </div>
      <ul class="list-group list-group-flush">
        <?php foreach ($centros_costo as $cc): ?>
          <li class="list-group-item d-flex justify-content-between align-items-center">
            <span>
              <code class="me-2"><?= e($cc['codigo']) ?></code>
              <?= e($cc['nombre']) ?>
              <?php if ((int)$cc['es_administracion']): ?>
                <span class="badge bg-info ms-1">ADM</span>
              <?php endif; ?>
            </span>
            <?= (int)$cc['activo']===1 ? '<i class="bi bi-check-circle-fill text-success"></i>' : '<i class="bi bi-x-circle text-muted"></i>' ?>
          </li>
        <?php endforeach; ?>
        <?php if (!$centros_costo): ?>
          <li class="list-group-item text-center text-muted small py-3">Sin CC creados</li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</div>
