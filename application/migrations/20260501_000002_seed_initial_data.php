<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Migración 2 — pueblan datos iniciales (geografía, monedas, estados,
 * roles, permisos, matriz roles_permisos, usuario admin, tipos de gasto,
 * centro de costo "Administración" y correlativos).
 *
 * Carga application/migrations/sql/seed_data.sql.
 */
class Migration_Seed_initial_data extends CI_Migration
{
    public function up()
    {
        $sqlFile = APPPATH . 'migrations/sql/seed_data.sql';
        if (!file_exists($sqlFile)) {
            show_error('No se encuentra el seed: ' . $sqlFile);
        }

        $sql = file_get_contents($sqlFile);

        // Limpieza de comentarios
        $sql = preg_replace('/^\s*--.*$/m', '', $sql);
        $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);

        // Para INSERTs multivaluados con strings que contienen ';' usar un
        // split más cuidadoso: respetamos comillas.
        $statements = $this->split_sql($sql);

        foreach ($statements as $stmt) {
            $stmt = trim($stmt);
            if ($stmt === '') continue;
            $this->db->query($stmt);
        }
    }

    public function down()
    {
        // Borra los datos de seed, pero conserva las tablas.
        $this->db->query('SET FOREIGN_KEY_CHECKS = 0');

        $tables = [
            'gmc_correlativos',
            'gmc_centros_costo',
            'gmc_tipos_gasto',
            'gmc_usuarios_roles',
            'gmc_usuarios',
            'gmc_roles_permisos',
            'gmc_permisos',
            'gmc_roles',
            'gmc_estados',
            'gmc_tipos_cambio',
            'gmc_monedas',
            'gmc_comunas',
            'gmc_regiones',
            'gmc_paises',
        ];
        foreach ($tables as $t) {
            $this->db->query("DELETE FROM `{$t}`");
            // Si quieres reiniciar AI: $this->db->query("ALTER TABLE `{$t}` AUTO_INCREMENT = 1");
        }

        $this->db->query('SET FOREIGN_KEY_CHECKS = 1');
    }

    /**
     * Divide un script SQL en sentencias respetando comillas simples y dobles.
     */
    private function split_sql(string $sql): array
    {
        $statements = [];
        $buffer = '';
        $in_single = false;
        $in_double = false;
        $len = strlen($sql);

        for ($i = 0; $i < $len; $i++) {
            $c = $sql[$i];
            $prev = $i > 0 ? $sql[$i - 1] : '';

            if (!$in_double && $c === "'" && $prev !== '\\') $in_single = !$in_single;
            if (!$in_single && $c === '"' && $prev !== '\\') $in_double = !$in_double;

            if ($c === ';' && !$in_single && !$in_double) {
                $statements[] = $buffer;
                $buffer = '';
            } else {
                $buffer .= $c;
            }
        }
        if (trim($buffer) !== '') $statements[] = $buffer;

        return $statements;
    }
}
