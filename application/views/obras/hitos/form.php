<?php
$esEdicion = !empty($h);
$action = $esEdicion
    ? site_url('obras/hitos/' . $h['id'] . '/actualizar')
    : site_url('obras/hitos/crear');
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="h4 mb-0">
        <i class="bi bi-flag"></i>
        <?= $esEdicion ? 'Editar Hito' : 'Nuevo Hito' ?>
        <small class="text-muted">— <?= htmlspecialchars($proyecto['codigo']) ?></small>
    </h2>
    <a class="btn btn-link" href="<?= site_url('obras/hitos?proyecto_id=' . $proyecto['id']) ?>">
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
                   value="<?= htmlspecialchars($h['nombre'] ?? '') ?>">
        </div>
        <div class="col-md-4">
            <label class="form-label">Orden</label>
            <input type="number" name="orden" class="form-control" min="0" step="10"
                   value="<?= (int)($h['orden'] ?? 0) ?>"
                   placeholder="Auto si lo dejas vacío">
        </div>

        <div class="col-md-6">
            <label class="form-label">Fecha objetivo</label>
            <input type="date" name="fecha_objetivo" class="form-control"
                   value="<?= htmlspecialchars($h['fecha_objetivo'] ?? '') ?>">
        </div>
        <?php if ($esEdicion): ?>
            <div class="col-md-6">
                <label class="form-label">Fecha real</label>
                <input type="date" name="fecha_real" class="form-control"
                       value="<?= htmlspecialchars($h['fecha_real'] ?? '') ?>">
            </div>
        <?php endif; ?>

        <div class="col-12">
            <label class="form-label">Descripción</label>
            <textarea name="descripcion" rows="3" class="form-control"
                      maxlength="500"><?= htmlspecialchars($h['descripcion'] ?? '') ?></textarea>
        </div>
    </div>

    <hr>
    <div class="d-flex justify-content-end gap-2">
        <a class="btn btn-outline-secondary"
           href="<?= site_url('obras/hitos?proyecto_id=' . $proyecto['id']) ?>">Cancelar</a>
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-save"></i> <?= $esEdicion ? 'Guardar cambios' : 'Crear hito' ?>
        </button>
    </div>
</form>
