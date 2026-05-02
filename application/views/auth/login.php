<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Ingresar · ERP GMC</title>
<link rel="icon" href="<?= base_url('assets/img/favicon.png') ?>" type="image/png">
<link rel="stylesheet" href="<?= base_url('assets/vendor/bootstrap/css/bootstrap.min.css') ?>">
<link rel="stylesheet" href="<?= base_url('assets/vendor/bootstrap-icons/bootstrap-icons.css') ?>">
<link rel="stylesheet" href="<?= base_url('assets/css/app.css') ?>">
</head>
<body class="bg-light">
<div class="d-flex align-items-center justify-content-center min-vh-100">
  <div class="card shadow-sm" style="width: 380px">
    <div class="card-body p-4">
      <div class="text-center mb-4">
        <i class="bi bi-buildings-fill text-primary" style="font-size:42px"></i>
        <h4 class="mt-2 mb-0">ERP GMC</h4>
        <small class="text-muted">Modelo de Gestión Integral</small>
      </div>

      <?php if (!empty($error)): ?>
        <div class="alert alert-danger py-2 small"><i class="bi bi-exclamation-triangle me-1"></i><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <form method="post" action="<?= base_url('login/submit') ?>">
        <?= form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()) ?>
        <div class="mb-3">
          <label class="form-label">RUT</label>
          <input type="text" name="rut" class="form-control" placeholder="12.345.678-9"
                 value="<?= htmlspecialchars($rut ?? '') ?>" autocomplete="username" required autofocus>
        </div>
        <div class="mb-3">
          <label class="form-label">Contraseña</label>
          <input type="password" name="password" class="form-control" autocomplete="current-password" required>
        </div>
        <button type="submit" class="btn btn-primary w-100">
          <i class="bi bi-box-arrow-in-right me-1"></i> Ingresar
        </button>
      </form>

      <div class="text-center mt-3">
        <a href="<?= base_url('password/forgot') ?>" class="small text-muted">¿Olvidaste tu contraseña?</a>
      </div>
    </div>
  </div>
</div>
</body></html>
