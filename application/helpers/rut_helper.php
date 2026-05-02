<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Helper de RUT chileno.
 * Almacena en BD: 12345678-9 (sin puntos, con guión).
 * Muestra al usuario: 12.345.678-9 (con puntos).
 */

if (!function_exists('normalizar_rut')) {
    /**
     * Limpia y normaliza un RUT al formato '12345678-9'.
     * Acepta entradas con puntos, espacios, guión o sin guión.
     */
    function normalizar_rut(?string $rut): string
    {
        if (!$rut) return '';
        $clean = preg_replace('/[^0-9kK]/', '', $rut);
        if (strlen($clean) < 2) return '';
        $dv = strtoupper(substr($clean, -1));
        $num = substr($clean, 0, -1);
        $num = ltrim($num, '0') ?: '0';
        return "{$num}-{$dv}";
    }
}

if (!function_exists('formatear_rut')) {
    /**
     * Formatea para visualización: '12.345.678-9'.
     */
    function formatear_rut(?string $rut): string
    {
        $r = normalizar_rut($rut);
        if (!$r) return '';
        [$num, $dv] = explode('-', $r);
        return number_format((int)$num, 0, ',', '.') . '-' . $dv;
    }
}

if (!function_exists('validar_rut')) {
    /**
     * Valida RUT chileno con módulo 11.
     */
    function validar_rut(?string $rut): bool
    {
        $r = normalizar_rut($rut);
        if (!$r) return false;
        [$num, $dv] = explode('-', $r);
        if (!ctype_digit($num)) return false;

        $sum = 0;
        $factor = 2;
        for ($i = strlen($num) - 1; $i >= 0; $i--) {
            $sum += $num[$i] * $factor;
            $factor = ($factor === 7) ? 2 : $factor + 1;
        }
        $resto = $sum % 11;
        $expected = 11 - $resto;
        if ($expected === 11) $exp = '0';
        elseif ($expected === 10) $exp = 'K';
        else $exp = (string)$expected;

        return $exp === $dv;
    }
}

if (!function_exists('validar_rut_callback')) {
    /**
     * Callback para form_validation:
     *   $this->form_validation->set_rules('rut', 'RUT', 'required|callback_validar_rut_callback');
     */
    function validar_rut_callback($rut)
    {
        return validar_rut($rut);
    }
}
