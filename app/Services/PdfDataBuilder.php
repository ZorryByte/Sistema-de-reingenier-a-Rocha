<?php

namespace App\Services;

use App\Enums\VentaEstado;
use App\Models\ConfiguracionEmpresa;
use App\Models\Pedido;
use App\Models\Venta;

class PdfDataBuilder
{
    // Shared PDF data builder
    public static function buildVentaData(Venta $venta): array
    {
        $nombreEmpresa = ConfiguracionEmpresa::get('nombre', 'Rocha Bakery & Coffee');
        $direccionEmpresa = ConfiguracionEmpresa::get('direccion', 'San Salvador, El Salvador');
        $telefonoEmpresa = ConfiguracionEmpresa::get('telefono', '');

        $fechaVenta = $venta->created_at
            ? $venta->created_at->format('d/m/Y H:i')
            : now()->format('d/m/Y H:i');

        $nombreCajero = $venta->usuario->name ?? 'N/A';
        $estadoVenta = ucfirst($venta->estado instanceof VentaEstado ? $venta->estado->value : ($venta->estado ?? 'pagada'));

        $items = $venta->detalles->map(fn ($det) => [
            'nombre' => $det->presentacion->producto->nombre ?? 'Producto eliminado',
            'presentacion' => $det->presentacion->nombre ?? '—',
            'precio_unitario' => $det->precio_unitario ?? 0,
            'cantidad' => $det->cantidad ?? 0,
            'subtotal' => $det->subtotal ?? 0,
            'descuento' => $det->descuento ?? 0,
        ]);

        $pagos = $venta->pagos->map(fn ($p) => [
            'metodo' => $p->metodo ?? 'N/A',
            'monto' => $p->monto ?? 0,
        ]);

        $totalPagado = $pagos->sum('monto');
        $totalVenta = $venta->total ?? 0;

        $pedidoId = ($venta->pedido && $venta->pedido->id) ? $venta->pedido->id : null;

        return [
            'venta' => $venta,
            'nombreEmpresa' => $nombreEmpresa,
            'direccionEmpresa' => $direccionEmpresa,
            'telefonoEmpresa' => $telefonoEmpresa,
            'fechaVenta' => $fechaVenta,
            'nombreCajero' => $nombreCajero,
            'estadoVenta' => $estadoVenta,
            'items' => $items,
            'pagos' => $pagos,
            'totalPagado' => $totalPagado,
            'totalVenta' => $totalVenta,
            'pedidoId' => $pedidoId,
        ];
    }

    // Shared PDF data builder for pedidos
    public static function buildPedidoData(Pedido $pedido): array
    {
        $nombreEmpresa = ConfiguracionEmpresa::get('nombre', 'Rocha Bakery & Coffee');
        $direccionEmpresa = ConfiguracionEmpresa::get('direccion', 'San Salvador, El Salvador');
        $telefonoEmpresa = ConfiguracionEmpresa::get('telefono', '');

        $fechaPedido = $pedido->fecha
            ? $pedido->fecha->format('d/m/Y H:i')
            : now()->format('d/m/Y H:i');

        $nombreAtendido = $pedido->usuario->name ?? 'N/A';

        $fechaEntrega = $pedido->fecha_entrega_estimada
            ? $pedido->fecha_entrega_estimada->format('d/m/Y')
            : null;

        $items = $pedido->detalles->map(fn ($det) => [
            'nombre' => $det->presentacion->producto->nombre ?? 'Producto eliminado',
            'presentacion' => $det->presentacion->nombre ?? '—',
            'precio_unitario' => $det->presentacion->precio ?? 0,
            'cantidad' => $det->cantidad ?? 0,
            'subtotal_cents' => $det->subtotalCents(),
        ]);

        $totalPedido = $items->sum('subtotal_cents') / 100;

        $ventaId = ($pedido->venta && $pedido->venta->id) ? $pedido->venta->id : null;

        $notas = $pedido->notas ?? null;

        return [
            'pedido' => $pedido,
            'nombreEmpresa' => $nombreEmpresa,
            'direccionEmpresa' => $direccionEmpresa,
            'telefonoEmpresa' => $telefonoEmpresa,
            'fechaPedido' => $fechaPedido,
            'nombreAtendido' => $nombreAtendido,
            'fechaEntrega' => $fechaEntrega,
            'items' => $items,
            'totalPedido' => $totalPedido,
            'ventaId' => $ventaId,
            'notas' => $notas,
        ];
    }
}
