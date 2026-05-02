-- =====================================================================
-- ERP GMC — Datos iniciales (seed)
-- Ejecutar después de erp_gmc.sql
-- Charset: utf8mb4_unicode_ci
--
-- Contiene:
--   1. Países / Regiones / Comunas de Chile (subset operativo)
--   2. Monedas (CLP, USD, UF, EUR)
--   3. Estados por dominio (proyecto, sdp, compra, rinde, cierre)
--   4. Roles, Permisos y matriz roles_permisos
--   5. Usuario admin (con bcrypt) y asignación de rol
--   6. Tipos de gasto base
--   7. Centro de costo "Administración" general
-- =====================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;


-- ---------------------------------------------------------------------
-- 1. GEOGRAFÍA: Chile + 16 regiones + comunas principales
-- ---------------------------------------------------------------------

INSERT INTO `gmc_paises` (`id`, `codigo_iso`, `nombre`) VALUES
  (1, 'CL', 'Chile');

INSERT INTO `gmc_regiones` (`id`, `pais_id`, `codigo`, `nombre`) VALUES
  (1,  1, 'XV',  'Arica y Parinacota'),
  (2,  1, 'I',   'Tarapacá'),
  (3,  1, 'II',  'Antofagasta'),
  (4,  1, 'III', 'Atacama'),
  (5,  1, 'IV',  'Coquimbo'),
  (6,  1, 'V',   'Valparaíso'),
  (7,  1, 'RM',  'Metropolitana de Santiago'),
  (8,  1, 'VI',  'O''Higgins'),
  (9,  1, 'VII', 'Maule'),
  (10, 1, 'XVI', 'Ñuble'),
  (11, 1, 'VIII','Biobío'),
  (12, 1, 'IX',  'La Araucanía'),
  (13, 1, 'XIV', 'Los Ríos'),
  (14, 1, 'X',   'Los Lagos'),
  (15, 1, 'XI',  'Aysén'),
  (16, 1, 'XII', 'Magallanes y Antártica Chilena');

-- Comunas RM (52) + capitales y comunas relevantes del resto del país
INSERT INTO `gmc_comunas` (`region_id`, `nombre`) VALUES
  -- Arica y Parinacota
  (1, 'Arica'), (1, 'Camarones'), (1, 'General Lagos'), (1, 'Putre'),
  -- Tarapacá
  (2, 'Iquique'), (2, 'Alto Hospicio'), (2, 'Pozo Almonte'),
  -- Antofagasta
  (3, 'Antofagasta'), (3, 'Calama'), (3, 'Mejillones'), (3, 'Tocopilla'), (3, 'Taltal'),
  -- Atacama
  (4, 'Copiapó'), (4, 'Caldera'), (4, 'Vallenar'), (4, 'Chañaral'), (4, 'Diego de Almagro'),
  -- Coquimbo
  (5, 'La Serena'), (5, 'Coquimbo'), (5, 'Ovalle'), (5, 'Illapel'), (5, 'Vicuña'),
  -- Valparaíso
  (6, 'Valparaíso'), (6, 'Viña del Mar'), (6, 'Concón'), (6, 'Quilpué'), (6, 'Villa Alemana'),
  (6, 'San Antonio'), (6, 'Quillota'), (6, 'San Felipe'), (6, 'Los Andes'), (6, 'Casablanca'),
  -- Metropolitana de Santiago (52)
  (7, 'Santiago'), (7, 'Cerrillos'), (7, 'Cerro Navia'), (7, 'Conchalí'), (7, 'El Bosque'),
  (7, 'Estación Central'), (7, 'Huechuraba'), (7, 'Independencia'), (7, 'La Cisterna'),
  (7, 'La Florida'), (7, 'La Granja'), (7, 'La Pintana'), (7, 'La Reina'), (7, 'Las Condes'),
  (7, 'Lo Barnechea'), (7, 'Lo Espejo'), (7, 'Lo Prado'), (7, 'Macul'), (7, 'Maipú'),
  (7, 'Ñuñoa'), (7, 'Pedro Aguirre Cerda'), (7, 'Peñalolén'), (7, 'Providencia'),
  (7, 'Pudahuel'), (7, 'Quilicura'), (7, 'Quinta Normal'), (7, 'Recoleta'), (7, 'Renca'),
  (7, 'San Joaquín'), (7, 'San Miguel'), (7, 'San Ramón'), (7, 'Vitacura'),
  (7, 'Puente Alto'), (7, 'Pirque'), (7, 'San José de Maipo'),
  (7, 'Colina'), (7, 'Lampa'), (7, 'Tiltil'),
  (7, 'San Bernardo'), (7, 'Buin'), (7, 'Calera de Tango'), (7, 'Paine'),
  (7, 'Melipilla'), (7, 'Alhué'), (7, 'Curacaví'), (7, 'María Pinto'), (7, 'San Pedro'),
  (7, 'Talagante'), (7, 'El Monte'), (7, 'Isla de Maipo'), (7, 'Padre Hurtado'), (7, 'Peñaflor'),
  -- O'Higgins
  (8, 'Rancagua'), (8, 'San Fernando'), (8, 'Rengo'), (8, 'Machalí'), (8, 'Santa Cruz'),
  -- Maule
  (9, 'Talca'), (9, 'Curicó'), (9, 'Linares'), (9, 'Cauquenes'), (9, 'Constitución'),
  -- Ñuble
  (10, 'Chillán'), (10, 'Chillán Viejo'), (10, 'Bulnes'), (10, 'Quirihue'),
  -- Biobío
  (11, 'Concepción'), (11, 'Talcahuano'), (11, 'Hualpén'), (11, 'San Pedro de la Paz'),
  (11, 'Chiguayante'), (11, 'Coronel'), (11, 'Lota'), (11, 'Los Ángeles'),
  -- La Araucanía
  (12, 'Temuco'), (12, 'Padre Las Casas'), (12, 'Villarrica'), (12, 'Pucón'), (12, 'Angol'),
  -- Los Ríos
  (13, 'Valdivia'), (13, 'La Unión'), (13, 'Río Bueno'), (13, 'Panguipulli'),
  -- Los Lagos
  (14, 'Puerto Montt'), (14, 'Puerto Varas'), (14, 'Osorno'), (14, 'Castro'), (14, 'Ancud'),
  -- Aysén
  (15, 'Coyhaique'), (15, 'Aysén'), (15, 'Cisnes'),
  -- Magallanes
  (16, 'Punta Arenas'), (16, 'Puerto Natales'), (16, 'Porvenir');


