<?php

namespace App\Actions;

use App\Actions\Dashboard\Widgets\BackupStatusWidget;
use App\Actions\Dashboard\Widgets\OrdenesActivasWidget;
use App\Actions\Dashboard\Widgets\PedidosActivosWidget;
use App\Actions\Dashboard\Widgets\PedidosEntregadosWidget;
use App\Actions\Dashboard\Widgets\StockAlertasWidget;
use App\Actions\Dashboard\Widgets\TopProductosWidget;
use App\Actions\Dashboard\Widgets\VentasHoyWidget;
use App\Actions\Dashboard\Widgets\VentasRangoWidget;

class GetDashboardStats
{
    public function execute(): array
    {
        $ventasHoy = (new VentasHoyWidget)->compute();
        $pedidosActivos = (new PedidosActivosWidget)->compute();
        $ordenesActivas = (new OrdenesActivasWidget)->compute();
        $pedidosEntregados = (new PedidosEntregadosWidget)->compute();
        $stockAlertas = (new StockAlertasWidget)->compute();
        $topProductos = (new TopProductosWidget)->compute();
        $estadoBackup = (new BackupStatusWidget)->compute();
        $cocinaItems = collect($pedidosActivos['items'])
            ->merge($ordenesActivas['items'])
            ->sortBy('fecha')
            ->take(7)
            ->values()
            ->toArray();

        $ventasAyer = (new VentasHoyWidget)->computeForDate(now()->subDay());
        $ventasRangoHoy = (new VentasRangoWidget)->compute('hoy');

        return [
            'ventasHoy' => $ventasHoy['count'],
            'ventasAyer' => $ventasAyer,
            'ingresosHoy' => $ventasHoy['total'],
            'pedidosPendientes' => $pedidosActivos['count'],
            'cocinaItems' => $cocinaItems,
            'cocinaPedidosCount' => $pedidosActivos['count'],
            'cocinaOrdenesCount' => $ordenesActivas['count'],
            'pedidosEntregadosCount' => $pedidosEntregados['count'],
            'stockNegativo' => $stockAlertas['negativo'],
            'stockCritico' => $stockAlertas['critico'],
            'topProductos' => $topProductos['items'],
            'estadoBackup' => $estadoBackup,
            'ventasRangoHoy' => $ventasRangoHoy,
        ];
    }
}
