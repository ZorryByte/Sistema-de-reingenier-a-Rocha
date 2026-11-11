<?php

namespace App\Http\Controllers;

use App\Http\Requests\IngredienteRequest;
use App\Models\Ingrediente;
use App\Models\KardexInventario;
use App\Models\Proveedor;
use App\Models\Receta;
use App\Models\RecetaDetalle;
use App\Support\UnitFormatter;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class IngredienteController extends Controller
{
    private function userId(): int
    {
        /** @var User $user */
        $user = Auth::user();

        return $user->id;
    }

    public function index()
    {
        $query = Ingrediente::query()
            ->with('proveedor')
            ->withCount(['recetaDetalles as recetas_activas_count' => fn ($q) => $q->whereHas('receta', fn ($sq) => $sq->where('activo', true))])
            ->orderBy('nombre')
            ->buscar(request('buscar'))
            ->categoria(request('categoria'));

        if (request('filtro') === 'bajo') {
            $query->stockBajo();
        }

        $ingredientes = $query->paginate(15);

        $bajosStockCount = Ingrediente::query()
            ->stockBajo()
            ->categoria(request('categoria'))
            ->buscar(request('buscar'))
            ->count();

        $categorias = collect(Ingrediente::CATEGORIAS)
            ->map(fn ($label, $value) => ['value' => $value, 'label' => $label]);

        return view('ingredientes.index', compact('ingredientes', 'bajosStockCount', 'categorias'));
    }

    public function create()
    {
        $proveedorId = request()->integer('proveedor_id') ?: (int) old('proveedor_id');
        $proveedorSeleccionado = $proveedorId
            ? Proveedor::find($proveedorId)
            : null;

        return view('ingredientes.create', compact('proveedorSeleccionado'));
    }

    public function store(IngredienteRequest $request)
    {
        $userId = $this->userId();

        DB::transaction(function () use ($request, $userId) {
            $ingrediente = Ingrediente::create($request->validated());

            if ($ingrediente->stock_actual > 0) {
                KardexInventario::create([
                    'tipo' => 'entrada',
                    'ingrediente_id' => $ingrediente->id,
                    'cantidad' => $ingrediente->stock_actual,
                    'stock_anterior' => 0,
                    'stock_resultante' => $ingrediente->stock_actual,
                    'user_id' => $userId,
                    'notas' => 'Stock inicial al crear ingrediente.',
                ]);
            }
        });

        return redirect()->route('ingredientes.index')
            ->with('success', "Ingrediente \"{$request->nombre}\" creado.");
    }

    public function edit(Ingrediente $ingrediente)
    {
        $movimientos = $ingrediente->movimientos()
            ->latest('fecha')
            ->take(10)
            ->get();

        $bajosStockCount = Ingrediente::query()->stockBajo()->count();
        $proveedorId = request()->integer('proveedor_id') ?: (int) old('proveedor_id');
        $proveedorSeleccionado = $proveedorId
            ? Proveedor::find($proveedorId)
            : $ingrediente->proveedor;

        return view('ingredientes.edit', compact('ingrediente', 'movimientos', 'bajosStockCount', 'proveedorSeleccionado'));
    }

    public function update(IngredienteRequest $request, Ingrediente $ingrediente)
    {
        $stockAnterior = $ingrediente->stock_actual;
        $data = $request->validated();
        $userId = $this->userId();

        DB::transaction(function () use ($ingrediente, $data, $stockAnterior, $userId) {
            $ingrediente->update($data);

            $diff = $data['stock_actual'] - $stockAnterior;

            if ($diff !== 0) {
                KardexInventario::create([
                    'tipo' => 'ajuste',
                    'ingrediente_id' => $ingrediente->id,
                    'cantidad' => abs($diff),
                    'stock_anterior' => $stockAnterior,
                    'stock_resultante' => $data['stock_actual'],
                    'user_id' => $userId,
                    'notas' => $diff > 0
                        ? "Ajuste manual: +{$diff} ".UnitFormatter::short($ingrediente->unidad_medida)
                        : "Ajuste manual: {$diff} ".UnitFormatter::short($ingrediente->unidad_medida),
                ]);
            }
        });

        return redirect()->route('ingredientes.index')
            ->with('success', "Ingrediente \"{$ingrediente->nombre}\" actualizado.");
    }

    public function destroy(Ingrediente $ingrediente)
    {
        return DB::transaction(function () use ($ingrediente) {
            $recetasAfectadas = 0;

            if ($ingrediente->recetaDetalles()->exists()) {
                $recetasIds = RecetaDetalle::where('ingrediente_id', $ingrediente->id)
                    ->whereHas('receta', fn ($q) => $q->where('activo', true))
                    ->pluck('receta_id')
                    ->unique();

                if ($recetasIds->isNotEmpty()) {
                    Receta::whereIn('id', $recetasIds)->update(['activo' => false]);
                    $recetasAfectadas = $recetasIds->count();
                }
            }

            $nombre = $ingrediente->nombre;

            $ingrediente->delete();

            $mensaje = "Ingrediente \"{$nombre}\" archivado.";
            if ($recetasAfectadas > 0) {
                $mensaje .= " Se desactivaron {$recetasAfectadas} receta(s).";
            }

            return redirect()->route('ingredientes.index')->with('success', $mensaje);
        });
    }
}
