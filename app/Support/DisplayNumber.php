<?php

namespace App\Support;

final class DisplayNumber
{
    public static function money(mixed $value): string
    {
        return self::currency($value, 2);
    }

    public static function unitCost(mixed $value): string
    {
        return self::currency($value, 4);
    }

    public static function quantity(mixed $value): string
    {
        return rtrim(rtrim(number_format((float) $value, 4), '0'), '.');
    }

    private static function currency(mixed $value, int $precision): string
    {
        $amount = round((float) $value, $precision);
        $formatted = $precision === 2
            ? number_format(abs($amount), 2)
            : self::quantity(abs($amount));

        return ($amount < 0 ? '-$' : '$').$formatted;
    }
}
