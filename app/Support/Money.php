<?php

namespace App\Support;

use InvalidArgumentException;

class Money
{
    public static function cents(int|float|string|null $amount): int
    {
        if ($amount === null || $amount === '') {
            return 0;
        }

        $normalized = trim((string) $amount);
        if (! preg_match('/^-?\d+(?:\.\d{1,2})?$/', $normalized)) {
            throw new InvalidArgumentException('El importe monetario no es válido.');
        }

        $negative = str_starts_with($normalized, '-');
        $normalized = ltrim($normalized, '-');
        [$units, $decimals] = array_pad(explode('.', $normalized, 2), 2, '');
        $cents = ((int) $units * 100) + (int) str_pad(substr($decimals, 0, 2), 2, '0');

        return $negative ? -$cents : $cents;
    }

    public static function decimalFromCents(int $cents): string
    {
        $sign = $cents < 0 ? '-' : '';
        $absolute = abs($cents);

        return $sign.intdiv($absolute, 100).'.'.str_pad((string) ($absolute % 100), 2, '0', STR_PAD_LEFT);
    }
}
