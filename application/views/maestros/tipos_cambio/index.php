<?php $meses = [1=>'Enero',2=>'Febrero',3=>'Marzo',4=>'Abril',5=>'Mayo',6=>'Junio',7=>'Julio',8=>'Agosto',9=>'Septiembre',10=>'Octubre',11=>'Noviembre',12=>'Diciembre']; ?>
<div class="d-flex align-items-center justify-content-between mb-3">
  <div>
    <h4 class="mb-0"><i class="bi bi-currency-exchange me-2"></i>Tipos de Cambio</h4>
    <small class="text-muted"><?= e($meses[$mes]) ?> <?= (int)$anio ?></small>
  </div>
  <?php if (can('maestros.tipo_cambio.editar')): ?>
    <a href="<?= base_url('maestros/tipos-cambio/cargar') ?>" class="btn btn-primary">
      <i class="bi bi-cloud-upload me-1"></i> Cargar TC del día
    </a>
  <?php endif; ?>
</div>

<div class="row g-3 mb-3">
  <?php foreach ($monedas as $m): ?>
    <div class="col-md-4">
      <div class="card border-0 shadow-sm">
        <div class="card-body d-flex align-items-center">
          <div class="rounded-circle bg-primary bg-opacity-10 p-3 me-3">
            <span class="fs-5 fw-bold text-primary"><?= e($m['simbolo'] ?: $m['codigo']) ?></span>
          </div>
          <div>
            <div class="text-muted small text-uppercase">TC vigente · <?= e($m['codigo']) ?></div>
            <div class="fs-4 fw-bold">
              <?= $vigentes[$m['codigo']] !== null
                  ? '$ ' . number_format($vigentes[$m['codigo']], $m['decimales'], ',', '.')
                  : '<span class="text-warning">Sin TC cargado</span>' ?>
            </div>
            <div class="small text-muted">por 1 <?= e($m['codigo']) ?></div>
          </div>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<form method="get" class="card card-body shadow-sm mb-3">
  <div class="row g-2">
    <div class="col-md-3">
      <label class="form-label small text-muted">Año</label>
      <input type="number" name="anio" class="form-control" value="<?= (int)$anio ?>" min="2020" max="2099">
    </div>
    <div class="col-md-3">
      <label class="form-label small text-muted">Mes</label>
      <select name="mes" class="form-select">
        <?php foreach ($meses as $i => $n): ?>
          <option value="<?= $i ?>" <?= ($i===$mes)?'selected':'' ?>><?= e($n) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-3 d-flex align-items-end">
      <button class="btn btn-outline-primary"><i class="bi bi-search"></i> Ver mes</button>
    </div>
  </div>
</form>

<div class="card shadow-sm">
  <div class="card-header bg-white fw-semibold">Histórico del mes</div>
  <div class="table-responsive">
    <table class="table table-sm mb-0">
      <thead class="table-light">
        <tr>
          <th>Fecha</th>
          <?php foreach ($monedas as $m): ?>
            <th class="text-end"><?= e($m['codigo']) ?> (CLP por 1 <?= e($m['codigo']) ?>)</th>
          <?php endforeach; ?>
        </tr>
      </thead>
      <tbody>
        <?php if (!$rows): ?>
          <tr><td colspan="<?= count($monedas)+1 ?>" class="text-center text-muted py-4">Sin tipos de cambio cargados para este mes.</td></tr>
        <?php endif; ?>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td><?= e(format_date($r['fecha'])) ?></td>
            <?php foreach ($monedas as $m): ?>
              <td class="text-end">
                <?php if (isset($r[$m['codigo']])): $cell = $r[$m['codigo']]; ?>
                  $ <?= number_format((float)$cell['valor'], $m['decimales'], ',', '.') ?>
                  <?php if (($cell['origen'] ?? 'manual') === 'auto'): ?>
                    <i class="bi bi-cloud-check text-info small ms-1" title="Sincronizado desde mindicador.cl"></i>
                  <?php else: ?>
                    <i class="bi bi-pencil-square text-secondary small ms-1" title="Cargado manualmente"></i>
                  <?php endif; ?>
                <?php else: ?>
                  <span class="text-muted">—</span>
                <?php endif; ?>
              </td>
            <?php endforeach; ?>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
