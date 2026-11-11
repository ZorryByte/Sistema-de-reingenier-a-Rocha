<?php

namespace App\Services;

use App\Constants\CacheKeys;
use Illuminate\Support\Facades\Cache;

class DashboardCacheInvalidator
{
    public function invalidateAll(): void
    {
        foreach (CacheKeys::all() as $key) {
            Cache::forget($key);
        }
    }

    public function invalidateDashboard(): void
    {
        foreach (CacheKeys::dashboard() as $key) {
            Cache::forget($key);
        }
    }

    public function invalidateNavbar(): void
    {
        foreach (CacheKeys::navbar() as $key) {
            Cache::forget($key);
        }
    }

    public function invalidateVentas(): void
    {
        Cache::forget(CacheKeys::VENTAS_HOY);
        Cache::forget(CacheKeys::VENTAS_SEMANA);
        Cache::forget(CacheKeys::ACTIVIDAD_RECIENTE);
        Cache::forget(CacheKeys::PEDIDOS_ENTREGADOS);
        Cache::forget(CacheKeys::TOP_PRODUCTOS);
        Cache::forget(CacheKeys::PREP_PROMEDIO);
    }

    public function invalidatePedidos(): void
    {
        Cache::forget(CacheKeys::PEDIDOS_PENDIENTES);
        Cache::forget(CacheKeys::ORDENES_ACTIVAS);
        Cache::forget(CacheKeys::ACTIVIDAD_RECIENTE);
        Cache::forget(CacheKeys::NAVBAR_PEDIDOS);
    }

    public function invalidateStock(): void
    {
        Cache::forget(CacheKeys::STOCK_ISSUES);
        Cache::forget(CacheKeys::NAVBAR_STOCK);
    }
}
