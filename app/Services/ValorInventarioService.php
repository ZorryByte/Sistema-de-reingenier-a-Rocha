<?php

namespace App\Services;

use App\Models\Ingrediente;
use App\Models\KardexInventario;
use App\Models\PresentacionProducto;

class ValorInventarioService
{
    public function resumen(): array
    {
        $ingredientes = Ingrediente::query()
            ->select(['id', 'nombre', 'stock_actual', 'costo_unitario', 'unidad_medida'])
            ->orderByDesc('stock_actual')
            ->get()
            ->map(fn ($ingrediente) => [
                'nombre' => $ingrediente->nombre,
                'tipo' => 'Ingrediente',
                'stock' => (float) $ingrediente->stock_actual,
                'costo' => (float) $ingrediente->costo_unitario,
                'valor' => (float) $ingrediente->stock_actual * (float) $ingrediente->costo_unitario,
                'unidad' => $ingrediente->unidad_medida,
            ]);

        $presentaciones = PresentacionProducto::query()
            ->with('producto')
            ->where('activo', true)
            ->orderByDesc('stock_actual')
            ->get()
            ->map(fn ($presentacion) => [
                'nombre' => trim(($presentacion->producto?->nombre ? $presentacion->producto->nombre.' - ' : '').$presentacion->nombre),
                'tipo' => 'Producto',
                'stock' => (float) $presentacion->stock_actual,
                'costo' => (float) ($presentacion->recetaActiva?->costoPorUnidad() ?? 0),
                'valor' => (float) $presentacion->stock_actual * (float) ($presentacion->recetaActiva?->costoPorUnidad() ?? 0),
                'unidad' => 'unidad',
            ]);

        $items = $ingredientes->merge($presentaciones)->sortByDesc('valor')->values();
        $movimientosMes = KardexInventario::activos()
            ->whereBetween('fecha', [now()->startOfMonth(), now()->endOfMonth()])
            ->count();

        return [
            'items' => $items,
            'total' => (float) $items->sum('valor'),
            'ingredientes' => (float) $ingredientes->sum('valor'),
            'productos' => (float) $presentaciones->sum('valor'),
            'movimientos_mes' => $movimientosMes,
        ];
    }
}
