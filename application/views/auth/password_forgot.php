<!doctype html><html lang="es"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Recuperar contraseña · ERP GMC</title>
<link rel="stylesheet" href="<?= base_url('assets/vendor/bootstrap/css/bootstrap.min.css') ?>">
<link rel="stylesheet" href="<?= base_url('assets/vendor/bootstrap-icons/bootstrap-icons.css') ?>">
</head><body class="bg-light">
<div class="d-flex align-items-center justify-content-center min-vh-100">
  <div class="card shadow-sm" style="width:380px"><div class="card-body p-4">
    <h5 class="mb-3"><i class="bi bi-envelope-arrow-up me-2"></i>Recuperar contraseña</h5>
    <p class="small text-muted">Te enviaremos un enlace para restablecerla.</p>
    <?php if (!empty($ok)): ?><div class="alert alert-success small"><?= htmlspecialchars($ok) ?></div><?php endif; ?>
    <?php if (!empty($error)): ?><div class="alert alert-danger small"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <form method="post" action="<?= base_url('password/forgot/submit') ?>">
      <?= form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()) ?>
      <div class="mb-3">
        <label class="form-label">Email</label>
        <input type="email" name="email" class="form-control" required autofocus>
      </div>
      <button class="btn btn-primary w-100">Enviar instrucciones</button>
    </form>
    <div class="text-center mt-3"><a href="<?= base_url('login') ?>" class="small text-muted">Volver a ingresar</a></div>
  </div></div>
</div></body></html>
