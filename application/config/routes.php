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
$route['maestros/tipos-cambio(:any)']  = 'maestros/tipos_cambio$1';

// ----- Finanzas: Solicitud de Pago -----
$route['finanzas/sdp']                   = 'finanzas/solicitud_pago/index';
$route['finanzas/sdp/exportar']          = 'finanzas/solicitud_pago/exportar';
$route['finanzas/sdp/crear']             = 'finanzas/solicitud_pago/crear';
$route['finanzas/sdp/(:num)']            = 'finanzas/solicitud_pago/ver/$1';
$route['finanzas/sdp/(:num)/editar']     = 'finanzas/solicitud_pago/editar/$1';
$route['finanzas/sdp/(:num)/validar']    = 'finanzas/solicitud_pago/validar/$1';
$route['finanzas/sdp/(:num)/programar']  = 'finanzas/solicitud_pago/programar/$1';
$route['finanzas/sdp/(:num)/pagar']      = 'finanzas/solicitud_pago/pagar/$1';
$route['finanzas/sdp/(:num)/rechazar']   = 'finanzas/solicitud_pago/rechazar/$1';
$route['finanzas/sdp/(:num)/eliminar']   = 'finanzas/solicitud_pago/eliminar/$1';

// ----- Compras / Recepciones -----
$route['compras/compras']                  = 'compras/compras/index';
$route['compras/compras/crear']            = 'compras/compras/crear';
$route['compras/compras/(:num)']           = 'compras/compras/ver/$1';
$route['compras/compras/(:num)/editar']    = 'compras/compras/editar/$1';
$route['compras/compras/(:num)/confirmar'] = 'compras/compras/confirmar/$1';
$route['compras/compras/(:num)/anular']    = 'compras/compras/anular/$1';

// ----- Rindes de Gastos -----
$route['compras/rindes']                   = 'compras/rindes/index';
$route['compras/rindes/crear']             = 'compras/rindes/crear';
$route['compras/rindes/(:num)']            = 'compras/rindes/ver/$1';
$route['compras/rindes/(:num)/editar']     = 'compras/rindes/editar/$1';
$route['compras/rindes/(:num)/enviar']     = 'compras/rindes/enviar/$1';
$route['compras/rindes/(:num)/aprobar']    = 'compras/rindes/aprobar/$1';
$route['compras/rindes/(:num)/rechazar']   = 'compras/rindes/rechazar/$1';
$route['compras/rindes/(:num)/generar-sdp']= 'compras/rindes/generar_sdp/$1';

// ----- Obras: Bitácora -----
$route['obras/bitacora']                  = 'obras/bitacora/index';
$route['obras/bitacora/crear']            = 'obras/bitacora/crear';
$route['obras/bitacora/(:num)']           = 'obras/bitacora/ver/$1';
$route['obras/bitacora/(:num)/editar']    = 'obras/bitacora/editar/$1';

// ----- Obras: Presupuesto -----
$route['obras/presupuesto']                       = 'obras/presupuesto/index';
$route['obras/presupuesto/crear']                 = 'obras/presupuesto/crear';
$route['obras/presupuesto/(:num)']                = 'obras/presupuesto/ver/$1';
$route['obras/presupuesto/(:num)/editar']         = 'obras/presupuesto/editar/$1';
$route['obras/presupuesto/(:num)/nueva-version']  = 'obras/presupuesto/nueva_version/$1';

// ----- Obras: Cierre -----
$route['obras/cierre']                  = 'obras/cierre/index';
$route['obras/cierre/(:num)']           = 'obras/cierre/ver/$1';
$route['obras/cierre/(:num)/editar']    = 'obras/cierre/editar/$1';
$route['obras/cierre/(:num)/cerrar']    = 'obras/cierre/cerrar/$1';
$route['obras/cierre/(:num)/pdf']       = 'obras/cierre/pdf/$1';

// ----- Obras: Gantt (Fase 2-C) -----
$route['obras/gantt']                          = 'obras/gantt/index';
$route['obras/gantt/data/(:num)']              = 'obras/gantt/data/$1';
$route['obras/gantt/pdf/(:num)']               = 'obras/gantt/pdf/$1';
$route['obras/gantt/reporte/(:num)']           = 'obras/gantt/reporte/$1';
$route['obras/gantt/recalcular-cpm/(:num)']    = 'obras/gantt/recalcular_cpm/$1';

$route['obras/dependencias/por-proyecto/(:num)'] = 'obras/dependencias/por_proyecto/$1';
$route['obras/dependencias/crear']               = 'obras/dependencias/crear';
$route['obras/dependencias/(:num)/eliminar']     = 'obras/dependencias/eliminar/$1';

$route['obras/hitos']                          = 'obras/hitos/index';
$route['obras/hitos/nuevo']                    = 'obras/hitos/nuevo';
$route['obras/hitos/crear']                    = 'obras/hitos/crear';
$route['obras/hitos/(:num)/editar']            = 'obras/hitos/editar/$1';
$route['obras/hitos/(:num)/actualizar']        = 'obras/hitos/actualizar/$1';
$route['obras/hitos/(:num)/eliminar']          = 'obras/hitos/eliminar/$1';

$route['obras/actividades']                    = 'obras/actividades/index';
$route['obras/actividades/nuevo']              = 'obras/actividades/nuevo';
$route['obras/actividades/crear']              = 'obras/actividades/crear';
$route['obras/actividades/(:num)/editar']      = 'obras/actividades/editar/$1';
$route['obras/actividades/(:num)/actualizar']  = 'obras/actividades/actualizar/$1';
$route['obras/actividades/(:num)/eliminar']    = 'obras/actividades/eliminar/$1';
$route['obras/actividades/(:num)/avance']      = 'obras/actividades/avance/$1';
$route['obras/actividades/(:num)/mover']       = 'obras/actividades/mover/$1';

$route['obras/feriados']                       = 'obras/feriados/index';
$route['obras/feriados/nuevo']                 = 'obras/feriados/nuevo';
$route['obras/feriados/crear']                 = 'obras/feriados/crear';
$route['obras/feriados/importar']              = 'obras/feriados/importar';
$route['obras/feriados/(:num)/editar']         = 'obras/feriados/editar/$1';
$route['obras/feriados/(:num)/actualizar']     = 'obras/feriados/actualizar/$1';
$route['obras/feriados/(:num)/eliminar']       = 'obras/feriados/eliminar/$1';

// ----- Reportes -----
$route['reportes/pagos']               = 'reportes/pagos/index';
$route['reportes/pagos/exportar']      = 'reportes/pagos/exportar';
$route['reportes/gastos']              = 'reportes/gastos/index';
$route['reportes/gastos/exportar']     = 'reportes/gastos/exportar';
$route['reportes/desviacion']          = 'reportes/desviacion/index';
$route['reportes/desviacion/exportar'] = 'reportes/desviacion/exportar';

// ----- Adjuntos (transversal) -----
$route['adjuntos/upload']              = 'adjuntos/upload';
$route['adjuntos/(:num)/descargar']    = 'adjuntos/descargar/$1';
$route['adjuntos/(:num)/eliminar']     = 'adjuntos/eliminar/$1';

// ----- API JSON (parcial, para selects async) -----
$route['api/comunas/(:num)']           = 'api/comunas/by_region/$1';
$route['api/proveedores/buscar']       = 'api/proveedores/buscar';
