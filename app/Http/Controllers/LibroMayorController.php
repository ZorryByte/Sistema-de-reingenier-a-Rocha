<?php

namespace App\Http\Controllers;

use App\Http\Requests\LibroMayorRequest;
use App\Models\CuentaContable;
use App\Services\LibroMayor;
use Carbon\CarbonImmutable;
use Illuminate\Http\Response;
use Illuminate\Pagination\LengthAwarePaginator;

class LibroMayorController extends Controller
{
    private const POR_PAGINA = 25;

    public function __invoke(LibroMayorRequest $request, LibroMayor $libroMayor): Response
    {
        $filtros = $request->safe()->only(['cuenta_id', 'desde', 'hasta']);
        $cuentas = CuentaContable::orderBy('codigo')->get();
        $cuenta = $cuentas->find($filtros['cuenta_id'] ?? null);
        $mayor = $movimientos = null;

        if ($cuenta) {
            $pagina = (int) $request->validated('page', 1);
            $mayor = $libroMayor->consultar(
                $cuenta,
                CarbonImmutable::parse($filtros['desde'])->startOfDay(),
                CarbonImmutable::parse($filtros['hasta'])->startOfDay(),
                $pagina,
                self::POR_PAGINA,
            );
            $movimientos = new LengthAwarePaginator($mayor['filas'], $mayor['total'], self::POR_PAGINA, $pagina, [
                'path' => $request->url(),
                'query' => $filtros,
            ]);
        }

        return response()
            ->view('finanzas.libro-mayor.index', compact('filtros', 'cuentas', 'cuenta', 'mayor', 'movimientos'))
            ->header('Cache-Control', 'private, no-store');
    }
}
