<?php

namespace App\Actions\Dashboard\Widgets;

use App\Actions\Dashboard\DashboardWidget;
use App\Constants\CacheKeys;
use App\Enums\PedidoEstado;
use App\Models\Pedido;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Cache;

class OrdenesActivasWidget implements DashboardWidget
{
    public function key(): string
    {
        return CacheKeys::ORDENES_ACTIVAS;
    }

    public function ttl(): CarbonInterface
    {
        return now()->addMinute();
    }

    public function compute(): array
    {
        $count = Cache::remember($this->key(), $this->ttl(), function () {
            return (int) Pedido::tipoOrden()
                ->whereIn('estado', [PedidoEstado::EnPreparacion, PedidoEstado::OrdenLista])
                ->count();
        });

        $items = Pedido::tipoOrden()
            ->whereIn('estado', [PedidoEstado::EnPreparacion, PedidoEstado::OrdenLista])
            ->with('usuario')
            ->orderBy('fecha', 'asc')
            ->limit(5)
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'tipo' => $p->tipo,
                'usuario' => $p->usuario?->name ?? 'Sin usuario',
                'estado' => $p->estado->label(),
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
