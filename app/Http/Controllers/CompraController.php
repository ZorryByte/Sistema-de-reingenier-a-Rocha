<?php

namespace App\Http\Controllers;

use App\Models\Compra;

class CompraController extends Controller
{
    public function index()
    {
        $query = Compra::query()
            ->with(['proveedor', 'user'])
            ->orderByDesc('fecha');

        $compras = $query->paginate(15);
        $inicioMes = now()->startOfMonth();
        $finMes = now()->endOfMonth();

        $lectura = [
            'mes_total' => (float) Compra::whereBetween('fecha', [$inicioMes, $finMes])->sum('total'),
            'mes_registros' => Compra::whereBetween('fecha', [$inicioMes, $finMes])->count(),
            'sin_proveedor' => Compra::whereBetween('fecha', [$inicioMes, $finMes])->whereNull('proveedor_id')->count(),
        ];

        return view('finanzas.compras.index', compact('compras', 'lectura'));
    }

    public function show(Compra $compra)
    {
        $compra->load(['proveedor', 'user', 'detalles.ingrediente']);

        return view('finanzas.compras.show', compact('compra'));
    }
}
