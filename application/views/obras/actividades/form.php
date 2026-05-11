<?php
$esEdicion = !empty($a);
$action = $esEdicion
    ? site_url('obras/actividades/' . $a['id'] . '/actualizar')
    : site_url('obras/actividades/crear');
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="h4 mb-0">
        <i class="bi bi-list-task"></i>
        <?= $esEdicion ? 'Editar Actividad' : 'Nueva Actividad' ?>
        <small class="text-muted">— <?= htmlspecialchars($proyecto['codigo']) ?></small>
    </h2>
    <a class="btn btn-link" href="<?= site_url('obras/actividades?proyecto_id=' . $proyecto['id']) ?>">
        <i class="bi bi-arrow-left"></i> Volver
    </a>
</div>

<form method="post" action="<?= $action ?>" class="card card-body">
    <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>"
           value="<?= $this->security->get_csrf_hash() ?>">
    <input type="hidden" name="proyecto_id" value="<?= (int)$proyecto['id'] ?>">

    <div class="row g-3">
        <div class="col-md-8">
            <label class="form-label">Nombre <span class="text-danger">*</span></label>
            <input type="text" name="nombre" class="form-control" required maxlength="180"
                   value="<?= htmlspecialchars($a['nombre'] ?? '') ?>">
        </div>
        <div class="col-md-4">
            <label class="form-label">Hito</label>
            <select name="hito_id" class="form-select">
                <option value="">— suelta (sin hito) —</option>
                <?php foreach ($hitos as $h): ?>
                    <option value="<?= (int)$h['id'] ?>"
                        <?= (int)($a['hito_id'] ?? 0) === (int)$h['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($h['codigo']) ?> · <?= htmlspecialchars($h['nombre']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-4">
            <label class="form-label">Fecha de inicio planificada <span class="text-danger">*</span></label>
            <input type="date" name="fecha_inicio_planificada" class="form-control" required
                   value="<?= htmlspecialchars($a['fecha_inicio_planificada'] ?? '') ?>">
            <small class="text-muted">Si cae en día no laboral, se moverá al siguiente laboral.</small>
        </div>
        <div class="col-md-2">
            <label class="form-label">Duración (días) <span class="text-danger">*</span></label>
            <input type="number" name="duracion_dias" class="form-control" required min="1"
                   value="<?= (int)($a['duracion_dias'] ?? 1) ?>">
            <small class="text-muted">Días laborales del proyecto.</small>
        </div>
        <div class="col-md-3">
            <label class="form-label">Responsable</label>
            <select name="responsable_id" class="form-select">
                <option value="">— sin asignar —</option>
                <?php foreach ($usuarios as $u): ?>
                    <option value="<?= (int)$u['id'] ?>"
                        <?= (int)($a['responsable_id'] ?? 0) === (int)$u['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($u['nombre_completo'] ?? ($u['nombre'] ?? '')) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Orden</label>
            <input type="number" name="orden" class="form-control" min="0" step="10"
                   value="<?= (int)($a['orden'] ?? 0) ?>" placeholder="Auto">
        </div>

        <?php if ($esEdicion): ?>
            <div class="col-md-3">
                <label class="form-label">Inicio real</label>
                <input type="date" name="fecha_inicio_real" class="form-control"
                       value="<?= htmlspecialchars($a['fecha_inicio_real'] ?? '') ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Término real</label>
                <input type="date" name="fecha_termino_real" class="form-control"
                       value="<?= htmlspecialchars($a['fecha_termino_real'] ?? '') ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label">Avance %</label>
                <input type="number" class="form-control" min="0" max="100" step="0.01"
                       value="<?= number_format((float)($a['porcentaje_avance'] ?? 0), 2, '.', '') ?>"
                       readonly disabled>
                <small class="text-muted">Editar desde la lista o el Gantt.</small>
            </div>
        <?php endif; ?>

        <div class="col-12">
            <label class="form-label">Colaboradores (texto libre)</label>
            <input type="text" name="colaboradores_libres" class="form-control" maxlength="255"
                   value="<?= htmlspecialchars($a['colaboradores_libres'] ?? '') ?>"
                   placeholder="Ej: Juan Pérez, Cuadrilla 3, Subcontrato Estuco SpA">
        </div>

        <div class="col-12">
            <label class="form-label">Descripción</label>
            <textarea name="descripcion" rows="3" class="form-control"
                      maxlength="500"><?= htmlspecialchars($a['descripcion'] ?? '') ?></textarea>
        </div>
    </div>

    <hr>
    <div class="d-flex justify-content-end gap-2">
        <a class="btn btn-outline-secondary"
           href="<?= site_url('obras/actividades?proyecto_id=' . $proyecto['id']) ?>">Cancelar</a>
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-save"></i> <?= $esEdicion ? 'Guardar cambios' : 'Crear actividad' ?>
        </button>
    </div>
</form>
