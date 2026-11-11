<?php

namespace App\Http\Controllers;

use App\Actions\ActivarReceta;
use App\Actions\ActualizarReceta;
use App\Actions\CrearReceta;
use App\Actions\DesactivarReceta;
use App\Http\Requests\StoreRecetaRequest;
use App\Http\Requests\UpdateRecetaRequest;
use App\Models\CategoriaReceta;
use App\Models\Ingrediente;
use App\Models\Producto;
use App\Models\Receta;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RecetaController extends Controller
{
    public function index(Request $request): View
    {
        $query = Receta::with(['producto', 'presentacion', 'categoriaReceta', 'detalles.ingrediente'])
            ->orderByRaw('producto_id IS NULL')
            ->orderBy('nombre');

        if ($request->filled('q')) {
            $q = $request->string('q')->trim();
            $query->where(function ($sub) use ($q) {
                $sub->where('nombre', 'like', "%{$q}%")
                    ->orWhereHas('producto', fn ($sq) => $sq->where('nombre', 'like', "%{$q}%"))
                    ->orWhereHas('presentacion', fn ($sq) => $sq->where('nombre', 'like', "%{$q}%"));
            });
        }

        if ($request->filled('tipo')) {
            if ($request->tipo === 'default') {
                $query->default();
            } elseif ($request->tipo === 'presentacion') {
                $query->porPresentacion();
            } elseif ($request->tipo === 'independiente') {
                $query->sinProducto();
            }
        }

        if ($request->filled('estado')) {
            if ($request->estado === 'activa') {
                $query->activas();
            } elseif ($request->estado === 'inactiva') {
                $query->where('activo', false);
            }
        }

        if ($request->filled('categoria_receta_id')) {
            if ($request->input('categoria_receta_id') === 'sin_categoria') {
                $query->whereNull('categoria_receta_id');
            } else {
                $query->where('categoria_receta_id', $request->integer('categoria_receta_id'));
            }
        }

        $recetas = $query->paginate(15)->withQueryString();
        $categorias = CategoriaReceta::orderBy('nombre')->get();

        return view('recetas.index', compact('recetas', 'categorias'));
    }

    public function create(Request $request): View
    {
        $productoId = $request->query('producto_id');
        $producto = null;

        if ($productoId) {
            $producto = Producto::activos()->with('presentacionesActivas')->find($productoId);
        }

        $productos = Producto::activos()->with('presentacionesActivas')->orderBy('nombre')->get();

        $presentacionesPorProducto = $productos
            ->flatMap(fn ($p) => $p->presentacionesActivas->map(
                fn ($pres) => ['producto_id' => $p->id, 'id' => $pres->id, 'nombre' => $pres->nombre]
            ))
            ->groupBy('producto_id')
            ->map(fn ($group) => $group->values())
            ->toArray();

        $presentaciones = $producto
            ? $producto->presentacionesActivas
            : collect();

        $ingredientes = Ingrediente::orderBy('nombre')->get();
        $costosIngredientes = Ingrediente::pluck('costo_unitario', 'id')->toArray();
        $categorias = CategoriaReceta::orderBy('nombre')->get();

        return view('recetas.create', compact('productos', 'presentacionesPorProducto', 'presentaciones', 'producto', 'ingredientes', 'costosIngredientes', 'categorias'));
    }

    public function store(StoreRecetaRequest $request, CrearReceta $action): RedirectResponse
    {
        $productoId = $request->producto_id ? (int) $request->producto_id : null;
        $presentacionId = $request->presentacion_id === 'null' || $request->presentacion_id === '' || is_null($request->presentacion_id) ? null : (int) $request->presentacion_id;

        if ($productoId) {
            $producto = Producto::activos()->find($productoId);
            if (! $producto) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'El producto seleccionado no existe o esta inactivo.');
            }

            if ($presentacionId) {
                $presentacion = $producto->presentaciones()->find($presentacionId);
                if (! $presentacion) {
                    return redirect()->back()
                        ->withInput()
                        ->with('error', 'La presentacion seleccionada no pertenece a este producto.');
                }
            }
        }

        try {
            $action->execute(
                $productoId,
                $request->nombre,
                $request->descripcion,
                $request->ingredientes,
                $presentacionId,
                (int) $request->tiempo_preparacion,
                (int) $request->lote_unidades,
                (int) $request->categoria_receta_id
            );
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', $e->getMessage());
        } catch (QueryException $e) {
            if ($e->getCode() === 23000) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'Ya existe una receta para esta combinacion de producto/presentacion.');
            }
            throw $e;
        }

        return redirect()->route('recetas.index')
            ->with('success', "Receta \"{$request->nombre}\" creada correctamente.");
    }

    public function show(Receta $receta): View
    {
        $receta->load(['producto', 'presentacion', 'categoriaReceta', 'detallesConIngredientes']);

        return view('recetas.show', compact('receta'));
    }

    public function edit(Receta $receta): View
    {
        $receta->load(['detalles.ingrediente', 'producto', 'presentacion', 'categoriaReceta']);
        $ingredientes = Ingrediente::orderBy('nombre')->get();
        $costosIngredientes = Ingrediente::pluck('costo_unitario', 'id')->toArray();
        $categorias = CategoriaReceta::orderBy('nombre')->get();

        return view('recetas.edit', compact('receta', 'ingredientes', 'costosIngredientes', 'categorias'));
    }

    public function update(UpdateRecetaRequest $request, Receta $receta, ActualizarReceta $action): RedirectResponse
    {
        $cambiarProducto = $request->has('producto_id');
        $cambiarPresentacion = $request->has('presentacion_id');
        $productoId = $cambiarProducto && $request->filled('producto_id') ? (int) $request->producto_id : null;
        $presentacionId = $cambiarPresentacion && $request->filled('presentacion_id') ? (int) $request->presentacion_id : null;

        try {
            $action->execute(
                $receta,
                $request->nombre,
                $request->descripcion,
                $request->boolean('activo', true),
                $request->ingredientes,
                (int) $request->tiempo_preparacion,
                (int) $request->lote_unidades,
                $productoId,
                $presentacionId,
                $cambiarProducto,
                $cambiarPresentacion,
                (int) $request->categoria_receta_id
            );
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', $e->getMessage());
        } catch (QueryException $e) {
            if ($e->getCode() === 23000) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'Ya existe una receta para esta combinacion de producto/presentacion.');
            }
            throw $e;
        }

        return redirect()->route('recetas.index')
            ->with('success', "Receta \"{$receta->nombre}\" actualizada.");
    }

    public function destroy(Receta $receta, DesactivarReceta $action): RedirectResponse
    {
        $action->execute($receta);

        return redirect()->route('recetas.index')
            ->with('success', "Receta \"{$receta->nombre}\" desactivada.");
    }

    public function activar(Receta $receta, ActivarReceta $action): RedirectResponse
    {
        try {
            $action->execute($receta);
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()
                ->with('error', $e->getMessage());
        } catch (QueryException $e) {
            if ($e->getCode() === 23000) {
                return redirect()->back()
                    ->with('error', 'Ya existe una receta activa para esta combinacion de producto/presentacion.');
            }
            throw $e;
        }

        return redirect()->route('recetas.index')
            ->with('success', "Receta \"{$receta->nombre}\" activada.");
    }
}
