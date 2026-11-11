<?php

namespace App\Actions;

use App\Models\KardexInventario;
use App\Models\Pedido;
use App\Services\DisponibilidadStock;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ReservarStock
{
    public function execute(Pedido $pedido, Collection $presentaciones, ?Collection $cantidadesAnteriores = null): void
    {
        DB::transaction(function () use ($pedido, $presentaciones, $cantidadesAnteriores) {
            foreach ($pedido->detalles as $detalle) {
                $presentacion = $presentaciones->get($detalle->presentacion_id);

                if ($presentacion === null) {
                    throw new RuntimeException(
                        "Presentación #{$detalle->presentacion_id} no encontrada para pedido #{$pedido->id}"
                    );
                }

                $cantidadAnterior = $cantidadesAnteriores?->get($presentacion->id, 0) ?? 0;
                if (! $presentacion->activo && $detalle->cantidad > $cantidadAnterior) {
                    throw new RuntimeException("La presentación \"{$presentacion->nombre}\" está inactiva.");
                }

                $stockDisponible = DisponibilidadStock::disponibleBloqueado($presentacion, $pedido->id);
                if ($stockDisponible < $detalle->cantidad) {
                    throw new RuntimeException(
                        "Stock insuficiente de \"{$presentacion->nombre}\": ".
                        "necesitas {$detalle->cantidad}, disponible {$stockDisponible}"
                    );
                }

                KardexInventario::create([
                    'tipo' => 'reserva',
                    'cantidad' => $detalle->cantidad,
                    'referencia_type' => Pedido::class,
                    'referencia_id' => $pedido->id,
                    'pedido_id' => $pedido->id,
                    'user_id' => $pedido->user_id,
                    'stock_anterior' => $presentacion->stock_actual,
                    'stock_resultante' => $presentacion->stock_actual,
                    'origen_movimiento' => 'reserva',
                    'reversible' => true,
                    'notas' => "Reserva pedido #{$pedido->id}",
                ]);
            }
        });
    }
}
