<?php

namespace App\Actions;

use App\Models\Compra;
use App\Models\CompraDetalle;
use App\Models\Ingrediente;
use App\Models\KardexInventario;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ProcesarCompra
{
    public function execute(array $data, int $userId): Compra
    {
        return DB::transaction(function () use ($data, $userId) {
            $detalles = $this->normalizarDetalles($data['detalles']);
            $total = $detalles->sum('subtotal');

            $compra = new Compra([
                'proveedor_id' => $data['proveedor_id'] ?? null,
                'fecha' => $data['fecha'] ?? now(),
                'notas' => $data['notas'] ?? null,
            ]);
            $compra->user_id = $userId;
            $compra->total = $total;
            $compra->save();

            $ids = $detalles->pluck('ingrediente_id')->unique();
            $ingredientes = Ingrediente::whereIn('id', $ids)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($detalles as $detalle) {
                $ingrediente = $ingredientes->get($detalle['ingrediente_id']);
                if (! $ingrediente) {
                    throw new RuntimeException('Ingrediente no encontrado.');
                }

                CompraDetalle::create([
                    'compra_id' => $compra->id,
                    'ingrediente_id' => $ingrediente->id,
                    'cantidad' => $detalle['cantidad'],
                    'precio_unitario' => $detalle['precio_unitario'],
                    'subtotal' => $detalle['subtotal'],
                ]);

                $stockAnterior = (float) $ingrediente->stock_actual;
                $costoAnterior = (float) $ingrediente->costo_unitario;
                $stockNuevo = $stockAnterior + $detalle['cantidad'];

                $valorActual = $stockAnterior * $costoAnterior;
                $nuevoCosto = $stockNuevo > 0
                    ? ($valorActual + $detalle['subtotal']) / $stockNuevo
                    : $detalle['precio_unitario'];

                $ingrediente->update([
                    'stock_actual' => $stockNuevo,
                    'costo_unitario' => $nuevoCosto,
                ]);

                KardexInventario::create([
                    'tipo' => 'entrada',
                    'ingrediente_id' => $ingrediente->id,
                    'cantidad' => $detalle['cantidad'],
                    'stock_anterior' => $stockAnterior,
                    'stock_resultante' => $stockNuevo,
                    'costo_anterior' => $costoAnterior,
                    'costo_resultante' => $nuevoCosto,
                    'compra_id' => $compra->id,
                    'user_id' => $userId,
                    'origen_movimiento' => 'compra',
                    'reversible' => true,
                    'referencia_type' => Compra::class,
                    'referencia_id' => $compra->id,
                    'notas' => "Compra #{$compra->id} - {$ingrediente->nombre}",
                ]);
            }

            return $compra;
        });
    }

    private function normalizarDetalles(array $detalles): Collection
    {
        return collect($detalles)->map(function (array $detalle) {
            $cantidad = (float) $detalle['cantidad'];
            $precio = (float) $detalle['precio_unitario'];

            if ($cantidad <= 0) {
                throw new RuntimeException('La cantidad debe ser mayor a 0.');
            }
            if ($precio <= 0) {
                throw new RuntimeException('El precio unitario debe ser mayor a 0.');
            }

            return [
                'ingrediente_id' => (int) $detalle['ingrediente_id'],
                'cantidad' => $cantidad,
                'precio_unitario' => $precio,
                'subtotal' => round($cantidad * $precio, 2),
            ];
        });
    }
}
