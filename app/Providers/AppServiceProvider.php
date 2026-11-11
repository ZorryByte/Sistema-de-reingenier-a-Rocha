<?php

namespace App\Providers;

use App\Constants\CacheKeys;
use App\Enums\PedidoEstado;
use App\Models\Ingrediente;
use App\Models\KardexInventario;
use App\Models\OrdenProduccion;
use App\Models\Pedido;
use App\Models\PresentacionProducto;
use App\Models\Producto;
use App\Models\Venta;
use App\Observers\DashboardCacheObserver;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        view()->composer('layouts.navigation', function ($view) {
            try {
                $pendingPedidos = Cache::remember(CacheKeys::NAVBAR_PEDIDOS, 60, function () {
                    return Pedido::whereIn('estado', [PedidoEstado::EnPreparacion, PedidoEstado::OrdenLista])
                        ->count();
                });
                $stockBajoCount = Cache::remember(CacheKeys::NAVBAR_STOCK, 60, function () {
                    return Ingrediente::where('stock_minimo', '>', 0)
                        ->whereColumn('stock_actual', '<=', 'stock_minimo')
                        ->count();
                });
                $view->with('pendingPedidos', $pendingPedidos);
                $view->with('stockBajoCount', $stockBajoCount);
                $view->with('esAdmin', Auth::check() && Auth::user()->esAdmin());
            } catch (\Exception $e) {
                Log::error('Navbar composer failed', ['exception' => $e->getMessage()]);
                $view->with('pendingPedidos', 0);
                $view->with('stockBajoCount', 0);
                $view->with('esAdmin', false);
            }
        });

        // Observer auto-invalidates dashboard cache on model changes
        Venta::observe(DashboardCacheObserver::class);
        Pedido::observe(DashboardCacheObserver::class);
        Producto::observe(DashboardCacheObserver::class);
        Ingrediente::observe(DashboardCacheObserver::class);
        OrdenProduccion::observe(DashboardCacheObserver::class);
        KardexInventario::observe(DashboardCacheObserver::class);
        PresentacionProducto::observe(DashboardCacheObserver::class);
    }
}
