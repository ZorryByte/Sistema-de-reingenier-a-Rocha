<?php

namespace App\Actions;

use App\Enums\PedidoEstado;
use App\Models\Pedido;
use Illuminate\Support\Facades\DB;

class MarcarOrdenLista
{
    public function execute(Pedido $pedido, int $userId): Pedido
    {
        if (! $pedido->esOrden()) {
            throw new \RuntimeException('Solo las órdenes pueden marcarse como lista');
        }

        if ($pedido->estado !== PedidoEstado::EnPreparacion) {
            throw new \RuntimeException('La orden debe estar en preparación');
        }

        return DB::transaction(function () use ($pedido, $userId) {
            $pedido = Pedido::lockForUpdate()->find($pedido->id);

            if ($pedido->estado !== PedidoEstado::EnPreparacion) {
                throw new \RuntimeException('Estado cambió. Recarga la página.');
            }

            $estadoAnterior = $pedido->estado;
            $pedido->update(['estado' => PedidoEstado::OrdenLista]);
            (new RegistrarEventoPedido)->execute(
                $pedido,
                'orden_lista',
                $userId,
                $estadoAnterior,
                PedidoEstado::OrdenLista,
            );

            return $pedido;
        });
    }
}
