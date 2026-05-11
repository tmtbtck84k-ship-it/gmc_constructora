<div class="d-flex align-items-center justify-content-between mb-3">
  <div>
    <h4 class="mb-0"><i class="bi bi-journal-text me-2"></i>Bitácora de obra</h4>
    <?php if ($proyecto): ?><small class="text-muted"><?= e($proyecto['codigo']) ?> · <?= e($proyecto['nombre']) ?> · <?= count($rows) ?> entradas</small><?php endif; ?>
  </div>
  <?php if ($proyecto && can('obras.bitacora.crear')): ?>
    <a class="btn btn-primary" href="<?= base_url('obras/bitacora/crear?proyecto_id=' . (int)$proyecto['id']) ?>"><i class="bi bi-plus-lg me-1"></i> Nueva entrada</a>
  <?php endif; ?>
</div>

<form method="get" class="card card-body shadow-sm mb-3">
  <div class="row g-2">
    <div class="col-md-4">
      <label class="form-label small text-muted">Proyecto *</label>
      <select name="proyecto_id" class="form-select select2" onchange="this.form.submit()" required>
        <option value="">— Seleccionar —</option>
        <?php foreach ($proyectos as $p): ?>
          <option value="<?= (int)$p['id'] ?>" <?= ($proyecto && (int)$proyecto['id']===(int)$p['id'])?'selected':'' ?>>
            <?= e($p['codigo'] . ' · ' . $p['nombre']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <?php if ($proyecto): ?>
      <div class="col-md-2">
        <label class="form-label small text-muted">Tipo</label>
        <select name="tipo" class="form-select">
          <option value="">Todos</option>
          <?php foreach (['avance','observacion','incidencia','otro'] as $t): ?>
            <option value="<?= $t ?>" <?= ($filters['tipo']===$t)?'selected':'' ?>><?= ucfirst($t) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2"><label class="form-label small text-muted">Desde</label><input type="date" name="desde" class="form-control" value="<?= e($filters['desde']) ?>"></div>
      <div class="col-md-2"><label class="form-label small text-muted">Hasta</label><input type="date" name="hasta" class="form-control" value="<?= e($filters['hasta']) ?>"></div>
      <div class="col-md-2 d-flex align-items-end"><button class="btn btn-outline-primary w-100"><i class="bi bi-search"></i></button></div>
    <?php endif; ?>
  </div>
</form>

<?php if ($proyecto): ?>
  <?php if (!$rows): ?>
    <div class="alert alert-info text-center py-4">Sin entradas de bitácora para este proyecto.</div>
  <?php else: ?>
    <div class="row g-3">
      <?php foreach ($rows as $r):
        $colorPorTipo = ['avance'=>'success','observacion'=>'info','incidencia'=>'danger','otro'=>'secondary'];
        $color = $colorPorTipo[$r['tipo_evento']] ?? 'secondary';
      ?>
        <div class="col-12">
          <div class="card shadow-sm">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-start">
                <div class="flex-grow-1">
                  <div class="d-flex align-items-center gap-2 mb-1">
                    <span class="badge bg-<?= $color ?>"><?= e(ucfirst($r['tipo_evento'])) ?></span>
                    <code class="small"><?= e($r['numero']) ?></code>
                    <span class="text-muted small"><?= e(format_date($r['fecha_evento'])) ?></span>
                  </div>
                  <h6 class="mb-1"><?= e($r['titulo']) ?></h6>
                  <p class="mb-1 small"><?= nl2br(e(mb_strimwidth($r['detalle'], 0, 280, '…'))) ?></p>
                  <small class="text-muted">
                    <i class="bi bi-person me-1"></i><?= e(trim(($r['autor_nombres']?:'') . ' ' . ($r['autor_apellidos']?:''))) ?>
                  </small>
                </div>
                <a class="btn btn-sm btn-outline-secondary" href="<?= base_url('obras/bitacora/' . (int)$r['id']) ?>"><i class="bi bi-eye"></i></a>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
<?php else: ?>
  <div class="alert alert-info">Selecciona un proyecto para ver su bitácora.</div>
<?php endif; ?>
