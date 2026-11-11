<?php

namespace App\Enums;

enum GastoEstado: string
{
    case Registrado = 'registrado';
    case Anulado = 'anulado';

    public function label(): string
    {
        return match ($this) {
            self::Registrado => 'Registrado',
            self::Anulado => 'Anulado',
        };
    }
}