-- ---------------------------------------------------------------------
-- 2. MONEDAS
-- ---------------------------------------------------------------------

INSERT INTO `gmc_monedas` (`id`, `codigo`, `nombre`, `simbolo`, `decimales`, `activa`) VALUES
  (1, 'CLP', 'Peso chileno',  '$',   0, 1),
  (2, 'USD', 'Dólar EE.UU.',  'US$', 2, 1),
  (3, 'UF',  'Unidad de Fomento', 'UF', 4, 1),
  (4, 'EUR', 'Euro',          '€',   2, 1);


-- ---------------------------------------------------------------------
-- 3. ESTADOS por dominio
-- ---------------------------------------------------------------------

-- Proyecto
INSERT INTO `gmc_estados` (`dominio`, `codigo`, `nombre`, `color`, `orden`, `es_final`) VALUES
  ('proyecto', 'planificacion', 'En planificación', 'secondary', 1, 0),
  ('proyecto', 'en_ejecucion',  'En ejecución',     'primary',   2, 0),
  ('proyecto', 'pausado',       'Pausado',          'warning',   3, 0),
  ('proyecto', 'cerrado',       'Cerrado',          'success',   4, 1),
  ('proyecto', 'cancelado',     'Cancelado',        'danger',    9, 1);

-- Solicitud de Pago (orden y nombres alineados al documento funcional)
INSERT INTO `gmc_estados` (`dominio`, `codigo`, `nombre`, `color`, `orden`, `es_final`) VALUES
  ('solicitud_pago', 'pendiente',  'Pendiente',             'warning', 1, 0),
  ('solicitud_pago', 'validada',   'Validada por Finanzas', 'info',    2, 0),
  ('solicitud_pago', 'programada', 'Programada',            'primary', 3, 0),
  ('solicitud_pago', 'pagada',     'Pagada',                'success', 4, 1),
  ('solicitud_pago', 'rechazada',  'Rechazada',             'danger',  9, 1);

