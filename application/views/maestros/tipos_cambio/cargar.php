<div class="d-flex align-items-center justify-content-between mb-3">
  <h4 class="mb-0"><i class="bi bi-cloud-upload me-2"></i>Cargar tipos de cambio</h4>
  <a class="btn btn-outline-secondary" href="<?= base_url('maestros/tipos-cambio') ?>"><i class="bi bi-arrow-left me-1"></i>Volver</a>
</div>

<form method="post" action="<?= base_url('maestros/tipos-cambio/cargar') ?>" class="card shadow-sm">
  <?= form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()) ?>
  <div class="card-body">
    <p class="small text-muted">Ingresa el valor en CLP por cada unidad de la moneda. Por ejemplo, si 1 UF = $39.729, escribe <code>39729</code>. Si una moneda no varió hoy, déjala vacía.</p>

    <div class="row g-3 mb-3">
      <div class="col-md-4">
        <label class="form-label">Fecha *</label>
        <input type="date" name="fecha" class="form-control" value="<?= e($fecha) ?>" required>
      </div>
    </div>

    <div class="row g-3">
      <?php foreach ($monedas as $m): ?>
        <div class="col-md-4">
          <label class="form-label">
            <?= e($m['codigo']) ?> · <span class="text-muted small"><?= e($m['nombre']) ?></span>
          </label>
          <div class="input-group">
            <span class="input-group-text">$</span>
            <input type="number" step="0.0001" min="0"
                   name="valor[<?= e($m['codigo']) ?>]"
                   class="form-control text-end"
                   placeholder="0,0000">
            <span class="input-group-text">/ 1 <?= e($m['codigo']) ?></span>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
  <div class="card-footer text-end">
    <button class="btn btn-primary"><i class="bi bi-check2 me-1"></i> Guardar</button>
  </div>
</form>
