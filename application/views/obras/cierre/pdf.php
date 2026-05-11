<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Cierre de obra · <?= htmlspecialchars($proyecto['codigo']) ?></title>
<style>
  body { font-family: 'Helvetica', Arial, sans-serif; font-size: 11pt; color: #222; }
  h1 { font-size: 18pt; color: #0d6efd; margin: 0 0 4pt 0; }
  h2 { font-size: 13pt; color: #0d6efd; border-bottom: 1px solid #d0d0d0; padding-bottom: 4pt; margin-top: 22pt; }
  h3 { font-size: 11pt; color: #444; margin-top: 14pt; }
  .small { font-size: 9pt; color: #777; }
  table { width: 100%; border-collapse: collapse; margin-top: 8pt; }
  th, td { padding: 5pt 6pt; border-bottom: 1px solid #e5e5e5; }
  th { background: #f4f6f9; text-align: left; font-size: 9pt; text-transform: uppercase; color: #555; }
  .text-end { text-align: right; }
  .text-center { text-align: center; }
  .badge { display: inline-block; padding: 2pt 6pt; border-radius: 3pt; font-size: 9pt; background: #e3effa; color: #0d6efd; }
  .header { border-bottom: 2pt solid #0d6efd; padding-bottom: 8pt; }
  .header td { border: none; padding: 0; }
  .resumen { background: #f9fafc; padding: 10pt; border-left: 3pt solid #0d6efd; margin-top: 6pt; }
  .footer { position: fixed; bottom: 0; left: 0; right: 0; text-align: center; font-size: 8pt; color: #999; }
</style>
</head>
<body>

<table class="header">
  <tr>
    <td>
      <h1><?= htmlspecialchars($company ?? 'GMC') ?></h1>
      <div class="small">Informe de Cierre de Obra</div>
    </td>
    <td class="text-end small">
      Generado: <?= date('d-m-Y H:i') ?><br>
      Documento interno
    </td>
  </tr>
</table>

<h2>Identificación del proyecto</h2>
<table>
  <tr><th style="width:30%">Código</th><td><?= htmlspecialchars($proyecto['codigo']) ?></td></tr>
  <tr><th>Nombre</th><td><?= htmlspecialchars($proyecto['nombre']) ?></td></tr>
  <tr><th>Cliente</th><td><?= htmlspecialchars($proyecto['cliente'] ?? '') ?></td></tr>
  <tr><th>Dirección</th><td><?= htmlspecialchars($proyecto['direccion'] ?? '—') ?></td></tr>
  <tr><th>Fecha inicio</th><td><?= htmlspecialchars(format_date($proyecto['fecha_inicio'])) ?: '—' ?></td></tr>
  <tr><th>Fecha término estimada</th><td><?= htmlspecialchars(format_date($proyecto['fecha_termino_estimada'])) ?: '—' ?></td></tr>
  <tr><th>Fecha término real</th><td><strong><?= htmlspecialchars(format_date($cierre['fecha_termino_real'])) ?></strong></td></tr>
  <tr><th>Estado del cierre</th><td><span class="badge"><?= htmlspecialchars($cierre['estado_nombre']) ?></span></td></tr>
</table>

<h2>Resumen ejecutivo</h2>
<div class="resumen"><?= nl2br(htmlspecialchars($cierre['resumen'])) ?></div>

<?php if (!empty($cierre['conformidades'])): ?>
  <h2>Conformidades</h2>
  <p><?= nl2br(htmlspecialchars($cierre['conformidades'])) ?></p>
<?php endif; ?>

<?php if (!empty($cierre['observaciones'])): ?>
  <h2>Observaciones</h2>
  <p><?= nl2br(htmlspecialchars($cierre['observaciones'])) ?></p>
<?php endif; ?>

<?php if (!empty($presupuesto)): ?>
  <h2>Presupuesto inicial vigente (v<?= (int)$presupuesto['version'] ?>)</h2>
  <p class="small">Moneda: <?= htmlspecialchars($presupuesto['moneda_id'] ?? '') ?> · Total presupuestado:
    <strong>$ <?= number_format((float)$presupuesto['monto_total'], 0, ',', '.') ?></strong>
  </p>
  <?php if (!empty($pres_items)): ?>
    <table>
      <thead><tr><th>Centro de costo</th><th>Tipo gasto</th><th>Descripción</th><th class="text-end">Monto</th></tr></thead>
      <tbody>
      <?php foreach ($pres_items as $pi): ?>
        <tr>
          <td><?= htmlspecialchars($pi['cc_codigo']) ?></td>
          <td><?= htmlspecialchars($pi['tg_codigo']) ?></td>
          <td><?= htmlspecialchars($pi['descripcion']) ?></td>
          <td class="text-end">$ <?= number_format((float)$pi['monto'], 0, ',', '.') ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
<?php endif; ?>

<h2>Bitácora de obra (<?= count($bitacoras) ?> entradas)</h2>
<?php if (!$bitacoras): ?>
  <p class="small">Sin entradas registradas.</p>
<?php else: ?>
  <table>
    <thead><tr><th style="width:14%">Fecha</th><th style="width:14%">Tipo</th><th>Título / detalle</th></tr></thead>
    <tbody>
    <?php foreach ($bitacoras as $b): ?>
      <tr>
        <td><?= htmlspecialchars(format_date($b['fecha_evento'])) ?></td>
        <td><?= htmlspecialchars(ucfirst($b['tipo_evento'])) ?></td>
        <td>
          <strong><?= htmlspecialchars($b['titulo']) ?></strong>
          <div class="small"><?= nl2br(htmlspecialchars(mb_strimwidth($b['detalle'], 0, 200, '…'))) ?></div>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
<?php endif; ?>

<div class="footer">
  Cierre de obra · <?= htmlspecialchars($proyecto['codigo']) ?> · Generado el <?= date('d-m-Y H:i') ?>
</div>

</body>
</html>
