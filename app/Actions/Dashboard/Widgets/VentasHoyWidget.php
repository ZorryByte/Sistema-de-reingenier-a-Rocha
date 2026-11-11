<?php

namespace App\Actions\Dashboard\Widgets;

use App\Actions\Dashboard\DashboardWidget;
use App\Constants\CacheKeys;
use App\Enums\VentaEstado;
use App\Models\Venta;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Cache;

class VentasHoyWidget implements DashboardWidget
{
    public function key(): string
    {
        return CacheKeys::VENTAS_HOY;
    }

    public function ttl(): CarbonInterface
    {
        return now()->addMinute();
    }

    public function compute(): array
    {
        return Cache::remember($this->key(), $this->ttl(), function () {
            $stats = Venta::where('estado', VentaEstado::Pagada)
                ->whereDate('created_at', today())
                ->selectRaw('COUNT(*) as count, COALESCE(SUM(total), 0) as total')
                ->first();

            $count = (int) ($stats?->count ?? 0);
            $total = (float) ($stats?->total ?? 0);
            $ticketPromedio = $count > 0 ? $total / $count : 0;

            return [
                'count' => $count,
                'total' => $total,
                'ticket_promedio' => round($ticketPromedio, 2),
            ];
        });
    }

    public function computeForDate(Carbon $date): array
    {
        $stats = Venta::where('estado', VentaEstado::Pagada)
            ->whereDate('created_at', $date->format('Y-m-d'))
            ->selectRaw('COUNT(*) as count, COALESCE(SUM(total), 0) as total')
            ->first();

        $count = (int) ($stats?->count ?? 0);
        $total = (float) ($stats?->total ?? 0);
        $ticketPromedio = $count > 0 ? $total / $count : 0;

        return [
            'count' => $count,
            'total' => $total,
            'ticket_promedio' => round($ticketPromedio, 2),
        ];
    }
}
