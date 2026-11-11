<?php

namespace App\Http\Controllers;

use App\Actions\RegistrarEntradaInventario;
use App\Actions\RegistrarMerma;
use App\Http\Requests\IngredienteControlInventarioRequest;
use App\Models\Ingrediente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class IngredienteControlInventarioController extends Controller
{
    public function create(Request $request)
    {
        $ingredientes = Ingrediente::orderBy('nombre')->get();
        $ingredienteSeleccionado = null;

        if ($request->filled('ingrediente_id')) {
            $ingredienteSeleccionado = Ingrediente::find($request->integer('ingrediente_id'));
        }

        return view('ingredientes.control-inventario.create', compact('ingredientes', 'ingredienteSeleccionado'));
    }

    public function store(
        IngredienteControlInventarioRequest $request,
        RegistrarEntradaInventario $registrarEntrada,
        RegistrarMerma $registrarMerma
    ) {
        $data = $request->validated();
        $userId = Auth::id();

        if ($data['tipo'] === 'entrada') {
            $registrarEntrada->execute([
                ...$data,
                'entidad_id' => $data['ingrediente_id'],
            ], $userId);

            return redirect()->route('ingredientes.index')
                ->with('success', 'Entrada de ingrediente registrada correctamente.');
        }

        try {
            $registrarMerma->execute([
                'ingrediente_id' => $data['ingrediente_id'],
                'cantidad' => $data['cantidad'],
                'categoria' => $data['categoria'] ?? null,
                'notas' => $data['notas'] ?? null,
                'fecha' => $data['fecha'] ?? null,
            ], $userId);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }

        return redirect()->route('ingredientes.index')
            ->with('success', 'Merma de ingrediente registrada correctamente.');
    }
}
