<?php

namespace App\Actions;

use App\Exceptions\StockNoDisponible;
use App\Models\KardexInventario;
use App\Models\PresentacionProducto;
use App\Models\Venta;
use App\Models\VentaDetalle;
use App\Services\DisponibilidadStock;
use RuntimeException;

class DescontarStock
{
    public function execute(Venta $venta): void
    {
        // Nested tx removed - outer tx handles commit
        foreach ($venta->detalles as $detalle) {
            $this->descontarStockPresentacion($detalle, $venta);
        }
    }

    public function revertir(Venta $venta): void
    {
        // Nested tx removed - outer tx handles commit
        foreach ($venta->detalles as $detalle) {
            $this->revertirStockPresentacion($detalle, $venta);
        }
    }

    private function descontarStockPresentacion(VentaDetalle $detalle, Venta $venta): void
    {
        $presentacion = PresentacionProducto::lockForUpdate()
            ->find($detalle->presentacion_id);

        if ($presentacion === null) {
            throw new RuntimeException("Presentación #{$detalle->presentacion_id} no encontrada");
        }

        $cantidadVendida = $detalle->cantidad;
        $stockActual = (float) ($presentacion->stock_actual ?? 0);

        $disponible = DisponibilidadStock::disponibleBloqueado($presentacion, $venta->pedido_id);
        if ($disponible < $cantidadVendida) {
            throw new StockNoDisponible(
                "Stock insuficiente de \"{$presentacion->nombre}\": ".
                "necesario {$cantidadVendida}, disponible {$disponible}."
            );
        }

        $stockAnterior = $stockActual;
        $presentacion->decrement('stock_actual', $cantidadVendida);
        $stockResultante = $stockAnterior - $cantidadVendida;

        KardexInventario::create([
            'tipo' => 'salida',
            'cantidad' => $cantidadVendida,
            'stock_anterior' => $stockAnterior,
            'stock_resultante' => $stockResultante,
            'referencia_type' => PresentacionProducto::class,
            'referencia_id' => $presentacion->id,
            'venta_id' => $venta->id,
            'pedido_id' => $venta->pedido_id,
            'user_id' => $venta->user_id,
            'origen_movimiento' => 'venta',
            'reversible' => true,
            'notas' => "Venta #{$venta->id} — {$presentacion->nombre}",
        ]);
    }

    private function revertirStockPresentacion(VentaDetalle $detalle, Venta $venta): void
    {
        $presentacion = PresentacionProducto::lockForUpdate()
            ->find($detalle->presentacion_id);

        if ($presentacion === null) {
            throw new RuntimeException("Presentación #{$detalle->presentacion_id} no encontrada");
        }

        $cantidadVendida = $detalle->cantidad;

        $stockAnterior = (float) $presentacion->stock_actual;
        $presentacion->increment('stock_actual', $cantidadVendida);
        $stockResultante = $stockAnterior + $cantidadVendida;

        KardexInventario::create([
            'tipo' => 'ajuste',
            'cantidad' => $cantidadVendida,
            'stock_anterior' => $stockAnterior,
            'stock_resultante' => $stockResultante,
            'referencia_type' => PresentacionProducto::class,
            'referencia_id' => $presentacion->id,
            'venta_id' => $venta->id,
            'pedido_id' => $venta->pedido_id,
            'user_id' => $venta->user_id,
            'origen_movimiento' => 'reversion',
            'reversible' => false,
            'notas' => "Reversión Venta #{$venta->id} — {$presentacion->nombre}",
        ]);
    }
}