-- Compra
INSERT INTO `gmc_estados` (`dominio`, `codigo`, `nombre`, `color`, `orden`, `es_final`) VALUES
  ('compra', 'borrador', 'Borrador', 'secondary', 1, 0),
  ('compra', 'recibida', 'Recibida', 'success',   2, 1),
  ('compra', 'anulada',  'Anulada',  'danger',    9, 1);

-- Rinde de gastos
INSERT INTO `gmc_estados` (`dominio`, `codigo`, `nombre`, `color`, `orden`, `es_final`) VALUES
  ('rinde', 'borrador',  'Borrador',  'secondary', 1, 0),
  ('rinde', 'enviada',   'Enviada',   'warning',   2, 0),
  ('rinde', 'aprobada',  'Aprobada',  'info',      3, 0),
  ('rinde', 'rechazada', 'Rechazada', 'danger',    9, 1),
  ('rinde', 'pagada',    'Pagada',    'success',   4, 1);

-- Cierre de obra
INSERT INTO `gmc_estados` (`dominio`, `codigo`, `nombre`, `color`, `orden`, `es_final`) VALUES
  ('cierre', 'borrador', 'Borrador', 'secondary', 1, 0),
  ('cierre', 'cerrada',  'Cerrada',  'success',   2, 1);


-- ---------------------------------------------------------------------
-- 4. ROLES
-- ---------------------------------------------------------------------

INSERT INTO `gmc_roles` (`id`, `codigo`, `nombre`, `descripcion`) VALUES
  (1, 'admin',               'Administrador',         'Gestión de usuarios, roles y configuración del sistema.'),
  (2, 'gerencia',             'Gerencia',              'Acceso transversal con foco en reportes y dashboard.'),
  (3, 'finanzas',             'Finanzas',              'Validación, programación y pago de SDP. Exportes contables.'),
  (4, 'jefe_proyecto',        'Jefe de Proyecto',      'Lidera técnicamente la obra: crea SDP, bitácora, cierre y aprueba rindes de su proyecto.'),
  (5, 'administrador_obra',   'Administrador de Obra', 'Apoyo administrativo en una o varias obras: rindes, compras, bitácora administrativa.'),
  (6, 'bodega_obra',          'Bodega / Obra',         'Recepciones, compras y rinde de gastos en terreno.'),
  (7, 'contabilidad',         'Contabilidad (lectura)','Acceso de sólo lectura transversal y exportes.');


-- ---------------------------------------------------------------------
-- 5. PERMISOS (catálogo completo Fase 1)
-- ---------------------------------------------------------------------

