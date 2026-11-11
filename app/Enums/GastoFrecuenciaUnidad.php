<?php

namespace App\Enums;

enum GastoFrecuenciaUnidad: string
{
    case Dias = 'dias';
    case Semanas = 'semanas';
    case Meses = 'meses';

    public function label(): string
    {
        return match ($this) {
            self::Dias => 'Días',
            self::Semanas => 'Semanas',
            self::Meses => 'Meses',
        };
    }
}
