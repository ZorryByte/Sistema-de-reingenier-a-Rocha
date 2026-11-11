<?php

namespace App\Actions\Dashboard\Widgets;

use App\Actions\Dashboard\DashboardWidget;
use App\Constants\CacheKeys;
use App\Enums\VentaEstado;
use App\Models\Venta;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class VentasRangoWidget implements DashboardWidget
{
    public function key(): string
    {
        return CacheKeys::VENTAS_HOY_RANGO;
    }

    public function ttl(): CarbonInterface
    {
        return now()->addMinutes(2);
    }

    public function compute(string $range = 'semana'): array
    {
        $range = in_array($range, ['hoy', 'semana', 'mes'], true) ? $range : 'semana';

        $cacheKey = match ($range) {
            'hoy' => CacheKeys::VENTAS_HOY_RANGO,
            'mes' => CacheKeys::VENTAS_MES_RANGO,
            default => CacheKeys::VENTAS_SEMANA,
        };

        $days = match ($range) {
            'hoy' => 2,
            'mes' => 30,
            default => 7,
        };

        $start = now()->subDays($days - 1)->startOfDay();
        $end = now()->endOfDay();

        $ventas = Cache::remember($cacheKey, $this->ttl(), function () use ($start, $end) {
            return Venta::where('estado', VentaEstado::Pagada->value)
                ->whereBetween('created_at', [$start, $end])
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
        $items = collect(CarbonPeriod::create($start, $end))
            ->map(function ($dia) use ($ventasPorDia) {
                $fecha = $dia->format('Y-m-d');
                $venta = $ventasPorDia->get($fecha, []);

                return [
                    'fecha' => $fecha,
                    'total' => round((float) ($venta['total'] ?? 0), 2),
                    'cantidad' => (int) ($venta['cantidad'] ?? 0),
                    'label' => Carbon::parse($fecha)->locale('es')->translatedFormat('D d'),
                ];
            })
            ->values()
            ->toArray();

        if ($range === 'hoy') {
            $insights = $this->todayInsight($items);
            $items = array_slice($items, -1);
        }

        $total = collect($items)->sum('total');
        $count = collect($items)->sum('cantidad');

        return [
            'items' => $items,
            'total' => round((float) $total, 2),
            'count' => (int) $count,
            'insights' => $insights ?? $this->insights($range, $items, (float) $total),
        ];
    }

    private function insights(string $range, array $items, float $total): array
    {
        if ($items === []) {
            return ['caption' => 'Sin ventas en la ventana'];
        }

        return match ($range) {
            'mes' => $this->monthInsight($total),
            default => $this->weekInsight($items, $total),
        };
    }

    private function todayInsight(array $items): array
    {
        $today = (float) ($items[array_key_last($items)]['total'] ?? 0);
        $yesterday = (float) ($items[array_key_last($items) - 1]['total'] ?? 0);

        if ($yesterday <= 0.0) {
            return [
                'caption' => 'Sin venta ayer para comparar',
                'delta_pct' => null,
            ];
        }

        $delta = (($today - $yesterday) / abs($yesterday)) * 100;

        return [
            'caption' => 'Hoy '.$this->formatPercent($delta).' vs ayer',
            'delta_pct' => round($delta, 1),
        ];
    }

    private function weekInsight(array $items, float $total): array
    {
        $best = collect($items)->sortByDesc('total')->first();

        if (! $best || (float) $best['total'] <= 0.0 || $total <= 0.0) {
            return ['caption' => 'Sin ventas en la ventana'];
        }

        $bestTotal = (float) $best['total'];
        $pctTotal = ($bestTotal / $total) * 100;

        return [
            'caption' => 'Mejor dia: '.$best['label'].' con $'.number_format($bestTotal, 2).' ('.round($pctTotal).'% del total)',
            'mejor_dia_label' => $best['label'],
            'mejor_dia_total' => round($bestTotal, 2),
            'pct_total' => round($pctTotal, 1),
        ];
    }

    private function monthInsight(float $total): array
    {
        $previousStart = now()->subDays(59)->startOfDay();
        $previousEnd = now()->subDays(30)->endOfDay();

        $previousTotal = (float) Venta::where('estado', VentaEstado::Pagada->value)
            ->whereBetween('created_at', [$previousStart, $previousEnd])
            ->sum('total');

        if ($previousTotal <= 0.0) {
            return [
                'caption' => 'Sin ventas previas para comparar',
                'delta_pct_prev' => null,
            ];
        }

        $delta = (($total - $previousTotal) / abs($previousTotal)) * 100;

        return [
            'caption' => 'Tendencia '.$this->formatPercent($delta).' vs los 30 dias previos',
            'delta_pct_prev' => round($delta, 1),
        ];
    }

    private function formatPercent(float $value): string
    {
        $rounded = round($value, 1);
        $prefix = $rounded > 0 ? '+' : '';

        return $prefix.$rounded.'%';
    }
}
