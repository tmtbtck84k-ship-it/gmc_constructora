<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Migración 5 — Fase 2-C Sprint A:
 *   - 3 columnas nuevas en gmc_proyectos (calendario laboral configurable).
 *   - Tablas nuevas: gmc_feriados, gmc_hitos, gmc_actividades, gmc_actividad_dependencias.
 *   - Seed de feriados oficiales de Chile 2026.
 *   - Correlativos para dominios `hito` y `actividad`.
 *   - Permisos nuevos en ACL + asignación a roles.
 *
 * Aditiva, no toca datos de Fase 1.
 */
class Migration_Crear_gantt_y_calendario extends CI_Migration
{
    public function up()
    {
        // ===== 1. ALTER gmc_proyectos: calendario configurable =====
        $this->db->query("
            ALTER TABLE `gmc_proyectos`
            ADD COLUMN `dias_laborales` ENUM('lun_vie','lun_sab','lun_dom','personalizado')
                                        NOT NULL DEFAULT 'lun_vie' AFTER `valor_uf_referencia`,
            ADD COLUMN `dias_laborales_custom` VARCHAR(20) NULL AFTER `dias_laborales`,
            ADD COLUMN `trabaja_feriados` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 AFTER `dias_laborales_custom`
        ");

        // ===== 2. CREATE gmc_feriados =====
        $this->db->query("
            CREATE TABLE `gmc_feriados` (
                `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `fecha`         DATE NOT NULL,
                `nombre`        VARCHAR(120) NOT NULL,
                `irrenunciable` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
                `tipo`          VARCHAR(40) NULL,
                `created_at`    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uk_feriados_fecha` (`fecha`),
                KEY `idx_feriados_anio` (`fecha`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // Seed feriados Chile 2026
        $feriados2026 = [
            ['2026-01-01', 'Año Nuevo',                            1, 'civil'],
            ['2026-04-03', 'Viernes Santo',                        0, 'religioso'],
            ['2026-04-04', 'Sábado Santo',                         0, 'religioso'],
            ['2026-05-01', 'Día del Trabajo',                      1, 'civil'],
            ['2026-05-21', 'Día de las Glorias Navales',           0, 'civil'],
            ['2026-06-29', 'San Pedro y San Pablo',                0, 'religioso'],
            ['2026-07-16', 'Día de la Virgen del Carmen',          0, 'religioso'],
            ['2026-08-15', 'Asunción de la Virgen',                0, 'religioso'],
            ['2026-09-18', 'Independencia Nacional',               1, 'civil'],
            ['2026-09-19', 'Día de las Glorias del Ejército',      1, 'civil'],
            ['2026-10-12', 'Encuentro de Dos Mundos',              0, 'civil'],
            ['2026-10-31', 'Día de las Iglesias Evangélicas',      0, 'religioso'],
            ['2026-11-01', 'Día de Todos los Santos',              0, 'religioso'],
            ['2026-12-08', 'Inmaculada Concepción',                0, 'religioso'],
            ['2026-12-25', 'Navidad',                              1, 'religioso'],
        ];
        foreach ($feriados2026 as $f) {
            $this->db->insert('gmc_feriados', [
                'fecha' => $f[0], 'nombre' => $f[1], 'irrenunciable' => $f[2], 'tipo' => $f[3],
            ]);
        }

        // ===== 3. CREATE gmc_hitos =====
        $this->db->query("
            CREATE TABLE `gmc_hitos` (
                `id`                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `proyecto_id`       BIGINT UNSIGNED NOT NULL,
                `codigo`            VARCHAR(30) NOT NULL,
                `nombre`            VARCHAR(180) NOT NULL,
                `descripcion`       VARCHAR(500) NULL,
                `fecha_objetivo`    DATE NULL,
                `fecha_real`        DATE NULL,
                `completado`        TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
                `porcentaje_avance` DECIMAL(5,2) NOT NULL DEFAULT 0,
                `orden`             SMALLINT NOT NULL DEFAULT 0,
                `created_at`        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at`        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                `deleted_at`        TIMESTAMP NULL,
                `created_by`        BIGINT UNSIGNED NULL,
                `updated_by`        BIGINT UNSIGNED NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uk_hitos_codigo` (`codigo`),
                KEY `idx_hitos_proyecto_orden` (`proyecto_id`, `orden`),
                KEY `idx_hitos_completado` (`proyecto_id`, `completado`),
                CONSTRAINT `fk_hitos_proyecto` FOREIGN KEY (`proyecto_id`)
                    REFERENCES `gmc_proyectos`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
                CONSTRAINT `fk_hitos_created_by` FOREIGN KEY (`created_by`)
                    REFERENCES `gmc_usuarios`(`id`) ON DELETE SET NULL ON UPDATE CASCADE,
                CONSTRAINT `fk_hitos_updated_by` FOREIGN KEY (`updated_by`)
                    REFERENCES `gmc_usuarios`(`id`) ON DELETE SET NULL ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // ===== 4. CREATE gmc_actividades =====
        $this->db->query("
            CREATE TABLE `gmc_actividades` (
                `id`                       BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `proyecto_id`              BIGINT UNSIGNED NOT NULL,
                `hito_id`                  BIGINT UNSIGNED NULL,
                `codigo`                   VARCHAR(30) NOT NULL,
                `nombre`                   VARCHAR(180) NOT NULL,
                `descripcion`              VARCHAR(500) NULL,
                `fecha_inicio_planificada` DATE NOT NULL,
                `fecha_termino_planificada` DATE NOT NULL,
                `fecha_inicio_real`        DATE NULL,
                `fecha_termino_real`       DATE NULL,
                `duracion_dias`            SMALLINT UNSIGNED NOT NULL DEFAULT 1,
                `porcentaje_avance`        DECIMAL(5,2) NOT NULL DEFAULT 0,
                `responsable_id`           BIGINT UNSIGNED NULL,
                `colaboradores_libres`     VARCHAR(255) NULL,
                `es_critica`               TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
                `holgura_dias`             SMALLINT NOT NULL DEFAULT 0,
                `orden`                    SMALLINT NOT NULL DEFAULT 0,
                `created_at`               TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at`               TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                `deleted_at`               TIMESTAMP NULL,
                `created_by`               BIGINT UNSIGNED NULL,
                `updated_by`               BIGINT UNSIGNED NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uk_actividades_codigo` (`codigo`),
                KEY `idx_act_proyecto_hito_orden` (`proyecto_id`, `hito_id`, `orden`),
                KEY `idx_act_responsable` (`responsable_id`),
                KEY `idx_act_termino` (`fecha_termino_planificada`),
                KEY `idx_act_critica` (`es_critica`),
                CONSTRAINT `fk_act_proyecto` FOREIGN KEY (`proyecto_id`)
                    REFERENCES `gmc_proyectos`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
                CONSTRAINT `fk_act_hito` FOREIGN KEY (`hito_id`)
                    REFERENCES `gmc_hitos`(`id`) ON DELETE SET NULL ON UPDATE CASCADE,
                CONSTRAINT `fk_act_responsable` FOREIGN KEY (`responsable_id`)
                    REFERENCES `gmc_usuarios`(`id`) ON DELETE SET NULL ON UPDATE CASCADE,
                CONSTRAINT `fk_act_created_by` FOREIGN KEY (`created_by`)
                    REFERENCES `gmc_usuarios`(`id`) ON DELETE SET NULL ON UPDATE CASCADE,
                CONSTRAINT `fk_act_updated_by` FOREIGN KEY (`updated_by`)
                    REFERENCES `gmc_usuarios`(`id`) ON DELETE SET NULL ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // ===== 5. CREATE gmc_actividad_dependencias =====
        $this->db->query("
            CREATE TABLE `gmc_actividad_dependencias` (
                `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `actividad_id`   BIGINT UNSIGNED NOT NULL,
                `predecesor_id`  BIGINT UNSIGNED NOT NULL,
                `tipo`           ENUM('FS','SS','FF','SF') NOT NULL DEFAULT 'FS',
                `lag_dias`       SMALLINT NOT NULL DEFAULT 0,
                `created_at`     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uk_act_dep_par` (`actividad_id`, `predecesor_id`),
                KEY `idx_dep_predecesor` (`predecesor_id`),
                CONSTRAINT `fk_dep_actividad` FOREIGN KEY (`actividad_id`)
                    REFERENCES `gmc_actividades`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
                CONSTRAINT `fk_dep_predecesor` FOREIGN KEY (`predecesor_id`)
                    REFERENCES `gmc_actividades`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // ===== 6. Correlativos para hito y actividad =====
        $anio = (int)date('Y');
        foreach (['hito', 'actividad'] as $dom) {
            $exists = $this->db->where(['dominio' => $dom, 'anio' => $anio])->count_all_results('gmc_correlativos');
            if (!$exists) {
                $this->db->insert('gmc_correlativos', [
                    'dominio' => $dom, 'anio' => $anio, 'ultimo_numero' => 0,
                ]);
            }
        }

        // ===== 7. Permisos ACL =====
        $permisos = [
            ['codigo' => 'obras.gantt.ver',         'descripcion' => 'Ver Gantt y actividades',         'modulo' => 'obras'],
            ['codigo' => 'obras.gantt.editar',      'descripcion' => 'Crear/editar/eliminar hitos y actividades', 'modulo' => 'obras'],
            ['codigo' => 'obras.gantt.dependencia', 'descripcion' => 'Crear/editar dependencias',       'modulo' => 'obras'],
            ['codigo' => 'obras.gantt.exportar',    'descripcion' => 'Exportar Gantt a PDF/PNG',        'modulo' => 'obras'],
            ['codigo' => 'obras.feriado.editar',    'descripcion' => 'Administrar feriados',            'modulo' => 'obras'],
        ];
        foreach ($permisos as $p) {
            $exists = $this->db->where('codigo', $p['codigo'])->count_all_results('gmc_permisos');
            if (!$exists) $this->db->insert('gmc_permisos', $p);
        }

        // Asignación a roles
        $this->_assign('admin', ['obras.gantt.ver','obras.gantt.editar','obras.gantt.dependencia','obras.gantt.exportar','obras.feriado.editar']);
        $this->_assign('gerencia', ['obras.gantt.ver','obras.gantt.editar','obras.gantt.dependencia','obras.gantt.exportar']);
        $this->_assign('jefe_proyecto', ['obras.gantt.ver','obras.gantt.editar','obras.gantt.dependencia','obras.gantt.exportar']);
        $this->_assign('administrador_obra', ['obras.gantt.ver']);
        $this->_assign('bodega_obra', ['obras.gantt.ver']);
        $this->_assign('contabilidad', ['obras.gantt.ver','obras.gantt.exportar']);
    }

    public function down()
    {
        $this->db->query('SET FOREIGN_KEY_CHECKS = 0');
        $this->db->query('DROP TABLE IF EXISTS `gmc_actividad_dependencias`');
        $this->db->query('DROP TABLE IF EXISTS `gmc_actividades`');
        $this->db->query('DROP TABLE IF EXISTS `gmc_hitos`');
        $this->db->query('DROP TABLE IF EXISTS `gmc_feriados`');

        $this->db->query("
            ALTER TABLE `gmc_proyectos`
            DROP COLUMN `trabaja_feriados`,
            DROP COLUMN `dias_laborales_custom`,
            DROP COLUMN `dias_laborales`
        ");
        $this->db->query('SET FOREIGN_KEY_CHECKS = 1');

        // Borrar permisos
        $this->db->query("
            DELETE rp FROM gmc_roles_permisos rp
            JOIN gmc_permisos p ON p.id = rp.permiso_id
            WHERE p.codigo IN ('obras.gantt.ver','obras.gantt.editar','obras.gantt.dependencia','obras.gantt.exportar','obras.feriado.editar')
        ");
        $this->db->where_in('codigo', ['obras.gantt.ver','obras.gantt.editar','obras.gantt.dependencia','obras.gantt.exportar','obras.feriado.editar'])
                 ->delete('gmc_permisos');

        // Borrar correlativos
        $this->db->where_in('dominio', ['hito','actividad'])->delete('gmc_correlativos');
    }

    private function _assign(string $rolCodigo, array $permisos): void
    {
        $rol = $this->db->where('codigo', $rolCodigo)->get('gmc_roles')->row_array();
        if (!$rol) return;
        foreach ($permisos as $pc) {
            $perm = $this->db->where('codigo', $pc)->get('gmc_permisos')->row_array();
            if (!$perm) continue;
            $exists = $this->db->where(['rol_id' => $rol['id'], 'permiso_id' => $perm['id']])
                               ->count_all_results('gmc_roles_permisos');
            if (!$exists) {
                $this->db->insert('gmc_roles_permisos', ['rol_id' => (int)$rol['id'], 'permiso_id' => (int)$perm['id']]);
            }
        }
    }
}