INSERT INTO `gmc_permisos` (`codigo`, `descripcion`, `modulo`) VALUES
  -- Administración
  ('admin.usuario.ver',      'Ver usuarios',                         'admin'),
  ('admin.usuario.crear',    'Crear usuario',                        'admin'),
  ('admin.usuario.editar',   'Editar usuario',                       'admin'),
  ('admin.usuario.eliminar', 'Eliminar usuario (soft delete)',       'admin'),
  ('admin.rol.ver',          'Ver roles',                            'admin'),
  ('admin.rol.editar',       'Editar roles y permisos',              'admin'),
  ('admin.permiso.ver',      'Ver matriz de permisos',               'admin'),

  -- Maestros
  ('maestros.cliente.ver',     'Ver clientes',     'maestros'),
  ('maestros.cliente.crear',   'Crear cliente',    'maestros'),
  ('maestros.cliente.editar',  'Editar cliente',   'maestros'),
  ('maestros.cliente.eliminar','Eliminar cliente', 'maestros'),

  ('maestros.proveedor.ver',     'Ver proveedores',     'maestros'),
  ('maestros.proveedor.crear',   'Crear proveedor',     'maestros'),
  ('maestros.proveedor.editar',  'Editar proveedor',    'maestros'),
  ('maestros.proveedor.eliminar','Eliminar proveedor',  'maestros'),

  ('maestros.proyecto.ver',     'Ver proyectos',           'maestros'),
  ('maestros.proyecto.crear',   'Crear proyecto',          'maestros'),
  ('maestros.proyecto.editar',  'Editar proyecto',         'maestros'),
  ('maestros.proyecto.eliminar','Eliminar proyecto',       'maestros'),

  ('maestros.cc.ver',     'Ver centros de costo',    'maestros'),
  ('maestros.cc.crear',   'Crear centro de costo',   'maestros'),
  ('maestros.cc.editar',  'Editar centro de costo',  'maestros'),
  ('maestros.cc.eliminar','Eliminar centro de costo','maestros'),

  ('maestros.tipo_gasto.ver',     'Ver tipos de gasto',    'maestros'),
  ('maestros.tipo_gasto.editar',  'Editar tipos de gasto', 'maestros'),

  -- Finanzas / SDP
  ('finanzas.sdp.ver',        'Ver Solicitudes de Pago',           'finanzas'),
  ('finanzas.sdp.crear',      'Crear Solicitud de Pago',           'finanzas'),
  ('finanzas.sdp.editar',     'Editar SDP en estado Pendiente',    'finanzas'),
  ('finanzas.sdp.eliminar',   'Eliminar SDP (soft delete)',        'finanzas'),
  ('finanzas.sdp.validar',    'Validar SDP (Pendiente -> Validada)','finanzas'),
  ('finanzas.sdp.programar',  'Programar SDP (Validada -> Programada)','finanzas'),
  ('finanzas.sdp.pagar',      'Pagar SDP (Programada -> Pagada)',  'finanzas'),
  ('finanzas.sdp.rechazar',   'Rechazar SDP con motivo',           'finanzas'),
  ('finanzas.sdp.exportar',   'Exportar SDP a CSV/Excel',          'finanzas'),

  -- Compras
  ('compras.compra.ver',     'Ver compras/recepciones',  'compras'),
  ('compras.compra.crear',   'Registrar compra',         'compras'),
  ('compras.compra.editar',  'Editar compra borrador',   'compras'),
  ('compras.compra.anular',  'Anular compra',            'compras'),

  ('compras.rinde.ver',     'Ver rindes de gastos',  'compras'),
  ('compras.rinde.crear',   'Crear rinde',           'compras'),
  ('compras.rinde.editar',  'Editar rinde borrador', 'compras'),
  ('compras.rinde.enviar',  'Enviar rinde',          'compras'),
  ('compras.rinde.aprobar', 'Aprobar rinde',         'compras'),
  ('compras.rinde.rechazar','Rechazar rinde',        'compras'),

  -- Obras
  ('obras.bitacora.ver',    'Ver bitácora',     'obras'),
  ('obras.bitacora.crear',  'Crear entrada bitácora','obras'),
  ('obras.bitacora.editar', 'Editar bitácora',  'obras'),

  ('obras.cierre.ver',     'Ver cierres de obra', 'obras'),
  ('obras.cierre.crear',   'Crear cierre',         'obras'),
  ('obras.cierre.cerrar',  'Cerrar obra',          'obras'),

  ('obras.presupuesto.ver',    'Ver presupuesto inicial', 'obras'),
  ('obras.presupuesto.editar', 'Editar presupuesto inicial','obras'),

  -- Reportes
  ('reportes.pagos.ver',         'Ver reporte de estado de pagos',           'reportes'),
  ('reportes.pagos.exportar',    'Exportar reporte de pagos',                'reportes'),
  ('reportes.gastos.ver',        'Ver reporte de gastos por obra/CC',        'reportes'),
  ('reportes.gastos.exportar',   'Exportar reporte de gastos',               'reportes'),
  ('reportes.desviacion.ver',    'Ver reporte de desviación',                'reportes'),
  ('reportes.desviacion.exportar','Exportar reporte de desviación',          'reportes'),

  -- Auditoría
  ('audit.log.ver', 'Ver log de auditoría', 'admin');


-- ---------------------------------------------------------------------
-- 6. MATRIZ ROLES_PERMISOS
-- ---------------------------------------------------------------------

-- ADMIN: todo
INSERT INTO `gmc_roles_permisos` (`rol_id`, `permiso_id`)
SELECT 1, p.id FROM `gmc_permisos` p;

