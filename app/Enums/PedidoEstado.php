<?php

namespace App\Enums;

// Enum for type-safe pedido estados
enum PedidoEstado: string
{
    case EnPreparacion = 'en preparacion';
    case OrdenLista = 'orden lista';
    case Entregado = 'entregado';
    case Cancelado = 'cancelado';

    public function label(): string
    {
        return match ($this) {
            self::EnPreparacion => 'En Preparación',
            self::OrdenLista => 'Orden Lista',
            self::Entregado => 'Entregado',
            self::Cancelado => 'Cancelado',
        };
    }
}
