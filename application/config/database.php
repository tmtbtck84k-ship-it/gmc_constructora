<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$env = require __DIR__ . '/.env.php';

$active_group = 'default';
$query_builder = TRUE;

$db['default'] = [
    'dsn'      => '',
    'hostname' => $env['DB_HOSTNAME'] ?? 'localhost',
    'port'     => (int)($env['DB_PORT'] ?? 3306),
    'username' => $env['DB_USERNAME'] ?? '',
    'password' => $env['DB_PASSWORD'] ?? '',
    'database' => $env['DB_DATABASE'] ?? 'gmc_erp',
    'dbdriver' => 'mysqli',
    'dbprefix' => '',
    'pconnect' => FALSE,
    'db_debug' => (($env['ENVIRONMENT'] ?? 'development') !== 'production'),
    'cache_on' => FALSE,
    'cachedir' => '',
    'char_set' => $env['DB_CHARSET'] ?? 'utf8mb4',
    'dbcollat' => $env['DB_COLLATE'] ?? 'utf8mb4_unicode_ci',
    'swap_pre' => '',
    'encrypt'  => FALSE,
    'compress' => FALSE,
    'stricton' => TRUE,        // STRICT_ALL_TABLES
    'failover' => [],
    'save_queries' => (($env['ENVIRONMENT'] ?? 'development') !== 'production'),
];
