<?php

namespace App\Actions\Dashboard\Widgets;

use App\Actions\Dashboard\DashboardWidget;
use App\Constants\CacheKeys;
use App\Enums\PedidoEstado;
use App\Models\Pedido;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Cache;

class PedidosActivosWidget implements DashboardWidget
{
    public function key(): string
    {
        return CacheKeys::PEDIDOS_PENDIENTES;
    }

    public function ttl(): CarbonInterface
    {
        return now()->addMinute();
    }

    public function compute(): array
    {
        $count = Cache::remember($this->key(), $this->ttl(), function () {
            return (int) Pedido::tipoPedido()
                ->where('estado', PedidoEstado::EnPreparacion)
                ->count();
        });

        $items = Pedido::tipoPedido()
            ->where('estado', PedidoEstado::EnPreparacion)
            ->with('usuario')
            ->orderBy('fecha', 'asc')
            ->limit(5)
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'tipo' => $p->tipo,
                'estado' => $p->estado->label(),
                'usuario' => $p->usuario?->name ?? 'Sin usuario',
                'fecha' => $p->fecha?->format('Y-m-d H:i:s'),
                'minutos' => $p->fecha ? (int) now()->diffInMinutes($p->fecha) : 0,
            ])
            ->values()
            ->toArray();

        return [
            'count' => $count,
            'items' => $items,
        ];
    }
}
