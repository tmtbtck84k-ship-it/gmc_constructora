<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Migración 3 — Multimoneda:
 *   - Agrega tipo_cambio_clp y monto_total_clp a SDP, compras y rindes (snapshot del TC).
 *   - Agrega permisos para el módulo "tipos de cambio" + asignación a roles.
 *   - Seed inicial de tipos de cambio para el día de hoy (UF, USD, EUR de referencia).
 */
class Migration_Agregar_multimoneda_y_permisos extends CI_Migration
{
    public function up()
    {
        // -------- 1. Columnas TC snapshot --------
        $tablas = ['gmc_solicitudes_pago', 'gmc_compras', 'gmc_rindes_gastos'];
        foreach ($tablas as $t) {
            $this->db->query("
                ALTER TABLE `{$t}`
                ADD COLUMN `tipo_cambio_clp` DECIMAL(14,4) NULL DEFAULT NULL AFTER `monto_total`,
                ADD COLUMN `monto_total_clp` DECIMAL(14,2) NULL DEFAULT NULL AFTER `tipo_cambio_clp`
            ");
            $this->db->query("CREATE INDEX `idx_{$t}_monto_total_clp` ON `{$t}`(`monto_total_clp`)");
        }

        // -------- 2. Permisos módulo Tipos de Cambio --------
        $permisos = [
            ['codigo' => 'maestros.tipo_cambio.ver',      'descripcion' => 'Ver tipos de cambio',       'modulo' => 'maestros'],
            ['codigo' => 'maestros.tipo_cambio.editar',   'descripcion' => 'Cargar / editar tipos de cambio', 'modulo' => 'maestros'],
        ];
        foreach ($permisos as $p) {
            // Idempotente: sólo inserta si no existe
            $exists = $this->db->where('codigo', $p['codigo'])->count_all_results('gmc_permisos');
            if (!$exists) $this->db->insert('gmc_permisos', $p);
        }

        // Asignar permisos a roles según corresponda
        $this->_assign('admin', ['maestros.tipo_cambio.ver','maestros.tipo_cambio.editar']);
        $this->_assign('finanzas', ['maestros.tipo_cambio.ver','maestros.tipo_cambio.editar']);
        $this->_assign('gerencia', ['maestros.tipo_cambio.ver']);
        $this->_assign('contabilidad', ['maestros.tipo_cambio.ver']);
        $this->_assign('jefe_proyecto', ['maestros.tipo_cambio.ver']);
        $this->_assign('administrador_obra', ['maestros.tipo_cambio.ver']);

        // -------- 3. Seed de TC iniciales (referencia) --------
        $hoy = date('Y-m-d');
        $tcs = [
            ['moneda' => 'UF',  'valor' => 39729.0000],
            ['moneda' => 'USD', 'valor' =>   950.0000],
            ['moneda' => 'EUR', 'valor' =>  1020.0000],
        ];
        foreach ($tcs as $tc) {
            $moneda = $this->db->where('codigo', $tc['moneda'])->get('gmc_monedas')->row_array();
            if (!$moneda) continue;
            $exists = $this->db->where(['moneda_id' => $moneda['id'], 'fecha' => $hoy])
                               ->count_all_results('gmc_tipos_cambio');
            if (!$exists) {
                $this->db->insert('gmc_tipos_cambio', [
                    'moneda_id' => (int)$moneda['id'],
                    'fecha'     => $hoy,
                    'valor_clp' => $tc['valor'],
                ]);
            }
        }
    }

    public function down()
    {
        // Rollback de columnas
        foreach (['gmc_solicitudes_pago', 'gmc_compras', 'gmc_rindes_gastos'] as $t) {
            $this->db->query("DROP INDEX `idx_{$t}_monto_total_clp` ON `{$t}`");
            $this->db->query("ALTER TABLE `{$t}` DROP COLUMN `monto_total_clp`, DROP COLUMN `tipo_cambio_clp`");
        }
        // Borrar permisos asignados
        $this->db->query("
            DELETE rp FROM gmc_roles_permisos rp
            JOIN gmc_permisos p ON p.id = rp.permiso_id
            WHERE p.codigo IN ('maestros.tipo_cambio.ver','maestros.tipo_cambio.editar')
        ");
        $this->db->where_in('codigo', ['maestros.tipo_cambio.ver','maestros.tipo_cambio.editar'])
                 ->delete('gmc_permisos');
        // Borrar TCs sembrados (sólo del día de migración - heurístico, mejor mantener)
    }

    private function _assign(string $rolCodigo, array $permisoCodigos): void
    {
        $rol = $this->db->where('codigo', $rolCodigo)->get('gmc_roles')->row_array();
        if (!$rol) return;
        foreach ($permisoCodigos as $pc) {
            $perm = $this->db->where('codigo', $pc)->get('gmc_permisos')->row_array();
            if (!$perm) continue;
            $exists = $this->db->where(['rol_id' => $rol['id'], 'permiso_id' => $perm['id']])
                               ->count_all_results('gmc_roles_permisos');
            if (!$exists) {
                $this->db->insert('gmc_roles_permisos', [
                    'rol_id'     => (int)$rol['id'],
                    'permiso_id' => (int)$perm['id'],
                ]);
            }
        }
    }
}
