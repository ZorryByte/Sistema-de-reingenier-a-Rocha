<?php

namespace App\Actions\Dashboard\Widgets;

use App\Actions\Dashboard\DashboardWidget;
use App\Constants\CacheKeys;
use App\Enums\VentaEstado;
use App\Models\Venta;
use Carbon\CarbonInterface;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class VentasSemanaWidget implements DashboardWidget
{
    public function key(): string
    {
        return CacheKeys::VENTAS_SEMANA;
    }

    public function ttl(): CarbonInterface
    {
        return now()->addMinutes(2);
    }

    public function compute(): array
    {
        $semanaStart = now()->subDays(6)->startOfDay();
        $semanaEnd = now()->endOfDay();

        $ventas = Cache::remember($this->key(), $this->ttl(), function () use ($semanaStart) {
            return Venta::where('estado', VentaEstado::Pagada)
                ->where('created_at', '>=', $semanaStart)
                ->select(
                    DB::raw('DATE(created_at) as fecha'),
                    DB::raw('SUM(total) as total'),
                    DB::raw('COUNT(*) as cantidad')
                )
                ->groupBy('fecha')
                ->orderBy('fecha')
                ->get()
                ->toArray();
        });

        $ventasPorDia = collect($ventas)->keyBy('fecha');
        $items = collect(CarbonPeriod::create($semanaStart, $semanaEnd))
            ->map(function ($dia) use ($ventasPorDia) {
                $fecha = $dia->format('Y-m-d');
                $venta = $ventasPorDia->get($fecha, []);

                return [
                    'fecha' => $fecha,
                    'total' => round((float) ($venta['total'] ?? 0), 2),
                    'cantidad' => (int) ($venta['cantidad'] ?? 0),
                ];
            })
            ->values()
            ->toArray();
        $total = collect($items)->sum('total');

        return [
            'items' => $items,
            'total' => round((float) $total, 2),
        ];
    }
}
