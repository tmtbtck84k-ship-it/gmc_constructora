<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="h4 mb-0"><i class="bi bi-upload"></i> Importar feriados desde CSV</h2>
    <a class="btn btn-link" href="<?= site_url('obras/feriados') ?>">
        <i class="bi bi-arrow-left"></i> Volver
    </a>
</div>

<form method="post" enctype="multipart/form-data"
      action="<?= site_url('obras/feriados/importar') ?>"
      class="card card-body" style="max-width: 720px;">
    <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>"
           value="<?= $this->security->get_csrf_hash() ?>">

    <p class="text-muted">
        El archivo CSV debe tener encabezado en la primera línea con las columnas:
        <code>fecha</code>, <code>nombre</code>, <code>irrenunciable</code> (1/0), <code>tipo</code> (opcional).
        Si una fecha ya existe se actualizará; si no, se creará.
    </p>

    <div class="mb-3">
        <label class="form-label">Archivo CSV</label>
        <input type="file" name="archivo" class="form-control" accept=".csv,text/csv" required>
    </div>

    <div class="alert alert-light border">
        <strong>Ejemplo:</strong>
        <pre class="mb-0">fecha,nombre,irrenunciable,tipo
2027-01-01,Año Nuevo,1,civil
2027-04-02,Viernes Santo,0,religioso</pre>
    </div>

    <div class="d-flex justify-content-end gap-2">
        <a class="btn btn-outline-secondary" href="<?= site_url('obras/feriados') ?>">Cancelar</a>
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-upload"></i> Importar
        </button>
    </div>
</form>
