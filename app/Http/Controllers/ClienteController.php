<?php

namespace App\Http\Controllers;

use App\Actions\GuardarCliente;
use App\Http\Requests\ClienteRequest;
use App\Models\Cliente;
use App\Services\HistorialCliente;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ClienteController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('administrar', Cliente::class);

        $filtros = $request->validate([
            'estado' => 'nullable|in:activos,archivados,todos',
            'q' => 'nullable|string|max:120',
        ]);
        $estado = $filtros['estado'] ?? 'activos';
        $texto = $filtros['q'] ?? null;
        $clientes = Cliente::query()->buscar($texto)
            ->when($estado === 'activos', fn ($query) => $query->activos())
            ->when($estado === 'archivados', fn ($query) => $query->whereNotNull('archivado_at'))
            ->orderBy('nombre')->orderBy('id')->paginate(20)->withQueryString();

        return view('clientes.index', compact('clientes', 'estado', 'texto'));
    }

    public function buscar(Request $request): JsonResponse
    {
        Gate::authorize('seleccionar', Cliente::class);

        $texto = trim($request->validate(['q' => 'required|string|max:120'])['q']);
        $resultados = mb_strlen($texto) < 2 ? collect() : Cliente::query()
            ->activos()->buscar($texto)->orderBy('nombre')->orderBy('id')->limit(10)->get();

        return response()->json($resultados->map($this->opcion(...)))->header('Cache-Control', 'no-store');
    }

    public function altaRapida(ClienteRequest $request, GuardarCliente $accion): JsonResponse
    {
        $cliente = $accion->crear($request->validated());

        return response()->json($this->opcion($cliente), $cliente->wasRecentlyCreated ? 201 : 200)
            ->header('Cache-Control', 'no-store');
    }

    public function create(): View
    {
        Gate::authorize('administrar', Cliente::class);

        return view('clientes.form', ['cliente' => new Cliente]);
    }

    public function store(ClienteRequest $request, GuardarCliente $accion): RedirectResponse
    {
        $cliente = $accion->crear($request->validated());

        return to_route('clientes.show', $cliente)->with('success',
            $cliente->wasRecentlyCreated ? 'Cliente registrado.' : 'Ya existe un cliente con ese teléfono.');
    }

    public function show(Cliente $cliente, HistorialCliente $historial): View
    {
        Gate::authorize('administrar', Cliente::class);

        return view('clientes.show', ['cliente' => $cliente] + $historial->consultar($cliente));
    }

    public function edit(Cliente $cliente): View
    {
        Gate::authorize('administrar', Cliente::class);

        return view('clientes.form', compact('cliente'));
    }

    public function update(ClienteRequest $request, Cliente $cliente, GuardarCliente $accion): RedirectResponse
    {
        $accion->actualizar($cliente, $request->validated());

        return to_route('clientes.show', $cliente)->with('success', 'Contacto actualizado.');
    }

    public function archivar(Cliente $cliente, GuardarCliente $accion): RedirectResponse
    {
        Gate::authorize('administrar', Cliente::class);
        $accion->cambiarArchivo($cliente, true);

        return to_route('clientes.show', $cliente)->with('success', 'Cliente archivado.');
    }

    public function reactivar(Cliente $cliente, GuardarCliente $accion): RedirectResponse
    {
        Gate::authorize('administrar', Cliente::class);
        $accion->cambiarArchivo($cliente, false);

        return to_route('clientes.show', $cliente)->with('success', 'Cliente reactivado.');
    }

    private function opcion(Cliente $cliente): array
    {
        return ['id' => $cliente->id, 'nombre' => $cliente->nombre, 'telefono' => $cliente->telefono_visible];
    }
}
