<div class="d-flex align-items-center justify-content-between mb-3">
  <h4 class="mb-0"><i class="bi bi-calendar-event me-2"></i>Programar pago de SDP <?= e($sdp['numero']) ?></h4>
  <a class="btn btn-outline-secondary" href="<?= base_url("finanzas/sdp/{$sdp['id']}") ?>"><i class="bi bi-arrow-left me-1"></i>Volver</a>
</div>

<form method="post" action="<?= base_url("finanzas/sdp/{$sdp['id']}/programar") ?>" class="card shadow-sm">
  <?= form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()) ?>
  <div class="card-body">
    <div class="alert alert-info small">
      <strong>Proveedor:</strong> <?= e($sdp['proveedor']) ?> · <strong>Total:</strong> <?= e($sdp['moneda_simbolo']) ?> <?= number_format((float)$sdp['monto_total'], (int)$sdp['moneda_decimales'], ',', '.') ?>
    </div>
    <div class="row g-3">
      <div class="col-md-4">
        <label class="form-label">Fecha programada *</label>
        <input type="date" name="fecha_programada" class="form-control" required min="<?= date('Y-m-d') ?>" value="<?= date('Y-m-d') ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label">Forma de pago</label>
        <select name="forma_pago" class="form-select">
          <option value="">— Seleccionar —</option>
          <option value="transferencia">Transferencia</option>
          <option value="cheque">Cheque</option>
          <option value="efectivo">Efectivo</option>
          <option value="otro">Otro</option>
        </select>
      </div>
    </div>
  </div>
  <div class="card-footer text-end">
    <button class="btn btn-primary"><i class="bi bi-check2 me-1"></i>Programar</button>
  </div>
</form>
