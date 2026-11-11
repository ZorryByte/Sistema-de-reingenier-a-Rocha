<?php

namespace App\Http\Controllers;

use App\Enums\GastoFrecuenciaUnidad;
use App\Enums\GastoMetodoPago;
use App\Http\Requests\RecordatorioGastoRequest;
use App\Models\CategoriaGasto;
use App\Models\Proveedor;
use App\Models\RecordatorioGasto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RecordatorioGastoController extends Controller
{
    public function index()
    {
        $recordatorios = RecordatorioGasto::with(['categoria', 'proveedor'])
            ->orderBy('proxima_fecha')
            ->paginate(15);

        return view('finanzas.gastos.recordatorios.index', compact('recordatorios'));
    }

    public function create()
    {
        return $this->form();
    }

    public function store(RecordatorioGastoRequest $request)
    {
        $recordatorio = new RecordatorioGasto($request->validated());
        $recordatorio->user_id = $request->user()->id;
        $recordatorio->save();

        return redirect()->route('finanzas.gastos.recordatorios.index')
            ->with('success', 'Recordatorio creado correctamente.');
    }

    public function edit(RecordatorioGasto $recordatorio)
    {
        return $this->form($recordatorio);
    }

    public function update(RecordatorioGastoRequest $request, RecordatorioGasto $recordatorio)
    {
        $recordatorio->update($request->validated());

        return redirect()->route('finanzas.gastos.recordatorios.index')
            ->with('success', 'Recordatorio actualizado.');
    }

    public function toggle(Request $request, RecordatorioGasto $recordatorio)
    {
        abort_unless($request->user()?->esAdmin(), 403);

        DB::transaction(function () use ($recordatorio) {
            $recordatorio = RecordatorioGasto::lockForUpdate()->findOrFail($recordatorio->id);
            if (! $recordatorio->activo && ! $recordatorio->categoria()->where('activo', true)->exists()) {
                throw ValidationException::withMessages(['recordatorio' => 'La categoría está inactiva.']);
            }
            $recordatorio->update(['activo' => ! $recordatorio->activo]);
        });

        return back()->with('success', 'Estado del recordatorio actualizado.');
    }

    private function form(?RecordatorioGasto $recordatorio = null)
    {
        return view('finanzas.gastos.recordatorios.form', [
            'recordatorio' => $recordatorio,
            'categorias' => CategoriaGasto::activas()->orderBy('nombre')->get(),
            'proveedores' => Proveedor::orderBy('nombre')->get(['id', 'nombre']),
            'metodosPago' => GastoMetodoPago::cases(),
            'unidades' => GastoFrecuenciaUnidad::cases(),
        ]);
    }
}
