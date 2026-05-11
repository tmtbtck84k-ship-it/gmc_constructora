<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="h4 mb-0"><i class="bi bi-calendar-x"></i> Feriados</h2>
    <div class="d-flex gap-2">
        <?php if (can('obras.feriado.editar')): ?>
            <a class="btn btn-outline-secondary" href="<?= site_url('obras/feriados/importar') ?>">
                <i class="bi bi-upload"></i> Importar CSV
            </a>
            <a class="btn btn-primary" href="<?= site_url('obras/feriados/nuevo') ?>">
                <i class="bi bi-plus-circle"></i> Nuevo feriado
            </a>
        <?php endif; ?>
    </div>
</div>

<form method="get" class="card card-body mb-3">
    <div class="row g-2 align-items-end">
        <div class="col-md-3">
            <label class="form-label">Año</label>
            <select name="anio" class="form-select" onchange="this.form.submit()">
                <?php for ($y = (int)date('Y') - 2; $y <= (int)date('Y') + 3; $y++): ?>
                    <option value="<?= $y ?>" <?= $y === $anio ? 'selected' : '' ?>><?= $y ?></option>
                <?php endfor; ?>
            </select>
        </div>
    </div>
</form>

<?php if ($errores = $this->session->flashdata('errores_csv')): ?>
    <div class="alert alert-warning">
        <strong>Detalles de la importación:</strong>
        <ul class="mb-0">
            <?php foreach ($errores as $e): ?>
                <li><?= htmlspecialchars($e) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="card">
    <div class="table-responsive">
        <table class="table table-striped table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>Fecha</th>
                    <th>Nombre</th>
                    <th class="text-center">Irrenunciable</th>
                    <th>Tipo</th>
                    <?php if (can('obras.feriado.editar')): ?>
                        <th class="text-end">Acciones</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($rows)): ?>
                <tr><td colspan="5" class="text-center text-muted py-4">Sin feriados para <?= $anio ?>.</td></tr>
            <?php else: foreach ($rows as $f): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($f['fecha']) ?></strong></td>
                    <td><?= htmlspecialchars($f['nombre']) ?></td>
                    <td class="text-center">
                        <?php if ((int)$f['irrenunciable'] === 1): ?>
                            <span class="badge bg-danger">Sí</span>
                        <?php else: ?>
                            <span class="badge bg-light text-dark">No</span>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($f['tipo'] ?? '') ?></td>
                    <?php if (can('obras.feriado.editar')): ?>
                        <td class="text-end">
                            <a class="btn btn-sm btn-outline-primary"
                               href="<?= site_url('obras/feriados/' . $f['id'] . '/editar') ?>">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <a class="btn btn-sm btn-outline-danger"
                               href="<?= site_url('obras/feriados/' . $f['id'] . '/eliminar') ?>"
                               onclick="return confirm('¿Eliminar este feriado?');">
                                <i class="bi bi-trash"></i>
                            </a>
                        </td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
