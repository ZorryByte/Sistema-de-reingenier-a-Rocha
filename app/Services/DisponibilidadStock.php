<?php

namespace App\Services;

use App\Enums\PedidoEstado;
use App\Models\PedidoDetalle;
use App\Models\PresentacionProducto;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class DisponibilidadStock
{
    public static function bloquearPresentaciones(iterable $ids): Collection
    {
        return PresentacionProducto::whereKey(collect($ids)->unique()->values()->all())
            ->orderBy('id')->lockForUpdate()->get()->keyBy('id');
    }

    public static function disponibleBloqueado(PresentacionProducto $presentacion, ?int $pedidoId = null): float
    {
        // Lectura actual de InnoDB, incluso si la transacción ya tiene una instantánea.
        $reservado = self::reservas([$presentacion->id], $pedidoId)
            ->lockForUpdate()->pluck('pedido_detalles.cantidad')->sum();

        return round((float) $presentacion->stock_actual - $reservado, 4);
    }

    public static function paraCatalogo(Collection $presentaciones, ?int $pedidoId = null): Collection
    {
        $reservado = self::reservas($presentaciones->modelKeys(), $pedidoId)
            ->groupBy('pedido_detalles.presentacion_id')
            ->selectRaw('pedido_detalles.presentacion_id, SUM(pedido_detalles.cantidad) AS reservado')
            ->pluck('reservado', 'presentacion_id');

        return $presentaciones->mapWithKeys(fn ($presentacion) => [
            $presentacion->id => max(0, round((float) $presentacion->stock_actual - ($reservado[$presentacion->id] ?? 0), 4)),
        ]);
    }

    private static function reservas(array $ids, ?int $pedidoId): Builder
    {
        return PedidoDetalle::query()
            ->join('pedidos', 'pedidos.id', '=', 'pedido_detalles.pedido_id')
            ->whereIn('pedido_detalles.presentacion_id', $ids)
            ->whereIn('pedidos.estado', [PedidoEstado::EnPreparacion, PedidoEstado::OrdenLista])
            ->when($pedidoId !== null, fn ($query) => $query->where('pedidos.id', '!=', $pedidoId));
    }
}
