<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Migración inicial — crea el esquema completo de Fase 1.
 *
 * Esta migración carga el archivo SQL canonico `erp_gmc.sql` que vive
 * en application/migrations/sql/erp_gmc.sql. La fuente de verdad del
 * DDL es ese archivo; las migraciones futuras (alterar columnas, crear
 * tablas adicionales) se harán como migraciones granulares con dbforge
 * o SQL específicos.
 *
 * Para ejecutar:
 *   php index.php cli/migrate latest
 */
class Migration_Create_initial_schema extends CI_Migration
{
    public function up()
    {
        $sqlFile = APPPATH . 'migrations/sql/erp_gmc.sql';
        if (!file_exists($sqlFile)) {
            show_error('No se encuentra el DDL: ' . $sqlFile);
        }

        $sql = file_get_contents($sqlFile);

        // Eliminar comentarios -- y bloques /* */
        $sql = preg_replace('/^\s*--.*$/m', '', $sql);
        $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);

        // Separar por sentencias; cuidamos que ; dentro de cuerpos no rompa
        // (no hay TRIGGER/PROC en este DDL, así que un split simple es OK).
        $statements = array_filter(array_map('trim', explode(';', $sql)));

        foreach ($statements as $stmt) {
            if ($stmt === '' || stripos($stmt, 'SET ') === 0) {
                // SET NAMES, SET FK, etc. los aplicamos a través de la conexión.
                if ($stmt !== '') {
                    $this->db->query($stmt);
                }
                continue;
            }
            $this->db->query($stmt);
        }
    }

    public function down()
    {
        // Rollback: borra todas las tablas con prefijo gmc_ del esquema.
        // Se hace con FK_CHECKS=0 para evitar problemas de orden.
        $this->db->query('SET FOREIGN_KEY_CHECKS = 0');

        $tables = [
            'gmc_notificaciones', 'gmc_auditoria_logs', 'gmc_adjuntos',
            'gmc_cierres_obra', 'gmc_bitacoras',
            'gmc_rinde_items', 'gmc_rindes_gastos',
            'gmc_compras_items', 'gmc_compras',
            'gmc_sdp_estados_log', 'gmc_solicitudes_pago',
            'gmc_presupuestos_obra_items', 'gmc_presupuestos_obra',
            'gmc_tipos_gasto', 'gmc_centros_costo', 'gmc_proyectos',
            'gmc_proveedores', 'gmc_clientes',
            'gmc_login_attempts', 'gmc_sesiones',
            'gmc_usuarios_roles', 'gmc_usuarios',
            'gmc_roles_permisos', 'gmc_permisos', 'gmc_roles',
            'gmc_correlativos', 'gmc_estados',
            'gmc_tipos_cambio', 'gmc_monedas',
            'gmc_comunas', 'gmc_regiones', 'gmc_paises',
        ];
        foreach ($tables as $t) {
            $this->db->query("DROP TABLE IF EXISTS `{$t}`");
        }

        $this->db->query('SET FOREIGN_KEY_CHECKS = 1');
    }
}
