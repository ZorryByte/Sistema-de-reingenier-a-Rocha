<?php

namespace App\Actions;

use App\Models\KardexInventario;
use App\Models\Pedido;
use App\Models\PresentacionProducto;
use Illuminate\Support\Facades\DB;

class LiberarReservaStock
{
    public function execute(Pedido $pedido, ?int $userId = null): void
    {
        DB::transaction(function () use ($pedido, $userId) {
            $actorId = $userId ?? $pedido->user_id;
            $reservas = KardexInventario::lockForUpdate()
                ->where('origen_movimiento', 'reserva')
                ->where('pedido_id', $pedido->id)
                ->whereNull('reverted_at')
                ->get();

            if ($reservas->isEmpty()) {
                return;
            }

            foreach ($reservas as $reserva) {
                $reserva->update([
                    'reverted_at' => now(),
                    'user_reverso_id' => $actorId,
                ]);
            }

            foreach ($pedido->detalles as $detalle) {
                $presentacion = PresentacionProducto::lockForUpdate()
                    ->find($detalle->presentacion_id);

                if ($presentacion === null) {
                    throw new \RuntimeException(
                        "Presentación #{$detalle->presentacion_id} no encontrada"
                    );
                }

                $stockActual = (float) $presentacion->stock_actual;

                KardexInventario::create([
                    'tipo' => 'ajuste',
                    'cantidad' => $detalle->cantidad,
                    'referencia_type' => Pedido::class,
                    'referencia_id' => $pedido->id,
                    'pedido_id' => $pedido->id,
                    'user_id' => $actorId,
                    'stock_anterior' => $stockActual,
                    'stock_resultante' => $stockActual,
                    'origen_movimiento' => 'liberacion',
                    'reversible' => false,
                    'notas' => "Liberar reserva pedido #{$pedido->id}",
                ]);
            }
        });
    }
}
