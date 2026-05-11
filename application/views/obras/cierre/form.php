<div class="d-flex align-items-center justify-content-between mb-3">
  <h4 class="mb-0"><i class="bi bi-pencil me-2"></i>Borrador de cierre · <?= e($proyecto['codigo']) ?></h4>
  <a class="btn btn-outline-secondary" href="<?= base_url("obras/cierre/{$proyecto['id']}") ?>"><i class="bi bi-arrow-left me-1"></i>Volver</a>
</div>

<form method="post" enctype="multipart/form-data"
      action="<?= base_url("obras/cierre/{$proyecto['id']}/editar") ?>" class="card shadow-sm">
  <?= form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()) ?>
  <div class="card-body">
    <div class="alert alert-info small">
      <i class="bi bi-info-circle me-1"></i>
      Estás creando/editando un <strong>borrador</strong>. Cuando esté completo, vuelve a la pantalla anterior y presiona "Cerrar obra" para formalizar el cierre. Para poder cerrar, todas las SDPs del proyecto deben estar Pagadas o Rechazadas.
    </div>
    <div class="row g-3">
      <div class="col-md-3">
        <label class="form-label">Fecha término real *</label>
        <input type="date" name="fecha_termino_real" class="form-control" required value="<?= e($cierre['fecha_termino_real'] ?? date('Y-m-d')) ?>">
      </div>
      <div class="col-12">
        <label class="form-label">Resumen ejecutivo *</label>
        <textarea name="resumen" rows="4" class="form-control" minlength="20" required placeholder="Describe el resumen del cierre: alcance ejecutado, hitos cumplidos, observaciones generales."><?= e($cierre['resumen'] ?? '') ?></textarea>
      </div>
      <div class="col-12">
        <label class="form-label">Conformidades</label>
        <textarea name="conformidades" rows="3" class="form-control" placeholder="Documenta conformidades del cliente o entrega de protocolos firmados."><?= e($cierre['conformidades'] ?? '') ?></textarea>
      </div>
      <div class="col-12">
        <label class="form-label">Observaciones</label>
        <textarea name="observaciones" rows="3" class="form-control" placeholder="Pendientes, garantías, ajustes pendientes, etc."><?= e($cierre['observaciones'] ?? '') ?></textarea>
      </div>
      <div class="col-12">
        <label class="form-label">Adjuntar acta de cierre / fotos / documentos</label>
        <input type="file" name="archivo" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
      </div>
    </div>
  </div>
  <div class="card-footer text-end">
    <button class="btn btn-primary"><i class="bi bi-check2 me-1"></i>Guardar borrador</button>
  </div>
</form>
