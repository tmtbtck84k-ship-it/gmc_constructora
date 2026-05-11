<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="h4 mb-0"><i class="bi bi-list-task"></i> Actividades</h2>
    <?php if ($proyecto && can('obras.gantt.editar')): ?>
        <a class="btn btn-primary" href="<?= site_url('obras/actividades/nuevo?proyecto_id=' . $proyecto['id']) ?>">
            <i class="bi bi-plus-circle"></i> Nueva actividad
        </a>
    <?php endif; ?>
</div>

<form method="get" class="card card-body mb-3">
    <div class="row g-2 align-items-end">
        <div class="col-md-4">
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
        <?php if ($proyecto): ?>
            <div class="col-md-3">
                <label class="form-label">Hito</label>
                <select name="hito_id" class="form-select">
                    <option value="">Todos</option>
                    <?php foreach ($hitos as $h): ?>
                        <option value="<?= (int)$h['id'] ?>"
                            <?= (int)($filters['hito_id'] ?? 0) === (int)$h['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($h['codigo']) ?> · <?= htmlspecialchars($h['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Responsable</label>
                <select name="responsable_id" class="form-select">
                    <option value="">Todos</option>
                    <?php foreach ($usuarios as $u): ?>
                        <option value="<?= (int)$u['id'] ?>"
                            <?= (int)($filters['responsable_id'] ?? 0) === (int)$u['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($u['nombre_completo'] ?? ($u['nombre'] ?? '')) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Desde</label>
                <input type="date" name="desde" class="form-control"
                       value="<?= htmlspecialchars($filters['desde'] ?? '') ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label">Hasta</label>
                <input type="date" name="hasta" class="form-control"
                       value="<?= htmlspecialchars($filters['hasta'] ?? '') ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label">Estado</label>
                <select name="estado" class="form-select">
                    <option value="">Todos</option>
                    <option value="pendiente"<?= ($filters['estado'] ?? '') === 'pendiente' ? ' selected' : '' ?>>Pendiente</option>
                    <option value="en_curso"<?= ($filters['estado'] ?? '') === 'en_curso' ? ' selected' : '' ?>>En curso</option>
                    <option value="completada"<?= ($filters['estado'] ?? '') === 'completada' ? ' selected' : '' ?>>Completada</option>
                    <option value="atrasada"<?= ($filters['estado'] ?? '') === 'atrasada' ? ' selected' : '' ?>>Atrasada</option>
                </select>
            </div>
            <div class="col-md-2 d-grid">
                <button class="btn btn-outline-primary" type="submit"><i class="bi bi-search"></i> Filtrar</button>
            </div>
        <?php endif; ?>
    </div>
</form>

<?php if (!$proyecto): ?>
    <div class="alert alert-info">Seleccione un proyecto para ver sus actividades.</div>
<?php else: ?>
    <div class="card">
        <div class="table-responsive">
            <table class="table table-striped table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Código</th>
                        <th>Hito</th>
                        <th>Actividad</th>
                        <th>Inicio plan</th>
                        <th>Término plan</th>
                        <th>Días</th>
                        <th>Responsable</th>
                        <th class="text-center">Avance</th>
                        <th class="text-center">Estado</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($rows)): ?>
                    <tr><td colspan="10" class="text-center text-muted py-4">Sin actividades.</td></tr>
                <?php else: foreach ($rows as $a):
                    $hoy = date('Y-m-d');
                    $estado = 'pendiente';
                    if ((float)$a['porcentaje_avance'] >= 100) $estado = 'completada';
                    elseif ((float)$a['porcentaje_avance'] > 0) $estado = 'en_curso';
                    if ($estado !== 'completada' && !empty($a['fecha_termino_planificada']) && $a['fecha_termino_planificada'] < $hoy) {
                        $estado = 'atrasada';
                    }
                    $colorEstado = ['pendiente'=>'secondary','en_curso'=>'info','completada'=>'success','atrasada'=>'danger'][$estado];
                ?>
                    <tr>
                        <td><code><?= htmlspecialchars($a['codigo']) ?></code></td>
                        <td><?= !empty($a['hito_codigo']) ? htmlspecialchars($a['hito_codigo']) : '<span class="text-muted">—</span>' ?></td>
                        <td>
                            <strong><?= htmlspecialchars($a['nombre']) ?></strong>
                            <?php if (!empty($a['descripcion'])): ?>
                                <br><small class="text-muted"><?= htmlspecialchars($a['descripcion']) ?></small>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($a['fecha_inicio_planificada']) ?></td>
                        <td><?= htmlspecialchars($a['fecha_termino_planificada']) ?></td>
                        <td class="text-center"><?= (int)$a['duracion_dias'] ?></td>
                        <td><?= !empty($a['responsable_nombre']) ? htmlspecialchars($a['responsable_nombre']) : '<span class="text-muted">—</span>' ?></td>
                        <td class="text-center" style="width: 140px;">
                            <div class="progress" style="height: 18px;">
                                <div class="progress-bar bg-<?= $colorEstado ?>" role="progressbar"
                                     style="width: <?= (float)$a['porcentaje_avance'] ?>%;">
                                    <?= number_format((float)$a['porcentaje_avance'], 0) ?>%
                                </div>
                            </div>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-<?= $colorEstado ?>">
                                <?= ucfirst(str_replace('_',' ',$estado)) ?>
                            </span>
                        </td>
                        <td class="text-end text-nowrap">
                            <?php if (can('obras.gantt.editar')): ?>
                                <a class="btn btn-sm btn-outline-primary"
                                   href="<?= site_url('obras/actividades/' . $a['id'] . '/editar') ?>">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <a class="btn btn-sm btn-outline-danger"
                                   href="<?= site_url('obras/actividades/' . $a['id'] . '/eliminar') ?>"
                                   onclick="return confirm('¿Eliminar esta actividad? Sus dependencias también se borrarán.');">
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
