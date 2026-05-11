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
      <div class="card-header bg-white border-bottom-0 fw-semibold">Gasto mensual (últimos 12 meses)</div>
      <div class="card-body">
        <canvas id="chart-gasto" height="100"></canvas>
      </div>
    </div>
  </div>

  <div class="col-12 col-lg-5">
    <div class="card shadow-sm">
      <div class="card-header bg-white border-bottom-0 fw-semibold">Últimas bitácoras</div>
      <ul class="list-group list-group-flush">
        <?php foreach ($bitacoras as $b):
          $colorPorTipo = ['avance'=>'success','observacion'=>'info','incidencia'=>'danger','otro'=>'secondary'];
          $color = $colorPorTipo[$b['tipo_evento']] ?? 'secondary'; ?>
          <li class="list-group-item">
            <div class="d-flex justify-content-between">
              <div>
                <a href="<?= base_url('obras/bitacora/' . (int)$b['id']) ?>" class="fw-semibold text-decoration-none"><?= e($b['titulo']) ?></a>
                <div class="small text-muted">
                  <span class="badge bg-<?= $color ?>"><?= e(ucfirst($b['tipo_evento'])) ?></span>
                  <?= e($b['proyecto_codigo']) ?> ·
                  <?= e(format_date($b['fecha_evento'])) ?>
                </div>
              </div>
            </div>
          </li>
        <?php endforeach; ?>
        <?php if (!$bitacoras): ?>
          <li class="list-group-item text-center text-muted py-4 small">Sin bitácoras registradas aún.</li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</div>

<script src="<?= base_url('assets/vendor/chartjs/chart.umd.min.js') ?>"></script>
<script>
(function () {
  var labels = <?= json_encode(array_keys($serie)) ?>;
  var data   = <?= json_encode(array_values($serie)) ?>;
  var ctx = document.getElementById('chart-gasto').getContext('2d');
  new Chart(ctx, {
    type: 'bar',
    data: {
      labels: labels,
      datasets: [{
        label: 'Gasto CLP',
        data: data,
        backgroundColor: 'rgba(13, 110, 253, 0.7)',
        borderColor: 'rgba(13, 110, 253, 1)',
        borderWidth: 1
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: { legend: { display: false } },
      scales: {
        y: { beginAtZero: true, ticks: { callback: function (v) { return '$ ' + v.toLocaleString('es-CL'); } } }
      }
    }
  });
})();
</script>
