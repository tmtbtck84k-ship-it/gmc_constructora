<?php $cerrada = $cierre && $cierre['estado_codigo'] === 'cerrada'; ?>
<div class="d-flex align-items-center justify-content-between mb-3">
  <div>
    <h4 class="mb-0"><i class="bi bi-check2-square me-2"></i>Cierre de <?= e($proyecto['codigo']) ?></h4>
    <small class="text-muted"><?= e($proyecto['nombre']) ?> · <?= e($proyecto['cliente']) ?></small>
  </div>
  <div class="d-flex gap-2 flex-wrap">
    <?php if ($cierre): ?>
      <a class="btn btn-outline-success" href="<?= base_url("obras/cierre/{$proyecto['id']}/pdf") ?>"><i class="bi bi-file-earmark-pdf"></i> Descargar PDF</a>
    <?php endif; ?>
    <?php if (!$cerrada && can('obras.cierre.crear')): ?>
      <a class="btn btn-primary" href="<?= base_url("obras/cierre/{$proyecto['id']}/editar") ?>"><i class="bi bi-pencil"></i> <?= $cierre ? 'Editar borrador' : 'Crear borrador' ?></a>
    <?php endif; ?>
    <?php if ($cierre && !$cerrada && can('obras.cierre.cerrar')): ?>
      <a class="btn btn-success js-confirm" data-confirm="¿Cerrar la obra formalmente? Verifica que todas las SDPs estén pagadas o rechazadas." href="<?= base_url("obras/cierre/{$proyecto['id']}/cerrar") ?>"><i class="bi bi-check2-all"></i> Cerrar obra</a>
    <?php endif; ?>
  </div>
</div>

<?php if (!$cierre): ?>
  <div class="alert alert-info">No hay borrador de cierre para este proyecto. Crea uno para empezar.</div>
<?php else: ?>
  <div class="row g-3">
    <div class="col-md-8">
      <div class="card shadow-sm">
        <div class="card-body">
          <div class="row g-2 mb-3">
            <div class="col-md-3 text-muted small">Estado</div>
            <div class="col-md-9"><span class="badge bg-<?= e($cierre['estado_color']) ?>"><?= e($cierre['estado_nombre']) ?></span></div>
            <div class="col-md-3 text-muted small">Fecha término real</div>
            <div class="col-md-9"><?= e(format_date($cierre['fecha_termino_real'])) ?></div>
            <?php if ($cerrada): ?>
              <div class="col-md-3 text-muted small">Cerrada por</div>
              <div class="col-md-9"><?= e(trim(($cierre['cerrada_por_nombres']?:'') . ' ' . ($cierre['cerrada_por_apellidos']?:''))) ?> · <?= e(format_datetime($cierre['cerrada_at'])) ?></div>
            <?php endif; ?>
          </div>
          <h6 class="text-muted text-uppercase small">Resumen</h6>
          <p><?= nl2br(e($cierre['resumen'])) ?></p>
          <?php if ($cierre['conformidades']): ?>
            <h6 class="text-muted text-uppercase small">Conformidades</h6>
            <p><?= nl2br(e($cierre['conformidades'])) ?></p>
          <?php endif; ?>
          <?php if ($cierre['observaciones']): ?>
            <h6 class="text-muted text-uppercase small">Observaciones</h6>
            <p><?= nl2br(e($cierre['observaciones'])) ?></p>
          <?php endif; ?>
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
<?php endif; ?>
