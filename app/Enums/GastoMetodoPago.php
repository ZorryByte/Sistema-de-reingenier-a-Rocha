<?php

namespace App\Enums;

enum GastoMetodoPago: string
{
    case Efectivo = 'efectivo';
    case Tarjeta = 'tarjeta';
    case Transferencia = 'transferencia';
    case Cheque = 'cheque';

    public function label(): string
    {
        return match ($this) {
            self::Efectivo => 'Efectivo',
            self::Tarjeta => 'Tarjeta',
            self::Transferencia => 'Transferencia',
            self::Cheque => 'Cheque',
        };
    }
}
