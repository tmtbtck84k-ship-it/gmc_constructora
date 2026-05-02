<!doctype html>
<html lang="es"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Cambiar contraseña · ERP GMC</title>
<link rel="stylesheet" href="<?= base_url('assets/vendor/bootstrap/css/bootstrap.min.css') ?>">
<link rel="stylesheet" href="<?= base_url('assets/vendor/bootstrap-icons/bootstrap-icons.css') ?>">
</head><body class="bg-light">
<div class="d-flex align-items-center justify-content-center min-vh-100">
  <div class="card shadow-sm" style="width:440px">
    <div class="card-body p-4">
      <h5 class="mb-3"><i class="bi bi-key me-2"></i>Cambiar contraseña</h5>
      <?php if (!empty($force)): ?>
        <div class="alert alert-warning small">Por seguridad, debes cambiar tu contraseña antes de continuar.</div>
      <?php endif; ?>
      <?php if (!empty($error)): ?>
        <div class="alert alert-danger small"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <form method="post" action="<?= base_url('password/change/submit') ?>">
        <?= form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()) ?>
        <div class="mb-3">
          <label class="form-label">Contraseña actual</label>
          <input type="password" name="current_password" class="form-control" required autocomplete="current-password">
        </div>
        <div class="mb-3">
          <label class="form-label">Nueva contraseña</label>
          <input type="password" name="new_password" class="form-control" required minlength="8" autocomplete="new-password">
          <div class="form-text">Mínimo 8 caracteres con mayúscula, minúscula, número y símbolo.</div>
        </div>
        <div class="mb-3">
          <label class="form-label">Repetir nueva contraseña</label>
          <input type="password" name="new_password_confirm" class="form-control" required minlength="8" autocomplete="new-password">
        </div>
        <button class="btn btn-primary w-100">Actualizar</button>
      </form>

      <div class="text-center mt-3">
        <a href="<?= base_url('logout') ?>" class="small text-muted">Cerrar sesión</a>
      </div>
    </div>
  </div>
</div>
</body></html>
