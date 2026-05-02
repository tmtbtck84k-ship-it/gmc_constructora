<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
<title><?= e($_company) ?> · ERP</title>
<link rel="icon" href="<?= base_url('assets/img/favicon.png') ?>" type="image/png">

<link rel="stylesheet" href="<?= base_url('assets/vendor/bootstrap/css/bootstrap.min.css') ?>">
<link rel="stylesheet" href="<?= base_url('assets/vendor/bootstrap-icons/bootstrap-icons.css') ?>">
<link rel="stylesheet" href="<?= base_url('assets/vendor/datatables/dataTables.bootstrap5.min.css') ?>">
<link rel="stylesheet" href="<?= base_url('assets/vendor/select2/select2.min.css') ?>">
<link rel="stylesheet" href="<?= base_url('assets/vendor/select2/select2-bootstrap-5.min.css') ?>">
<link rel="stylesheet" href="<?= base_url('assets/vendor/sweetalert2/sweetalert2.min.css') ?>">
<link rel="stylesheet" href="<?= base_url('assets/css/app.css') ?>">
</head>
<body>
<div class="d-flex" id="wrapper">
  <?php $this->load->view('layout/_sidebar'); ?>

  <div id="page-content" class="flex-grow-1">
    <?php $this->load->view('layout/_navbar'); ?>

    <main class="container-fluid py-4">
      <?php $this->load->view('layout/_flash'); ?>
      <?php $this->load->view($_main_view); ?>
    </main>

    <?php $this->load->view('layout/_footer'); ?>
  </div>
</div>

<script src="<?= base_url('assets/vendor/jquery/jquery-3.7.1.min.js') ?>"></script>
<script src="<?= base_url('assets/vendor/bootstrap/js/bootstrap.bundle.min.js') ?>"></script>
<script src="<?= base_url('assets/vendor/datatables/jquery.dataTables.min.js') ?>"></script>
<script src="<?= base_url('assets/vendor/datatables/dataTables.bootstrap5.min.js') ?>"></script>
<script src="<?= base_url('assets/vendor/select2/select2.min.js') ?>"></script>
<script src="<?= base_url('assets/vendor/sweetalert2/sweetalert2.all.min.js') ?>"></script>
<script src="<?= base_url('assets/js/app.js') ?>"></script>
</body>
</html>
