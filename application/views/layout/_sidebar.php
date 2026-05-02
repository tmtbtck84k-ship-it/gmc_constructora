<nav id="sidebar" class="sidebar bg-dark text-white">
  <div class="sidebar-brand p-3 d-flex align-items-center justify-content-between">
    <div>
      <i class="bi bi-buildings-fill me-2 text-warning"></i>
      <span class="fw-bold">ERP GMC</span>
    </div>
    <button class="btn btn-sm btn-outline-light d-md-none" id="sidebarClose"><i class="bi bi-x"></i></button>
  </div>

  <ul class="nav flex-column p-2">
    <?php foreach (menu_items() as $it): ?>
      <?php if (isset($it['_section'])): ?>
        <li class="nav-section text-uppercase small text-secondary mt-3 mb-1 px-2"><?= e($it['_section']) ?></li>
      <?php else: ?>
        <li class="nav-item">
          <a class="nav-link <?= is_active_url($it['url']) ? 'active' : '' ?>" href="<?= base_url($it['url']) ?>">
            <i class="bi bi-<?= e($it['icon']) ?> me-2"></i>
            <span><?= e($it['label']) ?></span>
          </a>
        </li>
      <?php endif; ?>
    <?php endforeach; ?>
  </ul>
</nav>
