<?php

namespace App\Http\Controllers;

use App\Models\AsientoContable;
use Illuminate\Http\Request;

class LibroContableController extends Controller
{
    public function index(Request $request)
    {
        $query = AsientoContable::with(['detalles.cuenta', 'usuario'])
            ->latest('fecha_contable');

        if ($request->filled('origen')) {
            $query->where('origen', $request->input('origen'));
        }

        $asientos = $query->paginate(20)->withQueryString();

        $lectura = [
            'asientos' => AsientoContable::count(),
            'debe' => (float) AsientoContable::query()
                ->join('asiento_detalles', 'asientos_contables.id', '=', 'asiento_detalles.asiento_contable_id')
                ->sum('asiento_detalles.debe'),
            'haber' => (float) AsientoContable::query()
                ->join('asiento_detalles', 'asientos_contables.id', '=', 'asiento_detalles.asiento_contable_id')
                ->sum('asiento_detalles.haber'),
        ];

        return view('finanzas.libro-contable.index', compact('asientos', 'lectura'));
    }

    public function show(AsientoContable $asiento)
    {
        $asiento->load(['detalles.cuenta', 'usuario', 'asientoReversado']);

        return view('finanzas.libro-contable.show', compact('asiento'));
    }
}
