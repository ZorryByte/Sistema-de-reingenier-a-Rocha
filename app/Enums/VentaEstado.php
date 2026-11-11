<?php

namespace App\Enums;

enum VentaEstado: string
{
    case Pagada = 'pagada';
    case Cancelada = 'cancelada';

    public function label(): string
    {
        return match ($this) {
            self::Pagada => 'Pagada',
            self::Cancelada => 'Cancelada',
        };
    }
}
