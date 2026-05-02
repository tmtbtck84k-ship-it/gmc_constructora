<div class="d-flex align-items-center justify-content-between mb-4">
  <div>
    <h3 class="mb-0">Bienvenido, <?= e($_user['nombres']) ?></h3>
    <small class="text-muted"><?= e(date('l, d \d\e F \d\e Y')) ?></small>
  </div>
</div>

<div class="row g-3 mb-4">
  <?php foreach ($kpis as $kpi): ?>
  <div class="col-12 col-md-6 col-lg-4">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-body d-flex align-items-center">
        <div class="rounded-circle bg-<?= e($kpi['color']) ?> bg-opacity-10 p-3 me-3">
          <i class="bi bi-<?= e($kpi['icon']) ?> fs-3 text-<?= e($kpi['color']) ?>"></i>
        </div>
        <div>
          <div class="text-muted small text-uppercase"><?= e($kpi['label']) ?></div>
          <div class="fs-4 fw-bold"><?= e($kpi['value']) ?></div>
        </div>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<div class="row g-3">
  <div class="col-12 col-lg-7">
    <div class="card shadow-sm">
      <div class="card-header bg-white border-bottom-0 fw-semibold">Gasto mensual por obra</div>
      <div class="card-body">
        <div class="text-center text-muted py-5">
          <i class="bi bi-bar-chart-line fs-1"></i>
          <p class="mt-2 mb-0 small">Disponible en Sprint 5 (KPIs reales)</p>
        </div>
      </div>
    </div>
  </div>

  <div class="col-12 col-lg-5">
    <div class="card shadow-sm">
      <div class="card-header bg-white border-bottom-0 fw-semibold">Últimas bitácoras</div>
      <div class="card-body">
        <div class="text-center text-muted py-5">
          <i class="bi bi-journal-text fs-1"></i>
          <p class="mt-2 mb-0 small">Disponible cuando exista el módulo de obras (Sprint 4)</p>
        </div>
      </div>
    </div>
  </div>
</div>
