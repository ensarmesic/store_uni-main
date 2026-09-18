<?php

namespace App\Services;

class EuSize
{
    public static function number(string $value): ?float
    {
        $value = trim(str_replace([',', '⅓', '⅔', '½'], ['.', ' 1/3', ' 2/3', '.5'], $value));
        if (! preg_match('/^(\d{2})(?:\s+([12])\/3|(\.\d+))?$/', $value, $match)) return null;
        $size = (float) $match[1] + (! empty($match[2]) ? (int) $match[2] / 3 : (float) ($match[3] ?? 0));
        return $size >= 15 && $size <= 55 ? $size : null;
    }

    public static function label(float $value): string
    {
        $whole = (int) floor($value);
        $fraction = $value - $whole;
        if (abs($fraction - 1/3) < .04) return $whole.' 1/3';
        if (abs($fraction - 2/3) < .04) return $whole.' 2/3';
        return rtrim(rtrim(number_format($value, 1, '.', ''), '0'), '.');
    }
}
