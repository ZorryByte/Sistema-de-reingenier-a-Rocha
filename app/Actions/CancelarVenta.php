<?php

namespace App\Actions;

use App\Enums\VentaEstado;
use App\Models\Ingrediente;
use App\Models\KardexInventario;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Support\Facades\DB;

class CancelarVenta
{
    public function execute(Venta $venta, User $user, bool $restaurarStock = true): void
    {
        DB::transaction(function () use ($venta, $user, $restaurarStock) {
            if ($venta->estado === VentaEstado::Cancelada) {
                throw new \RuntimeException('Esta venta ya fue cancelada.');
            }

            (new RegistrarCancelacionContable)->execute($venta, $user);

            $venta->update(['estado' => VentaEstado::Cancelada]);

            $movimientos = $venta->movimientos()
                ->whereNull('reverted_at')
                ->lockForUpdate()
                ->get();

            foreach ($movimientos as $movimiento) {
                $movimiento->update([
                    'reverted_at' => now(),
                    'user_reverso_id' => $user->id,
                ]);

                if ($restaurarStock) {
                    $this->restaurarStockPorMovimiento($movimiento, $venta, $user);
                }
            }
        });
    }

    private function restaurarStockPorMovimiento(KardexInventario $movimiento, Venta $venta, User $user): void
    {
        $this->restaurarStockUnicoMovimiento(
            $movimiento,
            $user,
            $venta->id,
            $venta->pedido_id,
            "Cancelación Venta #{$venta->id}"
        );
    }

    public static function restaurarStockUnicoMovimiento(
        KardexInventario $movimiento,
        User $user,
        ?int $ventaId = null,
        ?int $pedidoId = null,
        ?string $notaBase = null
    ): void {
        if ($movimiento->ingrediente_id) {
            $ingrediente = Ingrediente::lockForUpdate()->find($movimiento->ingrediente_id);

            if ($ingrediente) {
                $stockAnterior = (float) $ingrediente->stock_actual;
                $delta = (float) $movimiento->stock_resultante - (float) $movimiento->stock_anterior;
                $stockResultante = $stockAnterior - $delta;

                if ($stockResultante < 0) {
                    throw new \RuntimeException("Stock insuficiente de \"{$ingrediente->nombre}\" para revertir.");
                }

                $costoAnterior = (float) $ingrediente->costo_unitario;
                $costoResultante = self::costoRevertido($movimiento, $costoAnterior);

                $ingrediente->update([
                    'stock_actual' => $stockResultante,
                    'costo_unitario' => $costoResultante,
                ]);

                KardexInventario::create([
                    'tipo' => 'ajuste',
                    'ingrediente_id' => $movimiento->ingrediente_id,
                    'cantidad' => abs($delta),
                    'stock_anterior' => $stockAnterior,
                    'stock_resultante' => $stockResultante,
                    'costo_anterior' => $costoAnterior,
                    'costo_resultante' => $costoResultante,
                    'venta_id' => $ventaId,
                    'pedido_id' => $pedidoId,
                    'user_id' => $user->id,
                    'origen_movimiento' => 'cancelacion',
                    'reversible' => false,
                    'notas' => $notaBase ? "{$notaBase} — {$ingrediente->nombre}" : null,
                ]);
            }
        } elseif ($movimiento->referencia_type && $movimiento->referencia_id) {
            $model = $movimiento->referencia_type;
            if (class_exists($model)) {
                $presentacion = $model::lockForUpdate()->find($movimiento->referencia_id);
                if ($presentacion && isset($presentacion->stock_actual)) {
                    $stockAnterior = (float) $presentacion->stock_actual;
                    $delta = (float) $movimiento->stock_resultante - (float) $movimiento->stock_anterior;
                    $stockResultante = $stockAnterior - $delta;

                    if ($stockResultante < 0) {
                        throw new \RuntimeException("Stock insuficiente de \"{$presentacion->nombre}\" para revertir.");
                    }

                    $presentacion->update(['stock_actual' => $stockResultante]);

                    KardexInventario::create([
                        'tipo' => 'ajuste',
                        'cantidad' => abs($delta),
                        'stock_anterior' => $stockAnterior,
                        'stock_resultante' => $stockResultante,
                        'referencia_type' => $movimiento->referencia_type,
                        'referencia_id' => $movimiento->referencia_id,
                        'venta_id' => $ventaId,
                        'pedido_id' => $pedidoId,
                        'user_id' => $user->id,
                        'origen_movimiento' => 'cancelacion',
                        'reversible' => false,
                        'notas' => $notaBase ? "{$notaBase} — {$presentacion->nombre}" : null,
                    ]);
                }
            }
        } else {
            \Log::warning('CancelarVenta: Movimiento sin identificadores de stock', [
                'movimiento_id' => $movimiento->id,
                'venta_id' => $ventaId,
                'user_id' => $user->id,
            ]);
        }
    }

    private static function costoRevertido(KardexInventario $movimiento, float $costoActual): float
    {
        if ($movimiento->costo_anterior === null || $movimiento->costo_resultante === null) {
            return $costoActual;
        }

        if (abs($costoActual - (float) $movimiento->costo_resultante) > 0.0001) {
            return $costoActual;
        }

        return (float) $movimiento->costo_anterior;
    }
}
