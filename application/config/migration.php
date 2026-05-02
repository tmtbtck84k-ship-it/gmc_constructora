<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| Configuración de migraciones — colocar en application/config/migration.php
|--------------------------------------------------------------------------
| Habilita migraciones por timestamp y configura rutas.
*/

$config['migration_enabled']    = TRUE;
$config['migration_type']       = 'timestamp';   // formato YYYYMMDD_HHMMSS_descripcion
$config['migration_table']      = 'migrations';
$config['migration_auto_latest']= FALSE;         // false en prod; usar CLI manual
$config['migration_version']    = 0;             // ignorado con type=timestamp
$config['migration_path']       = APPPATH . 'migrations/';
