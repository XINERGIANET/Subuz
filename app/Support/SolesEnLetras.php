<?php

namespace App\Support;

/**
 * Monto en letras (soles) para pie de comprobante SUNAT.
 */
class SolesEnLetras
{
    private const UNIDADES = [
        '', 'UNO', 'DOS', 'TRES', 'CUATRO', 'CINCO', 'SEIS', 'SIETE', 'OCHO', 'NUEVE',
    ];

    private const DECENAS = [
        '', '', 'VEINTE', 'TREINTA', 'CUARENTA', 'CINCUENTA', 'SESENTA', 'SETENTA', 'OCHENTA', 'NOVENTA',
    ];

    private const CENTENAS = [
        '', 'CIENTO', 'DOSCIENTOS', 'TRESCIENTOS', 'CUATROCIENTOS', 'QUINIENTOS', 'SEISCIENTOS',
        'SETECIENTOS', 'OCHOCIENTOS', 'NOVECIENTOS',
    ];

    public static function format(float $amount): string
    {
        $amount = round($amount, 2);
        $formatted = number_format($amount, 2, '.', '');
        [$intPart, $decPart] = array_pad(explode('.', $formatted, 2), 2, '00');
        $enteros = (int) $intPart;
        $centavos = (int) substr(str_pad($decPart, 2, '0'), 0, 2);

        return strtoupper(trim(self::enteros($enteros))) . ' CON ' . str_pad((string) $centavos, 2, '0', STR_PAD_LEFT) . '/100 SOLES';
    }

    private static function enteros(int $n): string
    {
        if ($n === 0) {
            return 'CERO';
        }
        if ($n < 0) {
            return 'MENOS ' . self::enteros(-$n);
        }

        $millones = (int) floor($n / 1000000);
        $rest = $n % 1000000;
        $miles = (int) floor($rest / 1000);
        $cientos = $rest % 1000;

        $parts = [];

        if ($millones > 0) {
            $parts[] = $millones === 1
                ? 'UN MILLON'
                : trim(self::milesBlock($millones)) . ' MILLONES';
        }

        if ($miles > 0) {
            $parts[] = $miles === 1 ? 'MIL' : trim(self::milesBlock($miles)) . ' MIL';
        }

        if ($cientos > 0) {
            $parts[] = self::centenasBlock($cientos);
        }

        return trim(implode(' ', array_filter($parts))) ?: 'CERO';
    }

    private static function milesBlock(int $n): string
    {
        return self::centenasBlock($n);
    }

    private static function centenasBlock(int $n): string
    {
        if ($n === 0) {
            return '';
        }
        if ($n === 100) {
            return 'CIEN';
        }

        $c = (int) floor($n / 100);
        $r = $n % 100;
        $out = '';

        if ($c > 0) {
            $out = self::CENTENAS[$c];
        }
        if ($r === 0) {
            return $out;
        }
        if ($c > 0) {
            $out .= ' ';
        }

        return $out . self::bajo100($r);
    }

    private static function bajo100(int $n): string
    {
        if ($n < 10) {
            return self::UNIDADES[$n];
        }
        if ($n <= 15) {
            $map = [
                10 => 'DIEZ', 11 => 'ONCE', 12 => 'DOCE', 13 => 'TRECE', 14 => 'CATORCE', 15 => 'QUINCE',
            ];

            return $map[$n];
        }
        if ($n < 20) {
            return 'DIECI' . self::UNIDADES[$n - 10];
        }
        if ($n === 20) {
            return 'VEINTE';
        }
        if ($n < 30) {
            return 'VEINTI' . self::UNIDADES[$n - 20];
        }

        $d = (int) floor($n / 10);
        $u = $n % 10;
        if ($u === 0) {
            return self::DECENAS[$d];
        }

        return self::DECENAS[$d] . ' Y ' . self::UNIDADES[$u];
    }
}
