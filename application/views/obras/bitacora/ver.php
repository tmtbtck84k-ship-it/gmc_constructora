<?php
$colorPorTipo = ['avance'=>'success','observacion'=>'info','incidencia'=>'danger','otro'=>'secondary'];
$color = $colorPorTipo[$b['tipo_evento']] ?? 'secondary';
?>
<div class="d-flex align-items-center justify-content-between mb-3">
  <div>
    <h4 class="mb-0"><i class="bi bi-journal-text me-2"></i><?= e($b['titulo']) ?></h4>
    <small class="text-muted">
      <code><?= e($b['numero']) ?></code> ·
      <?= e($b['proyecto_codigo']) ?> · <?= e($b['proyecto_nombre']) ?> ·
      <span class="badge bg-<?= $color ?>"><?= e(ucfirst($b['tipo_evento'])) ?></span>
    </small>
  </div>
  <div class="d-flex gap-2">
    <a class="btn btn-outline-secondary" href="<?= base_url('obras/bitacora?proyecto_id=' . (int)$b['proyecto_id']) ?>"><i class="bi bi-arrow-left"></i> Volver</a>
    <?php if ($puede_editar): ?>
      <a class="btn btn-outline-primary" href="<?= base_url("obras/bitacora/{$b['id']}/editar") ?>"><i class="bi bi-pencil"></i> Editar</a>
    <?php endif; ?>
  </div>
</div>

<div class="row g-3">
  <div class="col-md-8">
    <div class="card shadow-sm">
      <div class="card-body">
        <div class="row g-2 mb-3">
          <div class="col-md-3 text-muted small">Fecha del evento</div><div class="col-md-9"><?= e(format_date($b['fecha_evento'])) ?></div>
          <div class="col-md-3 text-muted small">Autor</div><div class="col-md-9"><?= e(trim(($b['autor_nombres']?:'') . ' ' . ($b['autor_apellidos']?:''))) ?></div>
          <div class="col-md-3 text-muted small">Registrada</div><div class="col-md-9"><?= e(format_datetime($b['created_at'])) ?></div>
        </div>
        <div class="border-top pt-3"><?= nl2br(e($b['detalle'])) ?></div>
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
