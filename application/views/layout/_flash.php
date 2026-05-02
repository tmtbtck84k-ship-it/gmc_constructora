<?php
$map = [
    'success' => ['alert-success', 'check-circle-fill'],
    'error'   => ['alert-danger',  'exclamation-triangle-fill'],
    'warning' => ['alert-warning', 'exclamation-circle-fill'],
    'info'    => ['alert-info',    'info-circle-fill'],
];
foreach (($_flash ?? []) as $type => $msg):
    if (!$msg) continue;
    [$cls, $icon] = $map[$type] ?? ['alert-secondary', 'info-circle'];
?>
  <div class="alert <?= $cls ?> alert-dismissible fade show" role="alert">
    <i class="bi bi-<?= $icon ?> me-2"></i><?= e($msg) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
  </div>
<?php endforeach; ?>
