/* GMC ERP — JS común */

(function ($) {
  'use strict';

  $(function () {
    // Toggle sidebar mobile
    $('#sidebarToggle').on('click', function () { $('#sidebar').addClass('show'); });
    $('#sidebarClose').on('click', function () { $('#sidebar').removeClass('show'); });

    // Inicializar Select2 con tema Bootstrap
    if ($.fn.select2) {
      $('select.select2').select2({ theme: 'bootstrap-5', width: '100%' });
    }

    // Inicializar DataTables con español
    if ($.fn.DataTable) {
      $('table.datatable').DataTable({
        language: { url: '/assets/vendor/datatables/es-CL.json' },
        pageLength: 25,
        order: [[0, 'desc']],
      });
    }

    // Confirmación con SweetAlert2 para botones .js-confirm
    $(document).on('click', '.js-confirm', function (e) {
      var $btn = $(this);
      var msg  = $btn.data('confirm') || '¿Confirmas la acción?';
      if ($btn.data('confirmed')) return;
      e.preventDefault();
      Swal.fire({
        title: msg,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, continuar',
        cancelButtonText: 'Cancelar',
      }).then(function (r) {
        if (r.isConfirmed) {
          $btn.data('confirmed', true);
          if ($btn.is('a'))      window.location = $btn.attr('href');
          else if ($btn.is('button')) $btn.closest('form').trigger('submit');
        }
      });
    });

    // Formateador automático de RUT en inputs name=rut
    $('input[name="rut"]').on('blur', function () {
      var v = ($(this).val() || '').replace(/[^0-9kK]/g, '');
      if (v.length < 2) return;
      var dv = v.slice(-1).toUpperCase();
      var num = v.slice(0, -1).replace(/^0+/, '') || '0';
      var withDots = num.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
      $(this).val(withDots + '-' + dv);
    });
  });
})(jQuery);
