<?php

namespace App\Actions\Dashboard\Widgets;

use App\Actions\Dashboard\DashboardWidget;
use App\Constants\CacheKeys;
use App\Enums\PedidoEstado;
use App\Models\Pedido;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Cache;

class PrepPromedioWidget implements DashboardWidget
{
    public function key(): string
    {
        return CacheKeys::PREP_PROMEDIO;
    }

    public function ttl(): CarbonInterface
    {
        return now()->addMinutes(5);
    }

    public function compute(): array
    {
        $minutos = Cache::remember($this->key(), $this->ttl(), function () {
            $pedidos = Pedido::where('estado', PedidoEstado::Entregado)
                ->where('fecha', '>=', now()->subDays(7))
                ->with('venta')
                ->get();

            if ($pedidos->isEmpty()) {
                return 0;
            }

            $totalMinutos = $pedidos->sum(function ($p) {
                $deliveryTime = $p->venta?->created_at ?? $p->fecha;
                if (! $deliveryTime || ! $p->fecha) {
                    return 0;
                }

                return max(0, $p->fecha->diffInMinutes($deliveryTime));
            });

            return (int) round($totalMinutos / $pedidos->count());
        });

        return [
            'minutos' => $minutos,
        ];
    }
}
