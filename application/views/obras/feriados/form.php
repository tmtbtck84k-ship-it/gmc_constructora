<?php
$esEdicion = !empty($f);
$action = $esEdicion
    ? site_url('obras/feriados/' . $f['id'] . '/actualizar')
    : site_url('obras/feriados/crear');
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="h4 mb-0">
        <i class="bi bi-calendar-x"></i>
        <?= $esEdicion ? 'Editar Feriado' : 'Nuevo Feriado' ?>
    </h2>
    <a class="btn btn-link" href="<?= site_url('obras/feriados') ?>">
        <i class="bi bi-arrow-left"></i> Volver
    </a>
</div>

<form method="post" action="<?= $action ?>" class="card card-body" style="max-width: 720px;">
    <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>"
           value="<?= $this->security->get_csrf_hash() ?>">

    <div class="row g-3">
        <div class="col-md-4">
            <label class="form-label">Fecha <span class="text-danger">*</span></label>
            <input type="date" name="fecha" class="form-control" required
                   value="<?= htmlspecialchars($f['fecha'] ?? '') ?>">
        </div>
        <div class="col-md-8">
            <label class="form-label">Nombre <span class="text-danger">*</span></label>
            <input type="text" name="nombre" class="form-control" required maxlength="120"
                   value="<?= htmlspecialchars($f['nombre'] ?? '') ?>">
        </div>
        <div class="col-md-6">
            <label class="form-label">Tipo</label>
            <select name="tipo" class="form-select">
                <option value="">— sin tipo —</option>
                <?php foreach (['civil','religioso','regional'] as $t): ?>
                    <option value="<?= $t ?>" <?= ($f['tipo'] ?? '') === $t ? 'selected' : '' ?>>
                        <?= ucfirst($t) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-6 d-flex align-items-end">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="irrenunciable" id="irre" value="1"
                       <?= !empty($f['irrenunciable']) ? 'checked' : '' ?>>
                <label class="form-check-label" for="irre">Feriado irrenunciable (Ley)</label>
            </div>
        </div>
    </div>

    <hr>
    <div class="d-flex justify-content-end gap-2">
        <a class="btn btn-outline-secondary" href="<?= site_url('obras/feriados') ?>">Cancelar</a>
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-save"></i> <?= $esEdicion ? 'Guardar cambios' : 'Crear feriado' ?>
        </button>
    </div>
</form>
