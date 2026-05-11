<div class="d-flex align-items-center justify-content-between mb-3">
  <h4 class="mb-0"><i class="bi bi-check2-all me-2"></i>Marcar pagada SDP <?= e($sdp['numero']) ?></h4>
  <a class="btn btn-outline-secondary" href="<?= base_url("finanzas/sdp/{$sdp['id']}") ?>"><i class="bi bi-arrow-left me-1"></i>Volver</a>
</div>

<form method="post" action="<?= base_url("finanzas/sdp/{$sdp['id']}/pagar") ?>" class="card shadow-sm">
  <?= form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()) ?>
  <div class="card-body">
    <div class="alert alert-warning small">
      <i class="bi bi-exclamation-triangle me-1"></i>
      Marcar como pagada es una acción <strong>final</strong>. Una vez confirmada, la SDP queda inmutable.
    </div>
    <div class="row g-3">
      <div class="col-md-4">
        <label class="form-label">Fecha de pago *</label>
        <input type="date" name="fecha_pago" class="form-control" required value="<?= date('Y-m-d') ?>">
      </div>
    </div>
  </div>
  <div class="card-footer text-end">
    <button class="btn btn-success"><i class="bi bi-check2-all me-1"></i>Confirmar pago</button>
  </div>
</form>
