<?php

namespace App\Http\Controllers;

use App\Services\ValorInventarioService;

class ValorInventarioController extends Controller
{
    public function __invoke(ValorInventarioService $service)
    {
        $resumen = $service->resumen();

        return view('finanzas.reportes.valor-inventario', compact('resumen'));
    }
}
