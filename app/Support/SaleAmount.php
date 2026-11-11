<?php

namespace App\Support;

final class SaleAmount
{
    public static function cents(mixed $amount): int
    {
        return (int) round((float) $amount * 100);
    }

    public static function grossCents(mixed $price, mixed $quantity): int
    {
        $priceCents = self::cents($price);
        $quantityUnits = (int) round((float) $quantity * 10000);

        return $priceCents * intdiv($quantityUnits, 10000)
            + intdiv($priceCents * ($quantityUnits % 10000) + 5000, 10000);
    }

    public static function lineCents(mixed $price, mixed $quantity, mixed $discount = 0): int
    {
        return max(0, self::grossCents($price, $quantity) - self::cents($discount));
    }
}