-- GERENCIA: ve todo, edita maestros, ve auditoría, exporta reportes
INSERT INTO `gmc_roles_permisos` (`rol_id`, `permiso_id`)
SELECT 2, p.id FROM `gmc_permisos` p
WHERE p.codigo IN (
  'maestros.cliente.ver','maestros.cliente.crear','maestros.cliente.editar',
  'maestros.proveedor.ver','maestros.proveedor.crear','maestros.proveedor.editar',
  'maestros.proyecto.ver','maestros.proyecto.crear','maestros.proyecto.editar',
  'maestros.cc.ver','maestros.cc.crear','maestros.cc.editar',
  'maestros.tipo_gasto.ver','maestros.tipo_gasto.editar',
  'finanzas.sdp.ver','finanzas.sdp.exportar',
  'compras.compra.ver','compras.rinde.ver',
  'obras.bitacora.ver','obras.cierre.ver','obras.presupuesto.ver','obras.presupuesto.editar',
  'reportes.pagos.ver','reportes.pagos.exportar',
  'reportes.gastos.ver','reportes.gastos.exportar',
  'reportes.desviacion.ver','reportes.desviacion.exportar',
  'audit.log.ver'
);

-- FINANZAS: SDP completo + maestros lectura + reportes
INSERT INTO `gmc_roles_permisos` (`rol_id`, `permiso_id`)
SELECT 3, p.id FROM `gmc_permisos` p
WHERE p.codigo IN (
  'maestros.cliente.ver','maestros.proveedor.ver','maestros.proyecto.ver',
  'maestros.cc.ver','maestros.tipo_gasto.ver',
  'finanzas.sdp.ver','finanzas.sdp.crear','finanzas.sdp.editar',
  'finanzas.sdp.validar','finanzas.sdp.programar','finanzas.sdp.pagar',
  'finanzas.sdp.rechazar','finanzas.sdp.exportar','finanzas.sdp.eliminar',
  'compras.compra.ver','compras.rinde.ver',
  'reportes.pagos.ver','reportes.pagos.exportar',
  'reportes.gastos.ver','reportes.gastos.exportar',
  'reportes.desviacion.ver','reportes.desviacion.exportar'
);

-- JEFE PROYECTO: gestiona sus proyectos, crea SDP, bitácora, cierre, aprueba rindes de su obra
INSERT INTO `gmc_roles_permisos` (`rol_id`, `permiso_id`)
SELECT 4, p.id FROM `gmc_permisos` p
WHERE p.codigo IN (
  'maestros.cliente.ver','maestros.proveedor.ver',
  'maestros.proyecto.ver','maestros.proyecto.editar',
  'maestros.cc.ver','maestros.cc.crear','maestros.cc.editar',
  'maestros.tipo_gasto.ver',
  'finanzas.sdp.ver','finanzas.sdp.crear','finanzas.sdp.editar',
  'compras.compra.ver',
  'compras.rinde.ver','compras.rinde.aprobar','compras.rinde.rechazar',
  'obras.bitacora.ver','obras.bitacora.crear','obras.bitacora.editar',
  'obras.cierre.ver','obras.cierre.crear','obras.cierre.cerrar',
  'obras.presupuesto.ver','obras.presupuesto.editar',
  'reportes.pagos.ver','reportes.gastos.ver','reportes.desviacion.ver'
);

-- ADMINISTRADOR DE OBRA: apoyo administrativo en una o varias obras
-- (puede crear/editar rindes y compras del/los proyectos asignados,
-- llevar bitácora administrativa; NO aprueba rindes ni cierra obra,
-- NO edita presupuesto)
INSERT INTO `gmc_roles_permisos` (`rol_id`, `permiso_id`)
SELECT 5, p.id FROM `gmc_permisos` p
WHERE p.codigo IN (
  'maestros.cliente.ver','maestros.proveedor.ver',
  'maestros.proyecto.ver',
  'maestros.cc.ver','maestros.tipo_gasto.ver',
  'finanzas.sdp.ver','finanzas.sdp.crear','finanzas.sdp.editar',
  'compras.compra.ver','compras.compra.crear','compras.compra.editar',
  'compras.rinde.ver','compras.rinde.crear','compras.rinde.editar','compras.rinde.enviar',
  'obras.bitacora.ver','obras.bitacora.crear','obras.bitacora.editar',
  'obras.presupuesto.ver',
  'reportes.pagos.ver','reportes.gastos.ver'
);

-- BODEGA / OBRA: compras, rindes, bitácora (en terreno)
INSERT INTO `gmc_roles_permisos` (`rol_id`, `permiso_id`)
SELECT 6, p.id FROM `gmc_permisos` p
WHERE p.codigo IN (
  'maestros.proveedor.ver','maestros.proyecto.ver','maestros.cc.ver','maestros.tipo_gasto.ver',
  'compras.compra.ver','compras.compra.crear','compras.compra.editar','compras.compra.anular',
  'compras.rinde.ver','compras.rinde.crear','compras.rinde.editar','compras.rinde.enviar',
  'obras.bitacora.ver','obras.bitacora.crear'
);

