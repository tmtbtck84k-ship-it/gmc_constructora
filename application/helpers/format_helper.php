<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Helpers de formateo: dinero, fechas, números, escape.
 */

if (!function_exists('format_clp')) {
    /**
     * '$ 1.234.567' o '$ 0' si null.
     */
    function format_clp($value): string
    {
        $v = is_null($value) ? 0 : (float)$value;
        return '$ ' . number_format($v, 0, ',', '.');
    }
}

if (!function_exists('format_money')) {
    function format_money($value, int $decimals = 0, string $simbolo = '$'): string
    {
        $v = is_null($value) ? 0 : (float)$value;
        return $simbolo . ' ' . number_format($v, $decimals, ',', '.');
    }
}

if (!function_exists('format_uf')) {
    function format_uf($value): string
    {
        $v = is_null($value) ? 0 : (float)$value;
        return number_format($v, 4, ',', '.') . ' UF';
    }
}

if (!function_exists('format_date')) {
    /**
     * 'YYYY-MM-DD' → 'DD-MM-YYYY' (UI chilena).
     */
    function format_date($value, string $format = 'd-m-Y'): string
    {
        if (!$value) return '';
        $ts = is_numeric($value) ? (int)$value : strtotime($value);
        if (!$ts) return '';
        return date($format, $ts);
    }
}

if (!function_exists('format_datetime')) {
    function format_datetime($value): string
    {
        return format_date($value, 'd-m-Y H:i');
    }
}

if (!function_exists('e')) {
    /**
     * Escape HTML rápido.
     */
    function e($v): string
    {
        return htmlspecialchars((string)$v, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}

if (!function_exists('badge_estado')) {
    /**
     * Pinta un badge Bootstrap a partir de un row de gmc_estados.
     */
    function badge_estado(?array $estado): string
    {
        if (!$estado) return '';
        $color = $estado['color'] ?? 'secondary';
        return '<span class="badge bg-' . e($color) . '">' . e($estado['nombre']) . '</span>';
    }
}
