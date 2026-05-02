<?php $isAdmin = $rol['codigo'] === 'admin'; ?>
<div class="d-flex align-items-center justify-content-between mb-3">
  <div>
    <h4 class="mb-0"><i class="bi bi-shield-check me-2"></i>Permisos del rol: <span class="text-primary"><?= e($rol['nombre']) ?></span></h4>
    <small class="text-muted"><code><?= e($rol['codigo']) ?></code> · <?= e($rol['descripcion'] ?? '') ?></small>
  </div>
  <a href="<?= base_url('admin/roles') ?>" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Volver</a>
</div>

<?php if ($isAdmin): ?>
  <div class="alert alert-warning"><i class="bi bi-shield-exclamation me-1"></i>
    El rol <code>admin</code> tiene siempre todos los permisos y no se puede modificar.
  </div>
<?php endif; ?>

<form method="post" action="<?= base_url("admin/roles/{$rol['id']}/permisos") ?>" class="card shadow-sm">
  <?= form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()) ?>
  <div class="card-body">

    <?php foreach ($permisos as $modulo => $perms): ?>
      <fieldset class="mb-4">
        <legend class="h6 text-uppercase text-muted border-bottom pb-2">
          <i class="bi bi-folder me-1"></i><?= e($modulo) ?>
          <button type="button" class="btn btn-sm btn-link p-0 ms-2 toggle-modulo" data-modulo="<?= e($modulo) ?>">
            <small>Marcar/desmarcar todos</small>
          </button>
        </legend>
        <div class="row g-2">
          <?php foreach ($perms as $p): ?>
            <div class="col-md-6">
              <div class="form-check">
                <input class="form-check-input perm-<?= e($modulo) ?>" type="checkbox" name="permisos[]"
                       id="perm_<?= (int)$p['id'] ?>"
                       value="<?= (int)$p['id'] ?>"
                       <?= isset($permisosRol[(int)$p['id']]) ? 'checked' : '' ?>
                       <?= $isAdmin ? 'disabled' : '' ?>>
                <label class="form-check-label" for="perm_<?= (int)$p['id'] ?>">
                  <code class="small"><?= e($p['codigo']) ?></code>
                  <div class="small text-muted"><?= e($p['descripcion']) ?></div>
                </label>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </fieldset>
    <?php endforeach; ?>

  </div>
  <div class="card-footer text-end">
    <button class="btn btn-primary" <?= $isAdmin ? 'disabled' : '' ?>>
      <i class="bi bi-check2 me-1"></i> Guardar permisos
    </button>
  </div>
</form>

<script>
document.querySelectorAll('.toggle-modulo').forEach(btn => {
  btn.addEventListener('click', () => {
    const mod = btn.dataset.modulo;
    const cbs = document.querySelectorAll('.perm-' + CSS.escape(mod));
    const allChecked = Array.from(cbs).every(c => c.checked);
    cbs.forEach(c => { if (!c.disabled) c.checked = !allChecked; });
  });
});
</script>
