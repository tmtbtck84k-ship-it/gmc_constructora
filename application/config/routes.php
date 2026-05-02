<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$route['default_controller'] = 'auth/login';
$route['404_override']       = '';
$route['translate_uri_dashes'] = TRUE;

// ----- Auth -----
$route['login']                  = 'auth/login/index';
$route['login/submit']           = 'auth/login/submit';
$route['logout']                 = 'auth/logout';
$route['password/forgot']        = 'auth/password/forgot';
$route['password/forgot/submit'] = 'auth/password/forgot_submit';
$route['password/reset']         = 'auth/password/reset';
$route['password/reset/submit']  = 'auth/password/reset_submit';
$route['password/change']        = 'auth/password/change';
$route['password/change/submit'] = 'auth/password/change_submit';

// ----- Dashboard -----
$route['dashboard']              = 'dashboard/index';
$route['']                       = 'auth/login/index';

// ----- Admin -----
$route['admin/usuarios']                  = 'admin/usuarios/index';
$route['admin/usuarios/crear']            = 'admin/usuarios/crear';
$route['admin/usuarios/(:num)']           = 'admin/usuarios/ver/$1';
$route['admin/usuarios/(:num)/editar']    = 'admin/usuarios/editar/$1';
$route['admin/usuarios/(:num)/eliminar']  = 'admin/usuarios/eliminar/$1';
$route['admin/usuarios/(:num)/reset']     = 'admin/usuarios/reset_password/$1';

$route['admin/roles']                     = 'admin/roles/index';
$route['admin/roles/(:num)/permisos']     = 'admin/roles/permisos/$1';

$route['admin/auditoria']                 = 'admin/auditoria/index';

// ----- Maestros -----
$route['maestros/clientes(:any)']      = 'maestros/clientes$1';
$route['maestros/proveedores(:any)']   = 'maestros/proveedores$1';
$route['maestros/proyectos(:any)']     = 'maestros/proyectos$1';
$route['maestros/centros-costo(:any)'] = 'maestros/centros_costo$1';
$route['maestros/tipos-gasto(:any)']   = 'maestros/tipos_gasto$1';

// ----- Finanzas (Sprint 2) -----
$route['finanzas/sdp']                 = 'finanzas/solicitud_pago/index';
$route['finanzas/sdp/crear']           = 'finanzas/solicitud_pago/crear';
$route['finanzas/sdp/(:num)']          = 'finanzas/solicitud_pago/ver/$1';
$route['finanzas/sdp/(:num)/editar']   = 'finanzas/solicitud_pago/editar/$1';
$route['finanzas/sdp/(:num)/(:any)']   = 'finanzas/solicitud_pago/$2/$1';

// ----- Compras (Sprint 3) -----
$route['compras/compras(:any)']        = 'compras/compras$1';
$route['compras/rindes(:any)']         = 'compras/rindes$1';

// ----- Obras (Sprint 4) -----
$route['obras/(:any)']                 = 'obras/$1';

// ----- Reportes (Sprint 5) -----
$route['reportes/(:any)']              = 'reportes/$1';

// ----- Adjuntos (transversal) -----
$route['adjuntos/upload']              = 'adjuntos/upload';
$route['adjuntos/(:num)/descargar']    = 'adjuntos/descargar/$1';
$route['adjuntos/(:num)/eliminar']     = 'adjuntos/eliminar/$1';

// ----- API JSON (parcial, para selects async) -----
$route['api/comunas/(:num)']           = 'api/comunas/by_region/$1';
$route['api/proveedores/buscar']       = 'api/proveedores/buscar';
