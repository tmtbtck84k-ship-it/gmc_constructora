<div class="d-flex align-items-center justify-content-between mb-3">
  <div>
    <h4 class="mb-0"><i class="bi bi-kanban me-2"></i>Proyectos</h4>
    <small class="text-muted"><?= count($proyectos) ?> registros</small>
  </div>
  <?php if (can('maestros.proyecto.crear')): ?>
    <a href="<?= base_url('maestros/proyectos/crear') ?>" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i> Nuevo proyecto</a>
  <?php endif; ?>
</div>

<form method="get" class="card card-body shadow-sm mb-3">
  <div class="row g-2">
    <div class="col-md-6"><input type="text" name="q" class="form-control" placeholder="Buscar por código, nombre o cliente" value="<?= e($filters['q']) ?>"></div>
    <div class="col-md-3">
      <select name="estado_id" class="form-select">
        <option value="">Todos los estados</option>
        <?php foreach ($estados as $e): ?>
          <option value="<?= (int)$e['id'] ?>" <?= ($filters['estado_id']==$e['id'])?'selected':'' ?>><?= e($e['nombre']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-3 d-flex gap-2">
      <button class="btn btn-outline-primary flex-grow-1"><i class="bi bi-search"></i> Filtrar</button>
      <a href="<?= base_url('maestros/proyectos') ?>" class="btn btn-outline-secondary"><i class="bi bi-x"></i></a>
    </div>
  </div>
</form>

<div class="card shadow-sm">
  <div class="table-responsive">
    <table class="table table-hover mb-0 datatable">
      <thead class="table-light">
        <tr>
          <th>Código</th><th>Nombre</th><th>Cliente</th>
          <th>Jefe Proyecto</th><th>Admin Obra</th>
          <th class="text-center">Estado</th>
          <th>Inicio</th>
          <th class="text-end">Acciones</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($proyectos as $p): ?>
        <tr>
          <td><code><?= e($p['codigo']) ?></code></td>
          <td><strong><?= e($p['nombre']) ?></strong></td>
          <td class="small"><?= e($p['cliente'] ?? '—') ?></td>
          <td class="small"><?= e(trim(($p['jp_nombres'] ?? '') . ' ' . ($p['jp_apellidos'] ?? '')) ?: '—') ?></td>
          <td class="small"><?= e(trim(($p['ao_nombres'] ?? '') . ' ' . ($p['ao_apellidos'] ?? '')) ?: '—') ?></td>
          <td class="text-center"><span class="badge bg-<?= e($p['estado_color'] ?? 'secondary') ?>"><?= e($p['estado_nombre'] ?? '—') ?></span></td>
          <td class="small"><?= e(format_date($p['fecha_inicio'])) ?: '—' ?></td>
          <td class="text-end">
            <a class="btn btn-sm btn-outline-secondary" href="<?= base_url('maestros/proyectos/' . (int)$p['id']) ?>"><i class="bi bi-eye"></i></a>
            <?php if (can('maestros.proyecto.editar')): ?>
              <a class="btn btn-sm btn-outline-primary" href="<?= base_url('maestros/proyectos/editar/' . (int)$p['id']) ?>"><i class="bi bi-pencil"></i></a>
            <?php endif; ?>
            <?php if (can('maestros.proyecto.eliminar')): ?>
              <a class="btn btn-sm btn-outline-danger js-confirm" data-confirm="¿Eliminar este proyecto?" href="<?= base_url('maestros/proyectos/eliminar/' . (int)$p['id']) ?>"><i class="bi bi-trash"></i></a>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
