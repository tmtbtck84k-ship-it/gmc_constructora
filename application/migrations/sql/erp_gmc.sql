-- =====================================================================
-- ERP GMC — Modelo de datos Fase 1
-- Versión: 1.0.0
-- Motor: InnoDB | Charset: utf8mb4 | Collation: utf8mb4_unicode_ci
-- Convenciones: snake_case, prefijo gmc_, PK BIGINT UNSIGNED AI,
--               soft-delete (deleted_at), auditoría (created_by/updated_by),
--               dinero DECIMAL(14,2), FK explícitas con políticas claras.
-- =====================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'STRICT_ALL_TABLES,NO_ENGINE_SUBSTITUTION';

-- ---------------------------------------------------------------------
-- BD (opcional - el script asume estar conectado a la BD destino)
-- ---------------------------------------------------------------------
-- CREATE DATABASE IF NOT EXISTS `gmc_erp`
--   DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- USE `gmc_erp`;


-- =====================================================================
-- A) GEOGRAFÍA (catálogos)
-- =====================================================================

DROP TABLE IF EXISTS `gmc_paises`;
CREATE TABLE `gmc_paises` (
  `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `codigo_iso` CHAR(2) NOT NULL,
  `nombre`     VARCHAR(80) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_paises_codigo_iso` (`codigo_iso`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `gmc_regiones`;
CREATE TABLE `gmc_regiones` (
  `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `pais_id`    BIGINT UNSIGNED NOT NULL,
  `codigo`     VARCHAR(10) NULL,
  `nombre`     VARCHAR(120) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_regiones_pais` (`pais_id`, `codigo`),
  CONSTRAINT `fk_regiones_pais` FOREIGN KEY (`pais_id`)
    REFERENCES `gmc_paises`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `gmc_comunas`;
CREATE TABLE `gmc_comunas` (
  `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `region_id`  BIGINT UNSIGNED NOT NULL,
  `nombre`     VARCHAR(120) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_comunas_region` (`region_id`),
  CONSTRAINT `fk_comunas_region` FOREIGN KEY (`region_id`)
    REFERENCES `gmc_regiones`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =====================================================================
-- B) MONEDAS Y TIPOS DE CAMBIO
-- =====================================================================

DROP TABLE IF EXISTS `gmc_monedas`;
CREATE TABLE `gmc_monedas` (
  `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `codigo`     CHAR(3) NOT NULL,
  `nombre`     VARCHAR(40) NOT NULL,
  `simbolo`    VARCHAR(5) NULL,
  `decimales`  TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `activa`     TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_monedas_codigo` (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `gmc_tipos_cambio`;
CREATE TABLE `gmc_tipos_cambio` (
  `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `moneda_id`  BIGINT UNSIGNED NOT NULL,
  `fecha`      DATE NOT NULL,
  `valor_clp`  DECIMAL(14,4) NOT NULL,
  `origen`     ENUM('manual','auto') NOT NULL DEFAULT 'manual',
  `actualizado_por` BIGINT UNSIGNED NULL,
  `actualizado_at`  TIMESTAMP NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_tipos_cambio_moneda_fecha` (`moneda_id`, `fecha`),
  KEY `idx_tc_origen` (`origen`),
  KEY `idx_tipos_cambio_fecha` (`fecha`),
  CONSTRAINT `fk_tipos_cambio_moneda` FOREIGN KEY (`moneda_id`)
    REFERENCES `gmc_monedas`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =====================================================================
-- C) ESTADOS (catálogo unificado por dominio)
-- =====================================================================

DROP TABLE IF EXISTS `gmc_estados`;
CREATE TABLE `gmc_estados` (
  `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `dominio`    VARCHAR(40) NOT NULL,
  `codigo`     VARCHAR(40) NOT NULL,
  `nombre`     VARCHAR(80) NOT NULL,
  `color`      VARCHAR(20) NULL,
  `orden`      SMALLINT NOT NULL DEFAULT 0,
  `es_final`   TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
  `activo`     TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_estados_dominio_codigo` (`dominio`, `codigo`),
  KEY `idx_estados_dominio` (`dominio`, `orden`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =====================================================================
-- D) CORRELATIVOS (generador de números secuenciales)
-- =====================================================================

DROP TABLE IF EXISTS `gmc_correlativos`;
CREATE TABLE `gmc_correlativos` (
  `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `dominio`        VARCHAR(40) NOT NULL,
  `anio`           SMALLINT NOT NULL,
  `ultimo_numero`  INT UNSIGNED NOT NULL DEFAULT 0,
  `updated_at`     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_correlativos_dominio_anio` (`dominio`, `anio`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =====================================================================
-- E) SEGURIDAD: roles, permisos, usuarios
-- =====================================================================

DROP TABLE IF EXISTS `gmc_roles`;
CREATE TABLE `gmc_roles` (
  `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `codigo`      VARCHAR(40) NOT NULL,
  `nombre`      VARCHAR(80) NOT NULL,
  `descripcion` VARCHAR(255) NULL,
  `activo`      TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
  `created_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_roles_codigo` (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `gmc_permisos`;
CREATE TABLE `gmc_permisos` (
  `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `codigo`      VARCHAR(80) NOT NULL,
  `descripcion` VARCHAR(180) NULL,
  `modulo`      VARCHAR(40) NOT NULL,
  `created_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_permisos_codigo` (`codigo`),
  KEY `idx_permisos_modulo` (`modulo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `gmc_roles_permisos`;
CREATE TABLE `gmc_roles_permisos` (
  `rol_id`     BIGINT UNSIGNED NOT NULL,
  `permiso_id` BIGINT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`rol_id`, `permiso_id`),
  KEY `idx_rp_permiso` (`permiso_id`),
  CONSTRAINT `fk_rp_rol` FOREIGN KEY (`rol_id`)
    REFERENCES `gmc_roles`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_rp_permiso` FOREIGN KEY (`permiso_id`)
    REFERENCES `gmc_permisos`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `gmc_usuarios`;
CREATE TABLE `gmc_usuarios` (
  `id`                     BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `rut`                    VARCHAR(12) NOT NULL,
  `nombres`                VARCHAR(80) NOT NULL,
  `apellidos`              VARCHAR(80) NOT NULL,
  `email`                  VARCHAR(120) NOT NULL,
  `telefono`               VARCHAR(20) NULL,
  `password_hash`          VARCHAR(255) NOT NULL,
  `password_changed_at`    TIMESTAMP NULL,
  `force_password_change`  TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
  `activo`                 TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
  `ultimo_login_at`        TIMESTAMP NULL,
  `ultimo_login_ip`        VARCHAR(45) NULL,
  `created_at`             TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`             TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at`             TIMESTAMP NULL,
  `created_by`             BIGINT UNSIGNED NULL,
  `updated_by`             BIGINT UNSIGNED NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_usuarios_rut` (`rut`),
  UNIQUE KEY `uk_usuarios_email` (`email`),
  KEY `idx_usuarios_activo` (`activo`),
  KEY `idx_usuarios_created_by` (`created_by`),
  KEY `idx_usuarios_updated_by` (`updated_by`),
  CONSTRAINT `fk_usuarios_created_by` FOREIGN KEY (`created_by`)
    REFERENCES `gmc_usuarios`(`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_usuarios_updated_by` FOREIGN KEY (`updated_by`)
    REFERENCES `gmc_usuarios`(`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `gmc_usuarios_roles`;
CREATE TABLE `gmc_usuarios_roles` (
  `usuario_id` BIGINT UNSIGNED NOT NULL,
  `rol_id`     BIGINT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`usuario_id`, `rol_id`),
  KEY `idx_ur_rol` (`rol_id`),
  CONSTRAINT `fk_ur_usuario` FOREIGN KEY (`usuario_id`)
    REFERENCES `gmc_usuarios`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_ur_rol` FOREIGN KEY (`rol_id`)
    REFERENCES `gmc_roles`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `gmc_sesiones`;
CREATE TABLE `gmc_sesiones` (
  `id`         VARCHAR(128) NOT NULL,
  `ip_address` VARCHAR(45) NOT NULL,
  `timestamp`  INT UNSIGNED NOT NULL DEFAULT 0,
  `data`       BLOB NOT NULL,
  PRIMARY KEY (`id`, `ip_address`),
  KEY `idx_sesiones_timestamp` (`timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `gmc_login_attempts`;
CREATE TABLE `gmc_login_attempts` (
  `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ip`         VARCHAR(45) NOT NULL,
  `rut`        VARCHAR(12) NULL,
  `exitoso`    TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_la_ip_created` (`ip`, `created_at`),
  KEY `idx_la_rut_created` (`rut`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =====================================================================
-- F) MAESTROS DEL NEGOCIO
-- =====================================================================

DROP TABLE IF EXISTS `gmc_clientes`;
CREATE TABLE `gmc_clientes` (
  `id`                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `rut`               VARCHAR(12) NOT NULL,
  `razon_social`      VARCHAR(180) NOT NULL,
  `nombre_fantasia`   VARCHAR(180) NULL,
  `giro`              VARCHAR(180) NULL,
  `direccion`         VARCHAR(180) NULL,
  `comuna_id`         BIGINT UNSIGNED NULL,
  `email`             VARCHAR(120) NULL,
  `telefono`          VARCHAR(30) NULL,
  `contacto_nombre`   VARCHAR(120) NULL,
  `contacto_email`    VARCHAR(120) NULL,
  `contacto_telefono` VARCHAR(30) NULL,
  `activo`            TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
  `created_at`        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at`        TIMESTAMP NULL,
  `created_by`        BIGINT UNSIGNED NULL,
  `updated_by`        BIGINT UNSIGNED NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_clientes_rut` (`rut`),
  KEY `idx_clientes_comuna` (`comuna_id`),
  KEY `idx_clientes_activo` (`activo`, `deleted_at`),
  KEY `idx_clientes_created_by` (`created_by`),
  KEY `idx_clientes_updated_by` (`updated_by`),
  CONSTRAINT `fk_clientes_comuna` FOREIGN KEY (`comuna_id`)
    REFERENCES `gmc_comunas`(`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_clientes_created_by` FOREIGN KEY (`created_by`)
    REFERENCES `gmc_usuarios`(`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_clientes_updated_by` FOREIGN KEY (`updated_by`)
    REFERENCES `gmc_usuarios`(`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `gmc_proveedores`;
CREATE TABLE `gmc_proveedores` (
  `id`                 BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `rut`                VARCHAR(12) NOT NULL,
  `razon_social`       VARCHAR(180) NOT NULL,
  `nombre_fantasia`    VARCHAR(180) NULL,
  `giro`               VARCHAR(180) NULL,
  `direccion`          VARCHAR(180) NULL,
  `comuna_id`          BIGINT UNSIGNED NULL,
  `email`              VARCHAR(120) NULL,
  `telefono`           VARCHAR(30) NULL,
  `contacto_nombre`    VARCHAR(120) NULL,
  `contacto_email`     VARCHAR(120) NULL,
  `contacto_telefono`  VARCHAR(30) NULL,
  `es_subcontratista`  TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
  `categoria`          VARCHAR(80) NULL,
  `activo`             TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
  `created_at`         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at`         TIMESTAMP NULL,
  `created_by`         BIGINT UNSIGNED NULL,
  `updated_by`         BIGINT UNSIGNED NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_proveedores_rut` (`rut`),
  KEY `idx_proveedores_comuna` (`comuna_id`),
  KEY `idx_proveedores_activo` (`activo`, `deleted_at`),
  KEY `idx_proveedores_created_by` (`created_by`),
  KEY `idx_proveedores_updated_by` (`updated_by`),
  CONSTRAINT `fk_proveedores_comuna` FOREIGN KEY (`comuna_id`)
    REFERENCES `gmc_comunas`(`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_proveedores_created_by` FOREIGN KEY (`created_by`)
    REFERENCES `gmc_usuarios`(`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_proveedores_updated_by` FOREIGN KEY (`updated_by`)
    REFERENCES `gmc_usuarios`(`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `gmc_proyectos`;
CREATE TABLE `gmc_proyectos` (
  `id`                       BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `codigo`                   VARCHAR(20) NOT NULL,
  `nombre`                   VARCHAR(180) NOT NULL,
  `cliente_id`               BIGINT UNSIGNED NOT NULL,
  `direccion`                VARCHAR(255) NULL,
  `comuna_id`                BIGINT UNSIGNED NULL,
  `jefe_proyecto_id`         BIGINT UNSIGNED NULL,
  `administrador_obra_id`    BIGINT UNSIGNED NULL,
  `estado_id`                BIGINT UNSIGNED NOT NULL,
  `fecha_inicio`             DATE NULL,
  `fecha_termino_estimada`   DATE NULL,
  `fecha_termino_real`       DATE NULL,
  `moneda_base_id`           BIGINT UNSIGNED NOT NULL,
  `valor_uf_referencia`      DECIMAL(12,4) NULL,
  `observaciones`            TEXT NULL,
  `created_at`               TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`               TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at`               TIMESTAMP NULL,
  `created_by`               BIGINT UNSIGNED NULL,
  `updated_by`               BIGINT UNSIGNED NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_proyectos_codigo` (`codigo`),
  KEY `idx_proyectos_cliente` (`cliente_id`),
  KEY `idx_proyectos_estado` (`estado_id`),
  KEY `idx_proyectos_jefe` (`jefe_proyecto_id`),
  KEY `idx_proyectos_admin_obra` (`administrador_obra_id`),
  KEY `idx_proyectos_comuna` (`comuna_id`),
  KEY `idx_proyectos_moneda` (`moneda_base_id`),
  KEY `idx_proyectos_created_by` (`created_by`),
  KEY `idx_proyectos_updated_by` (`updated_by`),
  CONSTRAINT `fk_proyectos_cliente` FOREIGN KEY (`cliente_id`)
    REFERENCES `gmc_clientes`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_proyectos_comuna` FOREIGN KEY (`comuna_id`)
    REFERENCES `gmc_comunas`(`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_proyectos_jefe` FOREIGN KEY (`jefe_proyecto_id`)
    REFERENCES `gmc_usuarios`(`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_proyectos_admin_obra` FOREIGN KEY (`administrador_obra_id`)
    REFERENCES `gmc_usuarios`(`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_proyectos_estado` FOREIGN KEY (`estado_id`)
    REFERENCES `gmc_estados`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_proyectos_moneda` FOREIGN KEY (`moneda_base_id`)
    REFERENCES `gmc_monedas`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_proyectos_created_by` FOREIGN KEY (`created_by`)
    REFERENCES `gmc_usuarios`(`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_proyectos_updated_by` FOREIGN KEY (`updated_by`)
    REFERENCES `gmc_usuarios`(`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `gmc_centros_costo`;
CREATE TABLE `gmc_centros_costo` (
  `id`                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `proyecto_id`       BIGINT UNSIGNED NULL,
  `codigo`            VARCHAR(30) NOT NULL,
  `nombre`            VARCHAR(120) NOT NULL,
  `es_administracion` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
  `activo`            TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
  `created_at`        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at`        TIMESTAMP NULL,
  `created_by`        BIGINT UNSIGNED NULL,
  `updated_by`        BIGINT UNSIGNED NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_cc_proyecto_codigo` (`proyecto_id`, `codigo`),
  KEY `idx_cc_proyecto` (`proyecto_id`),
  KEY `idx_cc_admin` (`es_administracion`),
  CONSTRAINT `fk_cc_proyecto` FOREIGN KEY (`proyecto_id`)
    REFERENCES `gmc_proyectos`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_cc_created_by` FOREIGN KEY (`created_by`)
    REFERENCES `gmc_usuarios`(`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_cc_updated_by` FOREIGN KEY (`updated_by`)
    REFERENCES `gmc_usuarios`(`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `gmc_tipos_gasto`;
CREATE TABLE `gmc_tipos_gasto` (
  `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `codigo`     VARCHAR(30) NOT NULL,
  `nombre`     VARCHAR(120) NOT NULL,
  `activo`     TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_tipos_gasto_codigo` (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =====================================================================
-- G) PRESUPUESTO INICIAL POR OBRA (alimenta reporte de desviación)
-- =====================================================================

DROP TABLE IF EXISTS `gmc_presupuestos_obra`;
CREATE TABLE `gmc_presupuestos_obra` (
  `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `proyecto_id`  BIGINT UNSIGNED NOT NULL,
  `version`      SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  `moneda_id`    BIGINT UNSIGNED NOT NULL,
  `monto_total`  DECIMAL(14,2) NOT NULL DEFAULT 0,
  `vigente`      TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
  `created_at`   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at`   TIMESTAMP NULL,
  `created_by`   BIGINT UNSIGNED NULL,
  `updated_by`   BIGINT UNSIGNED NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_pres_proyecto_version` (`proyecto_id`, `version`),
  KEY `idx_pres_vigente` (`proyecto_id`, `vigente`),
  CONSTRAINT `fk_pres_proyecto` FOREIGN KEY (`proyecto_id`)
    REFERENCES `gmc_proyectos`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_pres_moneda` FOREIGN KEY (`moneda_id`)
    REFERENCES `gmc_monedas`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_pres_created_by` FOREIGN KEY (`created_by`)
    REFERENCES `gmc_usuarios`(`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_pres_updated_by` FOREIGN KEY (`updated_by`)
    REFERENCES `gmc_usuarios`(`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `gmc_presupuestos_obra_items`;
CREATE TABLE `gmc_presupuestos_obra_items` (
  `id`               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `presupuesto_id`   BIGINT UNSIGNED NOT NULL,
  `centro_costo_id`  BIGINT UNSIGNED NOT NULL,
  `tipo_gasto_id`    BIGINT UNSIGNED NOT NULL,
  `descripcion`      VARCHAR(255) NOT NULL,
  `monto`            DECIMAL(14,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_presi_presupuesto` (`presupuesto_id`),
  KEY `idx_presi_cc` (`centro_costo_id`),
  KEY `idx_presi_tg` (`tipo_gasto_id`),
  CONSTRAINT `fk_presi_presupuesto` FOREIGN KEY (`presupuesto_id`)
    REFERENCES `gmc_presupuestos_obra`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_presi_cc` FOREIGN KEY (`centro_costo_id`)
    REFERENCES `gmc_centros_costo`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_presi_tg` FOREIGN KEY (`tipo_gasto_id`)
    REFERENCES `gmc_tipos_gasto`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =====================================================================
-- H) FINANZAS — SOLICITUDES DE PAGO
-- =====================================================================

DROP TABLE IF EXISTS `gmc_solicitudes_pago`;
CREATE TABLE `gmc_solicitudes_pago` (
  `id`                  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `numero`              VARCHAR(20) NOT NULL,
  `proyecto_id`         BIGINT UNSIGNED NULL,
  `centro_costo_id`     BIGINT UNSIGNED NOT NULL,
  `proveedor_id`        BIGINT UNSIGNED NOT NULL,
  `tipo_gasto_id`       BIGINT UNSIGNED NOT NULL,
  `moneda_id`           BIGINT UNSIGNED NOT NULL,
  `monto_neto`          DECIMAL(14,2) NOT NULL,
  `monto_iva`           DECIMAL(14,2) NOT NULL DEFAULT 0,
  `monto_total`         DECIMAL(14,2) NOT NULL,
  `tipo_cambio_clp`     DECIMAL(14,4) NULL,
  `monto_total_clp`     DECIMAL(14,2) NULL,
  `fecha_emision`       DATE NOT NULL,
  `fecha_vencimiento`   DATE NULL,
  `fecha_programada`    DATE NULL,
  `fecha_pago`          DATE NULL,
  `documento_tipo`      VARCHAR(40) NULL,
  `documento_numero`    VARCHAR(40) NULL,
  `forma_pago`          VARCHAR(40) NULL,
  `descripcion`         TEXT NULL,
  `comentarios`         TEXT NULL,
  `motivo_rechazo`      VARCHAR(500) NULL,
  `estado_id`           BIGINT UNSIGNED NOT NULL,
  `validada_por`        BIGINT UNSIGNED NULL,
  `validada_at`         TIMESTAMP NULL,
  `programada_por`      BIGINT UNSIGNED NULL,
  `programada_at`       TIMESTAMP NULL,
  `pagada_por`          BIGINT UNSIGNED NULL,
  `pagada_at`           TIMESTAMP NULL,
  `rechazada_por`       BIGINT UNSIGNED NULL,
  `rechazada_at`        TIMESTAMP NULL,
  `created_at`          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at`          TIMESTAMP NULL,
  `created_by`          BIGINT UNSIGNED NULL,
  `updated_by`          BIGINT UNSIGNED NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_sdp_numero` (`numero`),
  KEY `idx_sdp_proyecto_estado` (`proyecto_id`, `estado_id`),
  KEY `idx_sdp_proveedor_estado` (`proveedor_id`, `estado_id`),
  KEY `idx_sdp_centro_costo` (`centro_costo_id`),
  KEY `idx_sdp_estado_fecha` (`estado_id`, `fecha_emision`),
  KEY `idx_sdp_tipo_gasto` (`tipo_gasto_id`),
  KEY `idx_sdp_moneda` (`moneda_id`),
  KEY `idx_sdp_validada_por` (`validada_por`),
  KEY `idx_sdp_programada_por` (`programada_por`),
  KEY `idx_sdp_pagada_por` (`pagada_por`),
  KEY `idx_sdp_rechazada_por` (`rechazada_por`),
  KEY `idx_sdp_created_by` (`created_by`),
  KEY `idx_sdp_updated_by` (`updated_by`),
  CONSTRAINT `fk_sdp_proyecto` FOREIGN KEY (`proyecto_id`)
    REFERENCES `gmc_proyectos`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_sdp_cc` FOREIGN KEY (`centro_costo_id`)
    REFERENCES `gmc_centros_costo`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_sdp_proveedor` FOREIGN KEY (`proveedor_id`)
    REFERENCES `gmc_proveedores`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_sdp_tipo_gasto` FOREIGN KEY (`tipo_gasto_id`)
    REFERENCES `gmc_tipos_gasto`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_sdp_moneda` FOREIGN KEY (`moneda_id`)
    REFERENCES `gmc_monedas`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_sdp_estado` FOREIGN KEY (`estado_id`)
    REFERENCES `gmc_estados`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_sdp_validada_por` FOREIGN KEY (`validada_por`)
    REFERENCES `gmc_usuarios`(`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_sdp_programada_por` FOREIGN KEY (`programada_por`)
    REFERENCES `gmc_usuarios`(`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_sdp_pagada_por` FOREIGN KEY (`pagada_por`)
    REFERENCES `gmc_usuarios`(`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_sdp_rechazada_por` FOREIGN KEY (`rechazada_por`)
    REFERENCES `gmc_usuarios`(`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_sdp_created_by` FOREIGN KEY (`created_by`)
    REFERENCES `gmc_usuarios`(`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_sdp_updated_by` FOREIGN KEY (`updated_by`)
    REFERENCES `gmc_usuarios`(`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `gmc_sdp_estados_log`;
CREATE TABLE `gmc_sdp_estados_log` (
  `id`                  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `solicitud_pago_id`   BIGINT UNSIGNED NOT NULL,
  `estado_anterior_id`  BIGINT UNSIGNED NULL,
  `estado_nuevo_id`     BIGINT UNSIGNED NOT NULL,
  `usuario_id`          BIGINT UNSIGNED NOT NULL,
  `comentario`          VARCHAR(500) NULL,
  `created_at`          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_sdpl_sdp` (`solicitud_pago_id`, `created_at`),
  KEY `idx_sdpl_estado_ant` (`estado_anterior_id`),
  KEY `idx_sdpl_estado_new` (`estado_nuevo_id`),
  KEY `idx_sdpl_usuario` (`usuario_id`),
  CONSTRAINT `fk_sdpl_sdp` FOREIGN KEY (`solicitud_pago_id`)
    REFERENCES `gmc_solicitudes_pago`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_sdpl_estado_ant` FOREIGN KEY (`estado_anterior_id`)
    REFERENCES `gmc_estados`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_sdpl_estado_new` FOREIGN KEY (`estado_nuevo_id`)
    REFERENCES `gmc_estados`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_sdpl_usuario` FOREIGN KEY (`usuario_id`)
    REFERENCES `gmc_usuarios`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =====================================================================
-- I) COMPRAS / RECEPCIÓN
-- =====================================================================

DROP TABLE IF EXISTS `gmc_compras`;
CREATE TABLE `gmc_compras` (
  `id`                 BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `numero`             VARCHAR(20) NOT NULL,
  `proyecto_id`        BIGINT UNSIGNED NULL,
  `centro_costo_id`    BIGINT UNSIGNED NOT NULL,
  `proveedor_id`       BIGINT UNSIGNED NOT NULL,
  `moneda_id`          BIGINT UNSIGNED NOT NULL,
  `fecha_recepcion`    DATE NOT NULL,
  `documento_tipo`     VARCHAR(40) NULL,
  `documento_numero`   VARCHAR(40) NULL,
  `monto_neto`         DECIMAL(14,2) NOT NULL,
  `monto_iva`          DECIMAL(14,2) NOT NULL DEFAULT 0,
  `monto_total`        DECIMAL(14,2) NOT NULL,
  `tipo_cambio_clp`    DECIMAL(14,4) NULL,
  `monto_total_clp`    DECIMAL(14,2) NULL,
  `solicitud_pago_id`  BIGINT UNSIGNED NULL,
  `rinde_id`           BIGINT UNSIGNED NULL,
  `observaciones`      TEXT NULL,
  `estado_id`          BIGINT UNSIGNED NOT NULL,
  `created_at`         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at`         TIMESTAMP NULL,
  `created_by`         BIGINT UNSIGNED NULL,
  `updated_by`         BIGINT UNSIGNED NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_compras_numero` (`numero`),
  KEY `idx_compras_proyecto` (`proyecto_id`),
  KEY `idx_compras_proveedor` (`proveedor_id`),
  KEY `idx_compras_cc_fecha` (`centro_costo_id`, `fecha_recepcion`),
  KEY `idx_compras_estado` (`estado_id`),
  KEY `idx_compras_sdp` (`solicitud_pago_id`),
  KEY `idx_compras_rinde` (`rinde_id`),
  KEY `idx_compras_moneda` (`moneda_id`),
  KEY `idx_compras_created_by` (`created_by`),
  KEY `idx_compras_updated_by` (`updated_by`),
  CONSTRAINT `fk_compras_proyecto` FOREIGN KEY (`proyecto_id`)
    REFERENCES `gmc_proyectos`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_compras_cc` FOREIGN KEY (`centro_costo_id`)
    REFERENCES `gmc_centros_costo`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_compras_proveedor` FOREIGN KEY (`proveedor_id`)
    REFERENCES `gmc_proveedores`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_compras_moneda` FOREIGN KEY (`moneda_id`)
    REFERENCES `gmc_monedas`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_compras_estado` FOREIGN KEY (`estado_id`)
    REFERENCES `gmc_estados`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_compras_sdp` FOREIGN KEY (`solicitud_pago_id`)
    REFERENCES `gmc_solicitudes_pago`(`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_compras_created_by` FOREIGN KEY (`created_by`)
    REFERENCES `gmc_usuarios`(`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_compras_updated_by` FOREIGN KEY (`updated_by`)
    REFERENCES `gmc_usuarios`(`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `gmc_compras_items`;
CREATE TABLE `gmc_compras_items` (
  `id`               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `compra_id`        BIGINT UNSIGNED NOT NULL,
  `tipo_gasto_id`    BIGINT UNSIGNED NULL,
  `descripcion`      VARCHAR(255) NOT NULL,
  `cantidad`         DECIMAL(12,3) NOT NULL DEFAULT 1.000,
  `unidad`           VARCHAR(30) NULL,
  `precio_unitario`  DECIMAL(14,2) NOT NULL,
  `total_linea`      DECIMAL(14,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_ci_compra` (`compra_id`),
  KEY `idx_ci_tg` (`tipo_gasto_id`),
  CONSTRAINT `fk_ci_compra` FOREIGN KEY (`compra_id`)
    REFERENCES `gmc_compras`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_ci_tg` FOREIGN KEY (`tipo_gasto_id`)
    REFERENCES `gmc_tipos_gasto`(`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =====================================================================
-- J) RINDE DE GASTOS
-- =====================================================================

DROP TABLE IF EXISTS `gmc_rindes_gastos`;
CREATE TABLE `gmc_rindes_gastos` (
  `id`                  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `numero`              VARCHAR(20) NOT NULL,
  `proyecto_id`         BIGINT UNSIGNED NULL,
  `centro_costo_id`     BIGINT UNSIGNED NOT NULL,
  `usuario_id`          BIGINT UNSIGNED NOT NULL,
  `moneda_id`           BIGINT UNSIGNED NOT NULL,
  `fecha_rendicion`     DATE NOT NULL,
  `monto_total`         DECIMAL(14,2) NOT NULL DEFAULT 0,
  `tipo_cambio_clp`     DECIMAL(14,4) NULL,
  `monto_total_clp`     DECIMAL(14,2) NULL,
  `solicitud_pago_id`   BIGINT UNSIGNED NULL,
  `observaciones`       TEXT NULL,
  `motivo_rechazo`      VARCHAR(500) NULL,
  `estado_id`           BIGINT UNSIGNED NOT NULL,
  `aprobada_por`        BIGINT UNSIGNED NULL,
  `aprobada_at`         TIMESTAMP NULL,
  `rechazada_por`       BIGINT UNSIGNED NULL,
  `rechazada_at`        TIMESTAMP NULL,
  `created_at`          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at`          TIMESTAMP NULL,
  `created_by`          BIGINT UNSIGNED NULL,
  `updated_by`          BIGINT UNSIGNED NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_rindes_numero` (`numero`),
  KEY `idx_rindes_proyecto` (`proyecto_id`),
  KEY `idx_rindes_cc` (`centro_costo_id`),
  KEY `idx_rindes_usuario` (`usuario_id`),
  KEY `idx_rindes_estado_fecha` (`estado_id`, `fecha_rendicion`),
  KEY `idx_rindes_sdp` (`solicitud_pago_id`),
  KEY `idx_rindes_moneda` (`moneda_id`),
  KEY `idx_rindes_aprobada_por` (`aprobada_por`),
  KEY `idx_rindes_rechazada_por` (`rechazada_por`),
  KEY `idx_rindes_created_by` (`created_by`),
  KEY `idx_rindes_updated_by` (`updated_by`),
  CONSTRAINT `fk_rindes_proyecto` FOREIGN KEY (`proyecto_id`)
    REFERENCES `gmc_proyectos`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_rindes_cc` FOREIGN KEY (`centro_costo_id`)
    REFERENCES `gmc_centros_costo`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_rindes_usuario` FOREIGN KEY (`usuario_id`)
    REFERENCES `gmc_usuarios`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_rindes_moneda` FOREIGN KEY (`moneda_id`)
    REFERENCES `gmc_monedas`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_rindes_estado` FOREIGN KEY (`estado_id`)
    REFERENCES `gmc_estados`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_rindes_sdp` FOREIGN KEY (`solicitud_pago_id`)
    REFERENCES `gmc_solicitudes_pago`(`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_rindes_aprobada_por` FOREIGN KEY (`aprobada_por`)
    REFERENCES `gmc_usuarios`(`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_rindes_rechazada_por` FOREIGN KEY (`rechazada_por`)
    REFERENCES `gmc_usuarios`(`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_rindes_created_by` FOREIGN KEY (`created_by`)
    REFERENCES `gmc_usuarios`(`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_rindes_updated_by` FOREIGN KEY (`updated_by`)
    REFERENCES `gmc_usuarios`(`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- Vínculo posterior: compra puede apuntar a rinde (FK añadida después de crear ambas tablas)
ALTER TABLE `gmc_compras`
  ADD CONSTRAINT `fk_compras_rinde` FOREIGN KEY (`rinde_id`)
    REFERENCES `gmc_rindes_gastos`(`id`) ON DELETE SET NULL ON UPDATE CASCADE;


DROP TABLE IF EXISTS `gmc_rinde_items`;
CREATE TABLE `gmc_rinde_items` (
  `id`               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `rinde_id`         BIGINT UNSIGNED NOT NULL,
  `tipo_gasto_id`    BIGINT UNSIGNED NOT NULL,
  `fecha`            DATE NOT NULL,
  `descripcion`      VARCHAR(255) NOT NULL,
  `documento_tipo`   VARCHAR(40) NULL,
  `documento_numero` VARCHAR(40) NULL,
  `monto`            DECIMAL(14,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_ri_rinde` (`rinde_id`),
  KEY `idx_ri_tg` (`tipo_gasto_id`),
  KEY `idx_ri_fecha` (`fecha`),
  CONSTRAINT `fk_ri_rinde` FOREIGN KEY (`rinde_id`)
    REFERENCES `gmc_rindes_gastos`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_ri_tg` FOREIGN KEY (`tipo_gasto_id`)
    REFERENCES `gmc_tipos_gasto`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =====================================================================
-- K) EJECUCIÓN DE OBRA
-- =====================================================================

DROP TABLE IF EXISTS `gmc_bitacoras`;
CREATE TABLE `gmc_bitacoras` (
  `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `numero`       VARCHAR(30) NOT NULL,
  `proyecto_id`  BIGINT UNSIGNED NOT NULL,
  `fecha_evento` DATE NOT NULL,
  `tipo_evento`  ENUM('avance','observacion','incidencia','otro') NOT NULL DEFAULT 'avance',
  `titulo`       VARCHAR(180) NOT NULL,
  `detalle`      TEXT NOT NULL,
  `autor_id`     BIGINT UNSIGNED NOT NULL,
  `created_at`   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at`   TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_bitacora_numero` (`numero`),
  KEY `idx_bit_proyecto_fecha` (`proyecto_id`, `fecha_evento`),
  KEY `idx_bit_autor` (`autor_id`),
  CONSTRAINT `fk_bit_proyecto` FOREIGN KEY (`proyecto_id`)
    REFERENCES `gmc_proyectos`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_bit_autor` FOREIGN KEY (`autor_id`)
    REFERENCES `gmc_usuarios`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `gmc_cierres_obra`;
CREATE TABLE `gmc_cierres_obra` (
  `id`                  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `proyecto_id`         BIGINT UNSIGNED NOT NULL,
  `fecha_termino_real`  DATE NOT NULL,
  `resumen`             TEXT NOT NULL,
  `conformidades`       TEXT NULL,
  `observaciones`       TEXT NULL,
  `cerrada_por`         BIGINT UNSIGNED NULL,
  `cerrada_at`          TIMESTAMP NULL,
  `estado_id`           BIGINT UNSIGNED NOT NULL,
  `created_at`          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at`          TIMESTAMP NULL,
  `created_by`          BIGINT UNSIGNED NULL,
  `updated_by`          BIGINT UNSIGNED NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_cierre_proyecto` (`proyecto_id`),
  KEY `idx_cierre_estado` (`estado_id`),
  KEY `idx_cierre_cerrada_por` (`cerrada_por`),
  KEY `idx_cierre_created_by` (`created_by`),
  KEY `idx_cierre_updated_by` (`updated_by`),
  CONSTRAINT `fk_cierre_proyecto` FOREIGN KEY (`proyecto_id`)
    REFERENCES `gmc_proyectos`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_cierre_estado` FOREIGN KEY (`estado_id`)
    REFERENCES `gmc_estados`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_cierre_cerrada_por` FOREIGN KEY (`cerrada_por`)
    REFERENCES `gmc_usuarios`(`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_cierre_created_by` FOREIGN KEY (`created_by`)
    REFERENCES `gmc_usuarios`(`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_cierre_updated_by` FOREIGN KEY (`updated_by`)
    REFERENCES `gmc_usuarios`(`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =====================================================================
-- L) ADJUNTOS / AUDITORÍA / NOTIFICACIONES
-- =====================================================================

DROP TABLE IF EXISTS `gmc_adjuntos`;
CREATE TABLE `gmc_adjuntos` (
  `id`               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `entidad`          VARCHAR(40) NOT NULL,
  `entidad_id`       BIGINT UNSIGNED NOT NULL,
  `categoria`        VARCHAR(40) NULL,
  `nombre_original`  VARCHAR(180) NOT NULL,
  `ruta`             VARCHAR(255) NOT NULL,
  `mime`             VARCHAR(120) NOT NULL,
  `tamano_bytes`     INT UNSIGNED NOT NULL,
  `uploaded_by`      BIGINT UNSIGNED NOT NULL,
  `created_at`       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at`       TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `idx_adj_entidad` (`entidad`, `entidad_id`),
  KEY `idx_adj_uploaded_by` (`uploaded_by`),
  CONSTRAINT `fk_adj_uploaded_by` FOREIGN KEY (`uploaded_by`)
    REFERENCES `gmc_usuarios`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `gmc_auditoria_logs`;
CREATE TABLE `gmc_auditoria_logs` (
  `id`               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `usuario_id`       BIGINT UNSIGNED NULL,
  `accion`           VARCHAR(80) NOT NULL,
  `entidad`          VARCHAR(60) NULL,
  `entidad_id`       BIGINT UNSIGNED NULL,
  `estado_anterior`  JSON NULL,
  `estado_nuevo`     JSON NULL,
  `ip`               VARCHAR(45) NULL,
  `user_agent`       VARCHAR(255) NULL,
  `created_at`       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_aud_entidad` (`entidad`, `entidad_id`),
  KEY `idx_aud_usuario_fecha` (`usuario_id`, `created_at`),
  KEY `idx_aud_accion_fecha` (`accion`, `created_at`),
  CONSTRAINT `fk_aud_usuario` FOREIGN KEY (`usuario_id`)
    REFERENCES `gmc_usuarios`(`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `gmc_notificaciones`;
CREATE TABLE `gmc_notificaciones` (
  `id`                       BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `tipo`                     VARCHAR(60) NOT NULL,
  `canal`                    ENUM('email','sistema') NOT NULL DEFAULT 'email',
  `destinatario_usuario_id`  BIGINT UNSIGNED NULL,
  `destinatario_email`       VARCHAR(120) NULL,
  `asunto`                   VARCHAR(180) NOT NULL,
  `cuerpo`                   TEXT NOT NULL,
  `payload`                  JSON NULL,
  `estado`                   ENUM('pendiente','enviada','fallida') NOT NULL DEFAULT 'pendiente',
  `intentos`                 TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `ultimo_error`             VARCHAR(255) NULL,
  `enviada_at`               TIMESTAMP NULL,
  `created_at`               TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`               TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_notif_estado_fecha` (`estado`, `created_at`),
  KEY `idx_notif_destinatario` (`destinatario_usuario_id`),
  CONSTRAINT `fk_notif_destinatario` FOREIGN KEY (`destinatario_usuario_id`)
    REFERENCES `gmc_usuarios`(`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================================
-- FIN DEL DDL
-- Para datos iniciales (roles, permisos, estados, tipos de gasto,
-- regiones/comunas de Chile, usuario admin), ver:
--   _rediseno/seeders/*.sql (versión SQL pura)
--   _rediseno/seeders/*.php (versión PHP para CI3)
-- =====================================================================
