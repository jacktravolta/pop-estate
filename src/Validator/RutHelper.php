<?php
namespace App\Validator;

/**
 * RutHelper - normaliza RUT a formato canónico 12.345.678-9
 * Evita duplicados 14137654-3 vs 14.137.654-3
 */
class RutHelper
{
    public static function clean(string $rut): string
    {
        return strtoupper(preg_replace('/[^0-9kK]/', '', $rut));
    }

    public static function format(string $rut): string
    {
        $c = self::clean($rut);
        if (strlen($c) <= 1) return $c;
        $body = substr($c, 0, -1);
        $dv = substr($c, -1);
        // quita ceros a la izquierda del cuerpo? no
        $body = ltrim($body, '0');
        if ($body === '') $body = '0';
        $formattedBody = '';
        $len = strlen($body);
        for ($i = 0; $i < $len; $i++) {
            if ($i > 0 && ($len - $i) % 3 === 0) $formattedBody .= '.';
            $formattedBody .= $body[$i];
        }
        return $formattedBody . '-' . $dv;
    }

    public static function isValid(string $rut): bool
    {
        $c = self::clean($rut);
        if (strlen($c) < 2) return false;
        $body = substr($c, 0, -1);
        $dv = substr($c, -1);
        if (strlen($body) < 7) return false;
        $sum = 0; $mul = 2;
        for ($i = strlen($body) - 1; $i >= 0; $i--) {
            $sum += intval($body[$i]) * $mul;
            $mul = $mul === 7 ? 2 : $mul + 1;
        }
        $res = 11 - ($sum % 11);
        $dvr = $res === 11 ? '0' : ($res === 10 ? 'K' : (string)$res);
        return $dvr === $dv;
    }
}
