<!doctype html><html lang="es"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Restablecer contraseña · ERP GMC</title>
<link rel="stylesheet" href="<?= base_url('assets/vendor/bootstrap/css/bootstrap.min.css') ?>">
</head><body class="bg-light">
<div class="d-flex align-items-center justify-content-center min-vh-100">
  <div class="card shadow-sm" style="width:420px"><div class="card-body p-4">
    <h5 class="mb-3">Restablecer contraseña</h5>
    <?php if (!$valid): ?>
      <div class="alert alert-danger small">El enlace expiró o no es válido.</div>
      <a class="btn btn-link p-0" href="<?= base_url('password/forgot') ?>">Solicitar uno nuevo</a>
    <?php else: ?>
      <?php if (!empty($error)): ?><div class="alert alert-danger small"><?= htmlspecialchars($error) ?></div><?php endif; ?>
      <form method="post" action="<?= base_url('password/reset/submit') ?>">
        <?= form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()) ?>
        <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
        <div class="mb-3">
          <label class="form-label">Nueva contraseña</label>
          <input type="password" name="new_password" class="form-control" required minlength="8" autocomplete="new-password">
          <div class="form-text">Mín 8, mayús + minús + dígito + símbolo.</div>
        </div>
        <div class="mb-3">
          <label class="form-label">Repetir contraseña</label>
          <input type="password" name="new_password_confirm" class="form-control" required minlength="8" autocomplete="new-password">
        </div>
        <button class="btn btn-primary w-100">Restablecer</button>
      </form>
    <?php endif; ?>
  </div></div>
</div></body></html>
