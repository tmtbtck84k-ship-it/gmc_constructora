<div class="d-flex align-items-center justify-content-between mb-3">
  <h4 class="mb-0"><i class="bi bi-x-circle me-2 text-danger"></i>Rechazar SDP <?= e($sdp['numero']) ?></h4>
  <a class="btn btn-outline-secondary" href="<?= base_url("finanzas/sdp/{$sdp['id']}") ?>"><i class="bi bi-arrow-left me-1"></i>Volver</a>
</div>

<form method="post" action="<?= base_url("finanzas/sdp/{$sdp['id']}/rechazar") ?>" class="card shadow-sm">
  <?= form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()) ?>
  <div class="card-body">
    <div class="alert alert-danger small">
      <strong>Atención:</strong> rechazar la SDP es una acción <strong>final</strong>. La SDP no podrá pasar a otro estado y queda registrada con el motivo en el log.
    </div>
    <div class="row g-3">
      <div class="col-12">
        <label class="form-label">Motivo del rechazo *</label>
        <textarea name="motivo" rows="4" class="form-control" minlength="5" maxlength="500" required
                  placeholder="Indica el motivo del rechazo (mínimo 5 caracteres). Esto será visible para el solicitante."></textarea>
        <div class="form-text">Máximo 500 caracteres.</div>
      </div>
    </div>
  </div>
  <div class="card-footer text-end">
    <button class="btn btn-danger js-confirm" data-confirm="¿Confirmar el rechazo de esta SDP?"><i class="bi bi-x-circle me-1"></i>Rechazar SDP</button>
  </div>
</form>
