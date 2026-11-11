<?php

namespace App\Enums;

enum GastoTipo: string
{
    case Fijo = 'fijo';
    case Variable = 'variable';

    public function label(): string
    {
        return match ($this) {
            self::Fijo => 'Fijo',
            self::Variable => 'Variable',
        };
    }
}
