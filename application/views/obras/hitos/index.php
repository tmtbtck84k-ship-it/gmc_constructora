<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="h4 mb-0"><i class="bi bi-flag"></i> Hitos del Proyecto</h2>
    <?php if ($proyecto && can('obras.gantt.editar')): ?>
        <a class="btn btn-primary" href="<?= site_url('obras/hitos/nuevo?proyecto_id=' . $proyecto['id']) ?>">
            <i class="bi bi-plus-circle"></i> Nuevo hito
        </a>
    <?php endif; ?>
</div>

<form method="get" class="card card-body mb-3">
    <div class="row g-2 align-items-end">
        <div class="col-md-6">
            <label class="form-label">Proyecto</label>
            <select name="proyecto_id" class="form-select" onchange="this.form.submit()">
                <option value="">— seleccione —</option>
                <?php foreach ($proyectos as $p): ?>
                    <option value="<?= (int)$p['id'] ?>"
                        <?= $proyecto && (int)$p['id'] === (int)$proyecto['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($p['codigo']) ?> — <?= htmlspecialchars($p['nombre']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
</form>

<?php if (!$proyecto): ?>
    <div class="alert alert-info">Seleccione un proyecto para ver sus hitos.</div>
<?php else: ?>
    <div class="card">
        <div class="table-responsive">
            <table class="table table-striped table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Código</th>
                        <th>Nombre</th>
                        <th>Fecha objetivo</th>
                        <th>Fecha real</th>
                        <th class="text-center">Avance</th>
                        <th class="text-center">Estado</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($rows)): ?>
                    <tr><td colspan="8" class="text-center text-muted py-4">Sin hitos registrados.</td></tr>
                <?php else: foreach ($rows as $h): ?>
                    <tr>
                        <td><?= (int)$h['orden'] ?></td>
                        <td><code><?= htmlspecialchars($h['codigo']) ?></code></td>
                        <td>
                            <strong><?= htmlspecialchars($h['nombre']) ?></strong>
                            <?php if (!empty($h['descripcion'])): ?>
                                <br><small class="text-muted"><?= htmlspecialchars($h['descripcion']) ?></small>
                            <?php endif; ?>
                        </td>
                        <td><?= !empty($h['fecha_objetivo']) ? htmlspecialchars($h['fecha_objetivo']) : '<span class="text-muted">—</span>' ?></td>
                        <td><?= !empty($h['fecha_real']) ? htmlspecialchars($h['fecha_real']) : '<span class="text-muted">—</span>' ?></td>
                        <td class="text-center" style="width: 160px;">
                            <div class="progress" style="height: 18px;">
                                <div class="progress-bar bg-success" role="progressbar"
                                     style="width: <?= (float)$h['porcentaje_avance'] ?>%;">
                                    <?= number_format((float)$h['porcentaje_avance'], 0) ?>%
                                </div>
                            </div>
                        </td>
                        <td class="text-center">
                            <?php if ((int)$h['completado'] === 1): ?>
                                <span class="badge bg-success">Completado</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">En curso</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <?php if (can('obras.gantt.editar')): ?>
                                <a class="btn btn-sm btn-outline-primary"
                                   href="<?= site_url('obras/hitos/' . $h['id'] . '/editar') ?>">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <a class="btn btn-sm btn-outline-success"
                                   href="<?= site_url('obras/actividades?proyecto_id=' . $proyecto['id'] . '&hito_id=' . $h['id']) ?>"
                                   title="Ver actividades">
                                    <i class="bi bi-list-task"></i>
                                </a>
                                <a class="btn btn-sm btn-outline-danger"
                                   href="<?= site_url('obras/hitos/' . $h['id'] . '/eliminar') ?>"
                                   onclick="return confirm('¿Eliminar este hito? Las actividades asociadas quedarán sueltas.');">
                                    <i class="bi bi-trash"></i>
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>
