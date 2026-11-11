<?php

namespace App\Http\Controllers;

use App\Actions\AsociarRecetaAProducto;
use App\Http\Requests\ProductoRequest;
use App\Http\Resources\ProductoIndexResource;
use App\Models\KardexInventario;
use App\Models\PresentacionProducto;
use App\Models\Producto;
use App\Models\Receta;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ProductoController extends Controller
{
    public function index(): View
    {
        $productos = Producto::with(['recetaDefault', 'presentacionesActivas.recetaActiva'])
            ->orderBy('nombre')
            ->get();

        return view('productos.index', ['productos' => ProductoIndexResource::collection($productos)]);
    }

    public function create(): View
    {
        $recetasIndependientes = Receta::activas()
            ->sinProducto()
            ->default()
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'lote_unidades']);

        return view('productos.create', compact('recetasIndependientes'));
    }

    public function store(ProductoRequest $request, AsociarRecetaAProducto $asociarReceta): RedirectResponse
    {
        try {
            return DB::transaction(function () use ($request, $asociarReceta) {
                $producto = Producto::create([
                    'nombre' => $request->nombre,
                    'tipo_unidad' => $request->tipo_unidad,
                    'activo' => $request->boolean('activo', true),
                ]);

                foreach ($request->presentaciones as $p) {
                    $presentacion = $producto->presentaciones()->create([
                        'nombre' => $p['nombre'],
                        'cantidad_unidades' => $p['cantidad_unidades'] ?? 1,
                        'precio' => $p['precio'],
                        'activo' => true,
                        'tipo_venta' => $p['tipo_venta'] ?? 'unidad',
                    ]);

                    KardexInventario::create([
                        'tipo' => 'creacion',
                        'categoria' => 'presentacion',
                        'user_id' => auth()->id(),
                        'referencia_type' => PresentacionProducto::class,
                        'referencia_id' => $presentacion->id,
                        'fecha' => now(),
                        'notas' => "Presentación \"{$p['nombre']}\" creada para producto \"{$producto->nombre}\"",
                    ]);
                }

                $asociarReceta->execute($request->input('receta_id') ? (int) $request->input('receta_id') : null, $producto);

                KardexInventario::create([
                    'tipo' => 'creacion',
                    'categoria' => 'producto',
                    'user_id' => auth()->id(),
                    'referencia_type' => Producto::class,
                    'referencia_id' => $producto->id,
                    'fecha' => now(),
                    'notas' => "Producto \"{$producto->nombre}\" creado con ".count($request->presentaciones).' presentación(es)',
                ]);

                return redirect()->route('productos.index')
                    ->with('success', "Producto \"{$producto->nombre}\" creado correctamente.");
            });
        } catch (\Throwable $e) {
            \Log::error('Error al crear producto: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request' => $request->except('_token'),
            ]);

            return redirect()->route('productos.create')
                ->withInput($request->except('_token'))
                ->withErrors(['general' => 'No se pudo crear el producto. Intenta de nuevo.']);
        }
    }

    public function show(Producto $producto): View
    {
        $producto->load([
            'presentaciones',
            'presentaciones.recetaActiva.detallesConIngredientes',
        ]);

        return view('productos.show', compact('producto'));
    }

    public function edit(Producto $producto): View
    {
        $producto->load(['presentaciones', 'recetaDefault.detallesConIngredientes']);
        $recetasIndependientes = Receta::activas()
            ->sinProducto()
            ->default()
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'lote_unidades']);

        return view('productos.edit', compact('producto', 'recetasIndependientes'));
    }

    public function update(ProductoRequest $request, Producto $producto, AsociarRecetaAProducto $asociarReceta): RedirectResponse
    {
        return DB::transaction(function () use ($request, $producto, $asociarReceta) {
            $producto->update([
                'nombre' => $request->nombre,
                'tipo_unidad' => $request->tipo_unidad,
                'activo' => $request->boolean('activo', true),
            ]);

            $idsEnviados = collect($request->presentaciones)
                ->pluck('id')
                ->filter()
                ->toArray();

            $desactivadas = $producto->presentaciones()
                ->whereNotIn('id', $idsEnviados)
                ->where('activo', true)
                ->get();

            $producto->presentaciones()
                ->whereNotIn('id', $idsEnviados)
                ->update(['activo' => false]);

            foreach ($desactivadas as $p) {
                KardexInventario::create([
                    'tipo' => 'actualizacion',
                    'categoria' => 'presentacion',
                    'user_id' => auth()->id(),
                    'referencia_type' => PresentacionProducto::class,
                    'referencia_id' => $p->id,
                    'fecha' => now(),
                    'notas' => "Presentación \"{$p->nombre}\" desactivada en producto \"{$producto->nombre}\"",
                ]);
            }

            foreach ($request->presentaciones as $p) {
                $data = [
                    'nombre' => $p['nombre'],
                    'cantidad_unidades' => $p['cantidad_unidades'] ?? 1,
                    'precio' => $p['precio'],
                    'activo' => isset($p['activo'])
                        ? filter_var($p['activo'], FILTER_VALIDATE_BOOLEAN)
                        : true,
                    'tipo_venta' => $p['tipo_venta'] ?? 'unidad',
                ];

                if (! empty($p['id'])) {
                    $existing = $producto->presentaciones()->find($p['id']);
                    $wasInactive = $existing && ! $existing->activo;
                    $producto->presentaciones()->where('id', $p['id'])->update($data);

                    if ($wasInactive && $data['activo']) {
                        KardexInventario::create([
                            'tipo' => 'actualizacion',
                            'categoria' => 'presentacion',
                            'user_id' => auth()->id(),
                            'referencia_type' => PresentacionProducto::class,
                            'referencia_id' => $p['id'],
                            'fecha' => now(),
                            'notas' => "Presentación \"{$p['nombre']}\" reactivada en producto \"{$producto->nombre}\"",
                        ]);
                    }
                } else {
                    $presentacion = $producto->presentaciones()->create($data);

                    KardexInventario::create([
                        'tipo' => 'creacion',
                        'categoria' => 'presentacion',
                        'user_id' => auth()->id(),
                        'referencia_type' => PresentacionProducto::class,
                        'referencia_id' => $presentacion->id,
                        'fecha' => now(),
                        'notas' => "Presentación \"{$p['nombre']}\" agregada a producto \"{$producto->nombre}\"",
                    ]);
                }
            }

            $asociarReceta->execute($request->input('receta_id') ? (int) $request->input('receta_id') : null, $producto);

            return redirect()->route('productos.index')
                ->with('success', "Producto \"{$producto->nombre}\" actualizado.");
        });
    }

    public function destroy(Producto $producto): RedirectResponse
    {
        $producto->update(['activo' => false]);
        $producto->presentaciones()->update(['activo' => false]);

        return redirect()->route('productos.index')
            ->with('success', "Producto \"{$producto->nombre}\" desactivado.");
    }
}
