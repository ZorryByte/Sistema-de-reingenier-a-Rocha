<?php

namespace App\Actions\Dashboard\Widgets;

use App\Actions\Dashboard\DashboardWidget;
use App\Constants\CacheKeys;
use App\Enums\VentaEstado;
use App\Models\VentaDetalle;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Cache;

class TopProductosWidget implements DashboardWidget
{
    public function key(): string
    {
        return CacheKeys::TOP_PRODUCTOS;
    }

    public function ttl(): CarbonInterface
    {
        return now()->addMinutes(2);
    }

    public function compute(): array
    {
        $items = Cache::remember($this->key(), $this->ttl(), function () {
            return VentaDetalle::selectRaw('p.nombre, SUM(vd.cantidad) as total_vendido, SUM(vd.subtotal) as total_ingreso')
                ->from('venta_detalles as vd')
                ->join('presentaciones_producto as pp', 'vd.presentacion_id', '=', 'pp.id')
                ->join('productos as p', 'pp.producto_id', '=', 'p.id')
                ->join('ventas as v', 'vd.venta_id', '=', 'v.id')
                ->where('v.estado', VentaEstado::Pagada)
                ->whereDate('v.created_at', today())
                ->groupBy('p.id', 'p.nombre')
                ->orderByDesc('total_vendido')
                ->limit(4)
                ->get()
                ->map(fn ($row) => [
                    'nombre' => $row->nombre,
                    'total_vendido' => (float) $row->total_vendido,
                    'total_ingreso' => round((float) $row->total_ingreso, 2),
                ])
                ->values()
                ->toArray();
        });

        return [
            'items' => $items,
        ];
    }
}