-- CONTABILIDAD: sólo lectura transversal + exportes
INSERT INTO `gmc_roles_permisos` (`rol_id`, `permiso_id`)
SELECT 7, p.id FROM `gmc_permisos` p
WHERE p.codigo IN (
  'maestros.cliente.ver','maestros.proveedor.ver','maestros.proyecto.ver',
  'maestros.cc.ver','maestros.tipo_gasto.ver',
  'finanzas.sdp.ver','finanzas.sdp.exportar',
  'compras.compra.ver','compras.rinde.ver',
  'obras.bitacora.ver','obras.cierre.ver','obras.presupuesto.ver',
  'reportes.pagos.ver','reportes.pagos.exportar',
  'reportes.gastos.ver','reportes.gastos.exportar',
  'reportes.desviacion.ver','reportes.desviacion.exportar'
);


-- ---------------------------------------------------------------------
-- 7. USUARIO ADMIN INICIAL
-- ---------------------------------------------------------------------
-- RUT: 11111111-1
-- Email: admin@gmc.cl
-- Password: GMC.2026!  (bcrypt cost 12)
-- IMPORTANTE: el flag force_password_change obliga a cambiarla en el primer login.
-- ---------------------------------------------------------------------

INSERT INTO `gmc_usuarios`
  (`id`, `rut`, `nombres`, `apellidos`, `email`, `telefono`,
   `password_hash`, `password_changed_at`, `force_password_change`,
   `activo`, `created_by`, `updated_by`)
VALUES
  (1, '11111111-1', 'Administrador', 'GMC', 'admin@gmc.cl', NULL,
   '$2y$12$sqKBjuPT./fvnXrEvJzlcOVfY81orgTE7FyqAoaKjWhz0UIO13kPm',
   NULL, 1, 1, NULL, NULL);

INSERT INTO `gmc_usuarios_roles` (`usuario_id`, `rol_id`) VALUES (1, 1);


-- ---------------------------------------------------------------------
-- 8. TIPOS DE GASTO (catálogo inicial)
-- ---------------------------------------------------------------------

INSERT INTO `gmc_tipos_gasto` (`codigo`, `nombre`, `activo`) VALUES
  ('MAT',   'Materiales',                  1),
  ('SUBC',  'Subcontratos',                1),
  ('COMB',  'Combustibles',                1),
  ('ARR',   'Arriendos (equipos/vehículos)', 1),
  ('SERV',  'Servicios básicos',           1),
  ('HON',   'Honorarios',                  1),
  ('SUEL',  'Sueldos y leyes sociales',    1),
  ('TRANS', 'Transporte y logística',      1),
  ('IMP',   'Impuestos y permisos',        1),
  ('SEG',   'Seguros',                     1),
  ('VIA',   'Viáticos',                    1),
  ('OTRO',  'Otros gastos',                1);


-- ---------------------------------------------------------------------
-- 9. CENTRO DE COSTO "ADMINISTRACIÓN" (sin proyecto)
-- ---------------------------------------------------------------------

INSERT INTO `gmc_centros_costo`
  (`proyecto_id`, `codigo`, `nombre`, `es_administracion`, `activo`, `created_by`)
VALUES
  (NULL, 'ADM', 'Administración', 1, 1, 1);


-- ---------------------------------------------------------------------
-- 10. CORRELATIVOS (vacíos al inicio; se autocrean al primer uso)
-- ---------------------------------------------------------------------

INSERT INTO `gmc_correlativos` (`dominio`, `anio`, `ultimo_numero`) VALUES
  ('proyecto',       YEAR(CURDATE()), 0),
  ('solicitud_pago', YEAR(CURDATE()), 0),
  ('compra',         YEAR(CURDATE()), 0),
  ('rinde',          YEAR(CURDATE()), 0),
  ('bitacora',       YEAR(CURDATE()), 0);


SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================================
-- LISTO
-- Credenciales del primer admin:
--   rut:       11111111-1
--   email:     admin@gmc.cl
--   password:  GMC.2026!     (debe cambiarla al primer login)
-- =====================================================================
