<?php

namespace App\Services;

use App\Enums\VentaEstado;
use App\Models\Cliente;
use App\Models\Venta;
use App\Models\VentaDetalle;
use Illuminate\Database\Eloquent\Builder;

class HistorialCliente
{
    public function consultar(Cliente $cliente): array
    {
        $ventas = Venta::query()->where(function (Builder $consulta) use ($cliente) {
            $consulta->where(fn (Builder $directas) => $directas->whereNull('pedido_id')->where('cliente_id', $cliente->id))
                ->orWhereIn('pedido_id', $cliente->pedidos()->select('id'));
        });

        $pagadas = (clone $ventas)->where('estado', VentaEstado::Pagada);
        $compras = (clone $pagadas)->toBase()
            ->selectRaw('COUNT(*) AS cantidad, COALESCE(SUM(total), 0) AS total, MAX(created_at) AS ultima')
            ->first();

        $articulos = VentaDetalle::query()
            ->whereIn('venta_id', $pagadas->select('id'))
            ->whereNotNull('presentacion_id')
            ->select('presentacion_id')
            ->selectRaw('SUM(cantidad) AS unidades')
            ->groupBy('presentacion_id')
            ->orderByDesc('unidades')
            ->orderBy('presentacion_id')
            ->limit(5)
            ->with('presentacion.producto')
            ->get();

        return [
            'compras' => $compras,
            'articulos' => $articulos,
            'ventas' => $ventas->latest('created_at')->orderByDesc('id')->paginate(10, ['*'], 'ventas')->withQueryString(),
            'pedidos' => $cliente->pedidos()->latest('fecha')->orderByDesc('id')->paginate(10, ['*'], 'pedidos')->withQueryString(),
        ];
    }
}
