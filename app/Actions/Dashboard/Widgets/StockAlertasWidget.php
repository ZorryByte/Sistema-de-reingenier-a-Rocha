<?php

namespace App\Actions\Dashboard\Widgets;

use App\Actions\Dashboard\DashboardWidget;
use App\Constants\CacheKeys;
use App\Models\Ingrediente;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Cache;

class StockAlertasWidget implements DashboardWidget
{
    public function key(): string
    {
        return CacheKeys::STOCK_ISSUES;
    }

    public function ttl(): CarbonInterface
    {
        return now()->addMinutes(2);
    }

    public function compute(): array
    {
        $stockIssues = Cache::remember($this->key(), $this->ttl(), function () {
            return Ingrediente::where(function ($q) {
                $q->whereColumn('stock_actual', '<=', 'stock_minimo')
                    ->where('stock_minimo', '>', 0);
            })
                ->orWhere('stock_actual', '<', 0)
                ->get(['id', 'nombre', 'stock_actual', 'stock_minimo', 'unidad_medida'])
                ->toArray();
        });

        $negativo = collect($stockIssues)
            ->filter(fn ($i) => $i['stock_actual'] < 0)
            ->count();

        $critico = collect($stockIssues)
            ->filter(fn ($i) => $i['stock_actual'] >= 0)
            ->values()
            ->toArray();

        return [
            'negativo' => $negativo,
            'critico' => $critico,
        ];
    }
}
