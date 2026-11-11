<?php

namespace App\Support;

class UnitFormatter
{
    public static function short(?string $unit): string
    {
        return $unit === 'unidad' ? 'und.' : (string) $unit;
    }

    public static function units(): string
    {
        return 'unds.';
    }
}
