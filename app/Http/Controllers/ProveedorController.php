<?php

namespace App\Http\Controllers;

use App\Actions\ActualizarProveedor;
use App\Actions\CrearProveedor;
use App\Http\Requests\ProveedorRequest;
use App\Models\CompraDetalle;
use App\Models\Ingrediente;
use App\Models\Proveedor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProveedorController extends Controller
{
    public function index(): View
    {
        $query = Proveedor::query()
            ->withCount(['compras', 'ingredientes'])
            ->orderBy('nombre')
            ->buscar(request('buscar'));

        $ingredientesTotal = Ingrediente::count();
        $sinProveedor = Ingrediente::whereNull('proveedor_id')->count();
        $lectura = [
            'total' => Proveedor::count(),
            'con_compras' => Proveedor::has('compras')->count(),
            'sin_contacto' => Proveedor::whereNull('telefono')->whereNull('email')->count(),
            'sin_proveedor' => $sinProveedor,
            'cobertura' => $ingredientesTotal > 0
                ? round((($ingredientesTotal - $sinProveedor) / $ingredientesTotal) * 100, 1)
                : 0,
        ];

        $proveedores = $query->paginate(15);

        return view('finanzas.proveedores.index', compact('proveedores', 'lectura'));
    }

    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        $proveedores = Proveedor::query()
            ->select(['id', 'nombre'])
            ->buscar($validated['q'] ?? null)
            ->orderBy('nombre')
            ->limit(20)
            ->get();

        return response()->json(['data' => $proveedores]);
    }

    public function create(Request $request): View
    {
        [$contexto, $ingredienteId] = $this->contexto($request);

        return view('finanzas.proveedores.form', [
            'proveedor' => new Proveedor,
            'contexto' => $contexto,
            'ingredienteId' => $ingredienteId,
        ]);
    }

    public function store(ProveedorRequest $request, CrearProveedor $action): RedirectResponse
    {
        $proveedor = $action->execute($request->validated());
        $creado = $proveedor->wasRecentlyCreated;
        $mensaje = $creado
            ? 'Proveedor creado correctamente.'
            : 'El proveedor ya existia y fue seleccionado.';

        return $this->redirigir($proveedor, $request->validated(), $mensaje, $creado);
    }

    public function show(Proveedor $proveedor): View
    {
        $proveedor->load(['ingredientes' => fn ($q) => $q->orderBy('nombre'), 'compras' => function ($q) {
            $q->with('detalles.ingrediente')->orderByDesc('fecha')->limit(20);
        }]);

        $historialPrecios = CompraDetalle::query()
            ->with(['compra', 'ingrediente'])
            ->join('compras', 'compra_detalles.compra_id', '=', 'compras.id')
            ->where('compras.proveedor_id', $proveedor->id)
            ->select('compra_detalles.*')
            ->orderByDesc('compras.fecha')
            ->limit(50)
            ->get();

        return view('finanzas.proveedores.show', compact('proveedor', 'historialPrecios'));
    }

    public function edit(Proveedor $proveedor): View
    {
        return view('finanzas.proveedores.form', [
            'proveedor' => $proveedor,
            'contexto' => 'proveedores',
            'ingredienteId' => null,
        ]);
    }

    public function update(
        ProveedorRequest $request,
        Proveedor $proveedor,
        ActualizarProveedor $action
    ): RedirectResponse {
        $proveedor = $action->execute($proveedor, $request->validated());

        return redirect()->route('finanzas.proveedores.show', $proveedor)
            ->with('success', 'Proveedor actualizado correctamente.');
    }

    private function contexto(Request $request): array
    {
        $permitidos = ['proveedores', 'ingrediente_create', 'ingrediente_edit', 'gasto_create'];
        $contexto = in_array($request->query('contexto'), $permitidos, true)
            ? $request->query('contexto')
            : 'proveedores';
        $ingredienteId = $request->integer('ingrediente_id') ?: null;

        if ($contexto === 'ingrediente_edit' && ! Ingrediente::whereKey($ingredienteId)->exists()) {
            return ['proveedores', null];
        }

        return [$contexto, $ingredienteId];
    }

    private function redirigir(
        Proveedor $proveedor,
        array $data,
        string $mensaje,
        bool $creado
    ): RedirectResponse {
        return match ($data['contexto'] ?? 'proveedores') {
            'ingrediente_create' => redirect()->route('ingredientes.create', [
                'proveedor_id' => $proveedor->id,
                'restaurar' => 1,
            ])->with('success', $mensaje),
            'ingrediente_edit' => redirect()->route('ingredientes.edit', [
                'ingrediente' => $data['ingrediente_id'],
                'proveedor_id' => $proveedor->id,
                'restaurar' => 1,
            ])->with('success', $mensaje),
            'gasto_create' => redirect()->route('finanzas.gastos.create', [
                'proveedor_id' => $proveedor->id,
                'restaurar' => 1,
            ])->with('success', $mensaje),
            default => redirect()->route(
                $creado ? 'finanzas.proveedores.index' : 'finanzas.proveedores.show',
                $creado ? [] : $proveedor
            )->with('success', $mensaje),
        };
    }
}
