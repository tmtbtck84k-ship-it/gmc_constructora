<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Migración 4 — Origen de los tipos de cambio.
 * - Agrega columna `origen` ENUM('manual','auto') a gmc_tipos_cambio.
 * - Default 'manual' para los registros existentes.
 * - Permite que el sync automático respete los TC cargados a mano.
 */
class Migration_Origen_tipos_cambio extends CI_Migration
{
    public function up()
    {
        $this->db->query("
            ALTER TABLE `gmc_tipos_cambio`
            ADD COLUMN `origen` ENUM('manual','auto') NOT NULL DEFAULT 'manual' AFTER `valor_clp`,
            ADD COLUMN `actualizado_por` BIGINT UNSIGNED NULL AFTER `origen`,
            ADD COLUMN `actualizado_at` TIMESTAMP NULL AFTER `actualizado_por`
        ");
        $this->db->query("CREATE INDEX `idx_tc_origen` ON `gmc_tipos_cambio`(`origen`)");
    }

    public function down()
    {
        $this->db->query("DROP INDEX `idx_tc_origen` ON `gmc_tipos_cambio`");
        $this->db->query("
            ALTER TABLE `gmc_tipos_cambio`
            DROP COLUMN `actualizado_at`,
            DROP COLUMN `actualizado_por`,
            DROP COLUMN `origen`
        ");
    }
}
