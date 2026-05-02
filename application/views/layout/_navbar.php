<nav class="navbar navbar-expand-md navbar-light bg-white border-bottom px-3">
  <button class="btn btn-light d-md-none me-2" id="sidebarToggle"><i class="bi bi-list"></i></button>

  <div class="ms-auto d-flex align-items-center gap-3">
    <?php if (!empty($_env) && $_env !== 'production'): ?>
      <span class="badge bg-warning text-dark text-uppercase">Entorno: <?= e($_env) ?></span>
    <?php endif; ?>

    <div class="dropdown">
      <button class="btn btn-link dropdown-toggle text-dark text-decoration-none" data-bs-toggle="dropdown">
        <i class="bi bi-person-circle me-1"></i>
        <?= e($_user['nombres'] . ' ' . $_user['apellidos']) ?>
      </button>
      <ul class="dropdown-menu dropdown-menu-end">
        <li class="dropdown-item-text small text-muted"><?= e($_user['email']) ?></li>
        <li><hr class="dropdown-divider"></li>
        <li><a class="dropdown-item" href="<?= base_url('password/change') ?>"><i class="bi bi-key me-1"></i>Cambiar contraseña</a></li>
        <li><a class="dropdown-item" href="<?= base_url('logout') ?>"><i class="bi bi-box-arrow-right me-1"></i>Cerrar sesión</a></li>
      </ul>
    </div>
  </div>
</nav>
