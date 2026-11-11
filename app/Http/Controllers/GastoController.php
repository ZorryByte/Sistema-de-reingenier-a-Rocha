<?php

namespace App\Http\Controllers;

use App\Actions\AnularGasto;
use App\Actions\RegistrarGasto;
use App\Enums\GastoEstado;
use App\Enums\GastoMetodoPago;
use App\Http\Requests\AnularGastoRequest;
use App\Http\Requests\GastoFilterRequest;
use App\Http\Requests\GastoRequest;
use App\Models\CategoriaGasto;
use App\Models\Gasto;
use App\Models\Proveedor;
use App\Models\RecordatorioGasto;
use App\Services\GastosAnalisisService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class GastoController extends Controller
{
    public function index(GastoFilterRequest $request, GastosAnalisisService $analisis)
    {
        $filtros = $request->validated();
        $query = Gasto::query()
            ->with(['categoria', 'proveedor', 'user'])
            ->orderByDesc('fecha')
            ->porFecha($filtros['desde'] ?? null, $filtros['hasta'] ?? null)
            ->porCategoria($filtros['categoria_id'] ?? null)
            ->when($filtros['proveedor_id'] ?? null, fn ($q, $id) => $q->where('proveedor_id', $id))
            ->when($filtros['metodo_pago'] ?? null, fn ($q, $metodo) => $q->where('metodo_pago', $metodo))
            ->when($filtros['estado'] ?? null, fn ($q, $estado) => $q->where('estado', $estado));

        $lectura = $analisis->resumenListado(clone $query);
        $gastos = $query->paginate(15)->withQueryString();
        $analisisPeriodo = $analisis->periodo(now()->startOfMonth(), now()->endOfMonth());
        $serieSeisMeses = $analisis->serieSeisMeses();

        return view('finanzas.gastos.index', [
            'gastos' => $gastos,
            'lectura' => $lectura,
            'categorias' => CategoriaGasto::activas()->orderBy('nombre')->get(),
            'proveedores' => Proveedor::orderBy('nombre')->get(['id', 'nombre']),
            'metodosPago' => GastoMetodoPago::cases(),
            'estados' => GastoEstado::cases(),
            'analisisPeriodo' => $analisisPeriodo,
            'serieSeisMeses' => $serieSeisMeses,
        ]);
    }

    public function create(Request $request)
    {
        $recordatorio = null;
        if ($request->filled('recordatorio')) {
            $recordatorio = RecordatorioGasto::activos()
                ->with(['categoria', 'proveedor'])
                ->findOrFail((int) $request->input('recordatorio'));
        }

        $proveedorSeleccionado = $recordatorio?->proveedor;
        if (! $proveedorSeleccionado && $request->filled('proveedor_id')) {
            $proveedorSeleccionado = Proveedor::findOrFail((int) $request->input('proveedor_id'));
        }
        if (! $proveedorSeleccionado && old('proveedor_id')) {
            $proveedorSeleccionado = Proveedor::find((int) old('proveedor_id'));
        }

        return view('finanzas.gastos.create', [
            'categorias' => CategoriaGasto::activas()->orderBy('nombre')->get(),
            'metodosPago' => GastoMetodoPago::cases(),
            'recordatorio' => $recordatorio,
            'proveedorSeleccionado' => $proveedorSeleccionado,
            'tokenRegistro' => old('token_registro', (string) Str::uuid()),
        ]);
    }

    public function store(GastoRequest $request, RegistrarGasto $action)
    {
        $gasto = $action->execute($request->validated(), $request->user()->id);

        return redirect()->route('finanzas.gastos.show', $gasto)
            ->with('success', 'Gasto registrado correctamente.');
    }

    public function show(Gasto $gasto)
    {
        $gasto->load([
            'categoria.cuenta',
            'proveedor',
            'recordatorio',
            'user',
            'usuarioAnulacion',
            'asientos.detalles.cuenta',
            'asientos.asientoReversado',
        ]);

        return view('finanzas.gastos.show', compact('gasto'));
    }

    public function anular(AnularGastoRequest $request, Gasto $gasto, AnularGasto $action)
    {
        $action->execute($gasto, $request->validated('motivo'), $request->user()->id);

        return redirect()->route('finanzas.gastos.show', $gasto)
            ->with('success', 'Gasto anulado con su reversión contable.');
    }

    public function comprobante(Gasto $gasto)
    {
        abort_unless($gasto->comprobante_path && $gasto->comprobante_disk, 404);
        abort_unless(Storage::disk($gasto->comprobante_disk)->exists($gasto->comprobante_path), 404);

        return Storage::disk($gasto->comprobante_disk)->download(
            $gasto->comprobante_path,
            $gasto->comprobante_nombre ?: "comprobante-gasto-{$gasto->id}"
        );
    }
}
