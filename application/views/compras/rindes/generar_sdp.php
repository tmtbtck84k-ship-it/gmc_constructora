<div class="d-flex align-items-center justify-content-between mb-3">
  <h4 class="mb-0"><i class="bi bi-cash-coin me-2"></i>Generar SDP de pago para rinde <?= e($rinde['numero']) ?></h4>
  <a class="btn btn-outline-secondary" href="<?= base_url("compras/rindes/{$rinde['id']}") ?>"><i class="bi bi-arrow-left me-1"></i>Volver</a>
</div>

<form method="post" action="<?= base_url("compras/rindes/{$rinde['id']}/generar-sdp") ?>" class="card shadow-sm">
  <?= form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()) ?>
  <div class="card-body">
    <div class="alert alert-info small">
      Se creará una Solicitud de Pago en estado <strong>Pendiente</strong> con los datos del rinde.
      Selecciona el proveedor que recibirá el pago (típicamente, el usuario o un proveedor "Reembolsos").
      Total: <strong><?= e($rinde['moneda_simbolo']) ?> <?= number_format((float)$rinde['monto_total'], (int)$rinde['moneda_decimales'], ',', '.') ?></strong>
    </div>
    <div class="row g-3">
      <div class="col-md-6">
        <label class="form-label">Proveedor (destino del pago) *</label>
        <select name="proveedor_id" class="form-select select2" required>
          <option value="">— Seleccionar —</option>
          <?php foreach ($proveedores as $pr): ?>
            <option value="<?= (int)$pr['id'] ?>"><?= e(formatear_rut($pr['rut']) . ' · ' . $pr['razon_social']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-6">
        <label class="form-label">Tipo de gasto *</label>
        <select name="tipo_gasto_id" class="form-select select2" required>
          <option value="">— Seleccionar —</option>
          <?php foreach ($tipos_gasto as $tg): ?>
            <option value="<?= (int)$tg['id'] ?>"><?= e($tg['codigo'] . ' · ' . $tg['nombre']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
  </div>
  <div class="card-footer text-end">
    <button class="btn btn-primary"><i class="bi bi-cash-coin me-1"></i>Generar SDP</button>
  </div>
</form>
