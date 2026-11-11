<?php

namespace App\Actions\Dashboard\Widgets;

use App\Actions\Dashboard\DashboardWidget;
use App\Constants\CacheKeys;
use App\Enums\PedidoEstado;
use App\Enums\VentaEstado;
use App\Models\Pedido;
use App\Models\Venta;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Cache;

class PedidosEntregadosWidget implements DashboardWidget
{
    public function key(): string
    {
        return CacheKeys::PEDIDOS_ENTREGADOS;
    }

    public function ttl(): CarbonInterface
    {
        return now()->addMinute();
    }

    public function compute(): array
    {
        $hoy = today();

        $count = Cache::remember($this->key(), $this->ttl(), function () use ($hoy) {
            return (int) Pedido::where('estado', PedidoEstado::Entregado)
                ->whereDate('fecha', $hoy)
                ->count();
        });

        $pedidoIds = Pedido::where('estado', PedidoEstado::Entregado)
            ->whereDate('fecha', $hoy)
            ->pluck('id');

        $totalVentas = (float) Venta::where('estado', VentaEstado::Pagada)
            ->whereIn('pedido_id', $pedidoIds)
            ->sum('total');

        $items = Pedido::where('estado', PedidoEstado::Entregado)
            ->whereDate('fecha', $hoy)
            ->with(['usuario', 'venta'])
            ->orderByDesc('fecha')
            ->limit(3)
            ->get()
            ->map(function ($p) {
                $deliveryTime = $p->venta?->created_at ?? $p->fecha;
                $minutos = $deliveryTime && $p->fecha
                    ? (int) $p->fecha->diffInMinutes($deliveryTime)
                    : 0;

                return [
                    'id' => $p->id,
                    'usuario' => $p->usuario?->name ?? 'Sin usuario',
                    'fecha' => $p->fecha?->format('Y-m-d H:i:s'),
                    'minutos' => $minutos,
                ];
            })
            ->values()
            ->toArray();

        return [
            'count' => $count,
            'total_ventas' => round($totalVentas, 2),
            'items' => $items,
        ];
    }
}
