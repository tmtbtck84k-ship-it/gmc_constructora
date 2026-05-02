<div class="d-flex align-items-center justify-content-between mb-3">
  <div>
    <h4 class="mb-0"><i class="bi bi-shield-lock me-2"></i>Roles y permisos</h4>
    <small class="text-muted"><?= count($roles) ?> roles · <?= array_sum(array_map('count', $permisos)) ?> permisos</small>
  </div>
</div>

<div class="alert alert-info small">
  <i class="bi bi-info-circle me-1"></i>
  Esta es una vista de lectura de la matriz roles × permisos. Para modificar los permisos de un rol específico, haz clic en <strong>"Editar permisos"</strong>.
  El rol <code>admin</code> tiene siempre todos los permisos y no es editable.
</div>

<div class="card shadow-sm">
  <div class="table-responsive">
    <table class="table table-sm align-middle mb-0">
      <thead class="table-light">
        <tr>
          <th class="ps-3" style="min-width:260px">Permiso</th>
          <?php foreach ($roles as $r): ?>
            <th class="text-center" style="min-width:100px">
              <span class="badge bg-secondary"><?= e($r['codigo']) ?></span>
              <div class="small text-muted mt-1"><?= e($r['nombre']) ?></div>
              <div class="small fw-bold"><?= (int)$counts[(int)$r['id']] ?> permisos</div>
            </th>
          <?php endforeach; ?>
          <th class="text-end pe-3" style="min-width:160px">Acciones</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($permisos as $modulo => $perms): ?>
        <tr class="table-secondary">
          <td colspan="<?= count($roles) + 2 ?>" class="ps-3 fw-bold text-uppercase small">
            <i class="bi bi-folder me-1"></i><?= e($modulo) ?>
          </td>
        </tr>
        <?php foreach ($perms as $p): ?>
          <tr>
            <td class="ps-3">
              <code class="small"><?= e($p['codigo']) ?></code>
              <div class="small text-muted"><?= e($p['descripcion']) ?></div>
            </td>
            <?php foreach ($roles as $r): ?>
              <td class="text-center">
                <?php if (isset($matriz[(int)$r['id']][(int)$p['id']])): ?>
                  <i class="bi bi-check-circle-fill text-success"></i>
                <?php else: ?>
                  <i class="bi bi-dash text-muted"></i>
                <?php endif; ?>
              </td>
            <?php endforeach; ?>
            <td></td>
          </tr>
        <?php endforeach; ?>
      <?php endforeach; ?>

      <tr class="table-light">
        <td class="ps-3 fw-bold">Editar</td>
        <?php foreach ($roles as $r): ?>
          <td class="text-center">
            <?php if (can('admin.rol.editar')): ?>
              <a class="btn btn-sm btn-outline-primary" href="<?= base_url("admin/roles/" . (int)$r['id'] . "/permisos") ?>"
                 title="Editar permisos">
                <i class="bi bi-pencil"></i>
              </a>
            <?php endif; ?>
          </td>
        <?php endforeach; ?>
        <td></td>
      </tr>
      </tbody>
    </table>
  </div>
</div>
