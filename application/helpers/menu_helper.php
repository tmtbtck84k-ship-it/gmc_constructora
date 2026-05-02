<?php
defined('BASEPATH') OR exit('No direct script access allowed');

if (!function_exists('can')) {
    /**
     * Helper para usar en vistas: muestra botones/acciones según permiso.
     *
     *   <?php if (can('finanzas.sdp.crear')): ?>
     *     <a class="btn btn-primary" href="...">Nueva SDP</a>
     *   <?php endif; ?>
     */
    function can(string $codigo): bool
    {
        $CI =& get_instance();
        $uid = (int) $CI->session->userdata('user_id');
        if (!$uid) return false;
        return $CI->acl->can($codigo, $uid);
    }
}

if (!function_exists('menu_items')) {
    /**
     * Devuelve el menú lateral filtrado por permisos del usuario actual.
     *
     * Cada ítem: ['label','icon','url','perm']
     */
    function menu_items(): array
    {
        $items = [
            ['label'=>'Dashboard',         'icon'=>'speedometer2', 'url'=>'dashboard',                'perm'=>null],

            ['_section'=>'Maestros'],
            ['label'=>'Clientes',          'icon'=>'building',     'url'=>'maestros/clientes',        'perm'=>'maestros.cliente.ver'],
            ['label'=>'Proveedores',       'icon'=>'truck',        'url'=>'maestros/proveedores',     'perm'=>'maestros.proveedor.ver'],
            ['label'=>'Proyectos',         'icon'=>'kanban',       'url'=>'maestros/proyectos',       'perm'=>'maestros.proyecto.ver'],
            ['label'=>'Centros de Costo',  'icon'=>'tag',          'url'=>'maestros/centros-costo',   'perm'=>'maestros.cc.ver'],
            ['label'=>'Tipos de Gasto',    'icon'=>'list-ul',      'url'=>'maestros/tipos-gasto',     'perm'=>'maestros.tipo_gasto.ver'],

            ['_section'=>'Finanzas'],
            ['label'=>'Solicitudes de Pago','icon'=>'cash-coin',   'url'=>'finanzas/sdp',             'perm'=>'finanzas.sdp.ver'],

            ['_section'=>'Compras'],
            ['label'=>'Compras',           'icon'=>'cart',         'url'=>'compras/compras',          'perm'=>'compras.compra.ver'],
            ['label'=>'Rindes de Gastos',  'icon'=>'receipt',      'url'=>'compras/rindes',           'perm'=>'compras.rinde.ver'],

            ['_section'=>'Obras'],
            ['label'=>'Bitácora',          'icon'=>'journal-text', 'url'=>'obras/bitacora',           'perm'=>'obras.bitacora.ver'],
            ['label'=>'Cierre de Obra',    'icon'=>'check2-square','url'=>'obras/cierre',             'perm'=>'obras.cierre.ver'],
            ['label'=>'Presupuesto inicial','icon'=>'calculator',  'url'=>'obras/presupuesto',        'perm'=>'obras.presupuesto.ver'],

            ['_section'=>'Reportes'],
            ['label'=>'Estado de Pagos',   'icon'=>'graph-up',     'url'=>'reportes/pagos',           'perm'=>'reportes.pagos.ver'],
            ['label'=>'Gastos por Obra',   'icon'=>'graph-up-arrow','url'=>'reportes/gastos',         'perm'=>'reportes.gastos.ver'],
            ['label'=>'Desviación',        'icon'=>'bar-chart',    'url'=>'reportes/desviacion',      'perm'=>'reportes.desviacion.ver'],

            ['_section'=>'Administración'],
            ['label'=>'Usuarios',          'icon'=>'people',       'url'=>'admin/usuarios',           'perm'=>'admin.usuario.ver'],
            ['label'=>'Roles y Permisos',  'icon'=>'shield-lock',  'url'=>'admin/roles',              'perm'=>'admin.rol.ver'],
            ['label'=>'Auditoría',         'icon'=>'clipboard-data','url'=>'admin/auditoria',         'perm'=>'audit.log.ver'],
        ];

        $filtered = [];
        $lastSection = null;
        foreach ($items as $it) {
            if (isset($it['_section'])) { $lastSection = $it; continue; }
            if (!empty($it['perm']) && !can($it['perm'])) continue;
            if ($lastSection !== null) { $filtered[] = $lastSection; $lastSection = null; }
            $filtered[] = $it;
        }
        return $filtered;
    }
}

if (!function_exists('is_active_url')) {
    /**
     * Compara URI actual contra patrón de URL para activar el item del sidebar.
     */
    function is_active_url(string $url): bool
    {
        $CI =& get_instance();
        $current = trim((string)$CI->uri->uri_string(), '/');
        $url = trim($url, '/');
        if ($current === $url) return true;
        return strpos($current, $url . '/') === 0;
    }
}
