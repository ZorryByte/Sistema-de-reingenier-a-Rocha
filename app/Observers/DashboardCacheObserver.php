<?php

namespace App\Observers;

use App\Http\Controllers\DashboardController;
use App\Models\Ingrediente;
use App\Models\KardexInventario;
use App\Models\OrdenProduccion;
use App\Models\Pedido;
use App\Models\PresentacionProducto;
use App\Models\Producto;
use App\Models\Venta;

class DashboardCacheObserver
{
    public function created(object $model): void
    {
        $this->invalidateIfNeeded($model);
    }

    public function updated(object $model): void
    {
        $this->invalidateIfNeeded($model);
    }

    public function deleted(object $model): void
    {
        $this->invalidateIfNeeded($model);
    }

    private function invalidateIfNeeded(object $model): void
    {
        $modelsRequiringCacheClear = [
            Venta::class,
            Pedido::class,
            Producto::class,
            Ingrediente::class,
            OrdenProduccion::class,
            KardexInventario::class,
            PresentacionProducto::class,
        ];

        if (in_array($model::class, $modelsRequiringCacheClear)) {
            DashboardController::clearCache();
        }
    }
}
