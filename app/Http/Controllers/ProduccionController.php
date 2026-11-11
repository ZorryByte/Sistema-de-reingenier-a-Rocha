<?php

namespace App\Http\Controllers;

use App\Actions\RegistrarProduccion;
use App\Models\OrdenProduccion;
use App\Models\PresentacionProducto;
use App\Models\Producto;
use App\Models\User;
use App\Support\UnitFormatter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProduccionController extends Controller
{
    public function index(Request $request): View
    {
        $desde = $request->input('desde') ?: $request->input('fecha');
        $hasta = $request->input('hasta') ?: $request->input('fecha');

        $query = OrdenProduccion::with([
            'presentacion.producto',
            'presentacion.recetaActiva',
            'presentacion.producto.recetaDefault',
            'user',
        ])
            ->buscar($request->input('buscar'))
            ->porFecha($desde, $hasta)
            ->porProducto($request->integer('producto_id') ?: null)
            ->porPresentacion($request->integer('presentacion_id') ?: null)
            ->porUsuario($request->integer('user_id') ?: null)
            ->latest();

        $producciones = $query->paginate(20)->withQueryString();

        $productos = Producto::orderBy('nombre')->get(['id', 'nombre']);
        $presentaciones = PresentacionProducto::with('producto')
            ->orderBy('nombre')
            ->get(['id', 'producto_id', 'nombre']);
        $usuarios = User::orderBy('name')->get(['id', 'name']);

        return view('produccion.index', compact('producciones', 'productos', 'presentaciones', 'usuarios', 'desde', 'hasta'));
    }

    public function create(): View
    {
        // Catalog query: active presentations with producto
        $presentaciones = PresentacionProducto::activas()
            ->conProducto()
            ->with(['recetaActiva.detalles.ingrediente', 'producto.recetaDefault'])
            ->get();

        return view('produccion.create', compact('presentaciones'));
    }

    public function preview(Request $request): JsonResponse
    {
        $request->validate([
            'presentacion_id' => 'required|exists:presentaciones_producto,id',
            'cantidad' => 'required|integer|min:1',
        ]);

        $presentacion = PresentacionProducto::with('producto', 'recetaActiva.detalles.ingrediente')
            ->findOrFail($request->presentacion_id);

        $receta = $presentacion->recetaActiva ?? $presentacion->producto->recetaDefault;
        $sinReceta = ! $receta;

        $consumos = [];
        if (! $sinReceta) {
            $consumos = RegistrarProduccion::calcularConsumoIngredientes($presentacion, $request->cantidad);
        }

        return response()->json([
            'sin_receta' => $sinReceta,
            'receta_nombre' => $receta?->nombre,
            'consumos' => $consumos,
            'stock_presentacion' => (float) $presentacion->stock_actual,
            'unidades_resultantes' => $request->cantidad * ($receta?->lote_unidades ?? $presentacion->cantidad_unidades),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'presentacion_id' => 'required|exists:presentaciones_producto,id',
            'cantidad' => 'required|integer|min:1',
            'notas' => 'nullable|string',
        ]);

        try {
            $action = app(RegistrarProduccion::class);
            $result = $action->execute($validated, auth()->id());

            return redirect()->route('produccion.index')
                ->with('success', "Producción {$result['numero_lote']} registrada: {$result['cantidad']} ".UnitFormatter::units()." de {$result['presentacion']}");

        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }
}
