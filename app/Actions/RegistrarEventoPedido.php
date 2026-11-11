<?php

namespace App\Actions;

use App\Enums\PedidoEstado;
use App\Models\Pedido;
use App\Models\PedidoHistorial;

class RegistrarEventoPedido
{
    public function execute(
        Pedido $pedido,
        string $evento,
        int $userId,
        PedidoEstado|string|null $estadoAnterior = null,
        PedidoEstado|string|null $estadoNuevo = null,
        array $cambios = [],
    ): PedidoHistorial {
        return PedidoHistorial::create([
            'pedido_id' => $pedido->id,
            'user_id' => $userId,
            'evento' => $evento,
            'estado_anterior' => $estadoAnterior instanceof PedidoEstado ? $estadoAnterior->value : $estadoAnterior,
            'estado_nuevo' => $estadoNuevo instanceof PedidoEstado ? $estadoNuevo->value : $estadoNuevo,
            'cambios' => $cambios ?: null,
            'created_at' => now(),
        ]);
    }
}
