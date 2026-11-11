<?php

namespace App\Http\Controllers;

use App\Actions\CancelarVenta;
use App\Models\Ingrediente;
use App\Models\KardexInventario;
use App\Models\Venta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ControlInventarioController extends Controller
{
    public function index(Request $request)
    {
        $query = KardexInventario::with(['ingrediente', 'usuario', 'usuarioReverso', 'venta', 'pedido']);

        if (! $request->boolean('incluir_revertidos')) {
            $query->activos();
        }

        $query->porTipo($request->input('tipo'))
            ->porIngrediente($request->integer('ingrediente_id'))
            ->porFecha($request->input('desde'), $request->input('hasta'))
            ->latest('fecha');

        $movimientos = $query->paginate(20)->withQueryString();
        $ingredientes = Ingrediente::orderBy('nombre')->get();

        return view('control-inventario.index', compact('movimientos', 'ingredientes'));
    }

    public function show(KardexInventario $movimiento)
    {
        $movimiento->load(['ingrediente', 'usuario', 'usuarioReverso', 'venta', 'pedido']);

        $historial = KardexInventario::with(['usuario'])
            ->where('ingrediente_id', $movimiento->ingrediente_id)
            ->where('id', '!=', $movimiento->id)
            ->latest('fecha')
            ->take(10)
            ->get();

        return view('control-inventario.show', compact('movimiento', 'historial'));
    }

    public function revertir(Request $request, KardexInventario $movimiento)
    {
        $request->validate([
            'restaurar_stock' => 'boolean',
        ]);

        if (! $movimiento->esReversible()) {
            return back()->with('error', 'Este movimiento ya fue revertido o no es reversible.');
        }

        if (! Auth::user()->esAdmin()) {
            abort(403, 'No tienes permiso para realizar esta accion');
        }

        $user = Auth::user();
        $restaurarStock = $request->boolean('restaurar_stock', true);

        if ($movimiento->venta_id && $movimiento->tipo === 'venta') {
            try {
                $venta = Venta::with('movimientos')->findOrFail($movimiento->venta_id);
                (new CancelarVenta)->execute($venta, $user, $restaurarStock);
            } catch (\RuntimeException $e) {
                return back()->with('error', $e->getMessage());
            }
        } else {
            try {
                DB::transaction(function () use ($movimiento, $user, $restaurarStock) {
                    $movimiento->update([
                        'reverted_at' => now(),
                        'user_reverso_id' => $user->id,
                    ]);

                    if ($restaurarStock) {
                        CancelarVenta::restaurarStockUnicoMovimiento(
                            $movimiento,
                            $user,
                            null,
                            null,
                            "Reversion manual del movimiento #{$movimiento->id}"
                        );
                    }
                });
            } catch (\Exception $e) {
                return back()->with('error', 'Error al revertir movimiento: '.$e->getMessage());
            }
        }

        return redirect()->route('control-inventario.index')
            ->with('success', 'Movimiento revertido correctamente.');
    }

    public function historial(Ingrediente $ingrediente, Request $request)
    {
        $query = $ingrediente->movimientos()
            ->with(['usuario', 'usuarioReverso', 'venta', 'pedido']);

        if (! $request->boolean('incluir_revertidos')) {
            $query->activos();
        }

        $query->porTipo($request->input('tipo'))
            ->porFecha($request->input('desde'), $request->input('hasta'))
            ->latest('fecha');

        $movimientos = $query->paginate(20)->withQueryString();

        return view('control-inventario.historial', compact('ingrediente', 'movimientos'));
    }
}
