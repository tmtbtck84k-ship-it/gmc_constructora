<div class="d-flex align-items-center justify-content-between mb-3">
  <h4 class="mb-0"><i class="bi bi-x-circle text-danger me-2"></i>Anular compra <?= e($compra['numero']) ?></h4>
  <a class="btn btn-outline-secondary" href="<?= base_url("compras/compras/{$compra['id']}") ?>"><i class="bi bi-arrow-left me-1"></i>Volver</a>
</div>

<form method="post" action="<?= base_url("compras/compras/{$compra['id']}/anular") ?>" class="card shadow-sm">
  <?= form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()) ?>
  <div class="card-body">
    <div class="alert alert-danger small">Anular es una acción <strong>final</strong>. La compra no podrá volver a su estado anterior.</div>
    <label class="form-label">Motivo *</label>
    <textarea name="motivo" rows="3" class="form-control" minlength="5" maxlength="500" required></textarea>
  </div>
  <div class="card-footer text-end">
    <button class="btn btn-danger js-confirm" data-confirm="¿Confirmar anulación?"><i class="bi bi-x-circle me-1"></i>Anular</button>
  </div>
</form>
