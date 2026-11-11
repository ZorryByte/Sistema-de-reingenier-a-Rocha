<?php

namespace App\Http\Controllers;

use App\Enums\VentaEstado;
use App\Models\CategoriaGasto;
use App\Models\Compra;
use App\Models\CompraDetalle;
use App\Models\Gasto;
use App\Models\Ingrediente;
use App\Models\Producto;
use App\Models\Venta;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReporteController extends Controller
{
    public function resultados(Request $request)
    {
        $validated = $request->validate([
            'mes' => ['nullable', 'date_format:Y-m'],
        ]);

        $mes = $validated['mes'] ?? now()->format('Y-m');
        $desde = Carbon::createFromFormat('Y-m', $mes)->startOfMonth();
        $hasta = Carbon::createFromFormat('Y-m', $mes)->endOfMonth();
        $desdeAnterior = $desde->copy()->subMonthNoOverflow()->startOfMonth();
        $hastaAnterior = $desdeAnterior->copy()->endOfMonth();

        $ventas = Venta::whereBetween('created_at', [$desde, $hasta])
            ->where('estado', VentaEstado::Pagada->value)
            ->sum('total');

        $compras = Compra::whereBetween('fecha', [$desde, $hasta])->sum('total');

        $gastos = Gasto::registrados()->whereBetween('fecha', [$desde, $hasta])->sum('monto');

        $ventasAnterior = Venta::whereBetween('created_at', [$desdeAnterior, $hastaAnterior])
            ->where('estado', VentaEstado::Pagada->value)
            ->sum('total');
        $comprasAnterior = Compra::whereBetween('fecha', [$desdeAnterior, $hastaAnterior])->sum('total');
        $gastosAnterior = Gasto::registrados()->whereBetween('fecha', [$desdeAnterior, $hastaAnterior])->sum('monto');

        $gastosPorCategoria = Gasto::query()
            ->registrados()
            ->select('categoria_id', DB::raw('SUM(monto) as total'))
            ->whereBetween('fecha', [$desde, $hasta])
            ->groupBy('categoria_id')
            ->with('categoria')
            ->get();

        $resultado = (float) $ventas - (float) $gastos;
        $resultadoAnterior = (float) $ventasAnterior - (float) $gastosAnterior;

        $comparativo = [
            'ventas' => $this->variacion((float) $ventas, (float) $ventasAnterior),
            'compras' => $this->variacion((float) $compras, (float) $comprasAnterior),
            'gastos' => $this->variacion((float) $gastos, (float) $gastosAnterior),
            'resultado' => $this->variacion($resultado, $resultadoAnterior),
        ];

        $periodo = [
            'actual' => $desde->translatedFormat('F Y'),
            'anterior' => $desdeAnterior->translatedFormat('F Y'),
            'desde' => $desde->format('d/m/Y'),
            'hasta' => $hasta->format('d/m/Y'),
        ];

        $meses = $this->mesesDisponibles();

        return view('finanzas.reportes.resultados', compact(
            'ventas', 'compras', 'gastos', 'resultado',
            'ventasAnterior', 'comprasAnterior', 'gastosAnterior', 'resultadoAnterior',
            'comparativo', 'gastosPorCategoria', 'meses', 'mes', 'periodo'
        ));
    }

    public function rentabilidad()
    {
        $productos = Producto::where('activo', true)
            ->with([
                'recetaDefault.detalles.ingrediente',
                'presentaciones' => fn ($query) => $query->activas()->with('recetaActiva.detalles.ingrediente'),
            ])
            ->get()
            ->map(function ($producto) {
                return $producto->presentaciones->map(function ($presentacion) use ($producto) {
                    $receta = $presentacion->recetaActiva ?? $producto->recetaDefault;
                    $costo = $receta ? $receta->costoPorLote() / $receta->unidadesPorLote($presentacion) : null;
                    $precio = (float) $presentacion->precio;
                    $margen = $costo === null ? null : $precio - $costo;
                    $porcentaje = $margen !== null && $precio > 0 ? round(($margen / $precio) * 100, 1) : null;

                    return [
                        'producto' => $producto->nombre,
                        'presentacion' => $presentacion->nombre,
                        'costo' => $costo,
                        'precio' => $precio,
                        'margen' => $margen,
                        'porcentaje' => $porcentaje,
                    ];
                });
            })
            ->flatten(1)
            ->sortByDesc('margen');

        return view('finanzas.reportes.rentabilidad', compact('productos'));
    }

    public function preciosProveedor(Request $request)
    {
        $validated = $request->validate([
            'ingrediente_id' => ['nullable', 'integer', 'exists:ingredientes,id'],
        ]);

        $ingredienteId = $validated['ingrediente_id'] ?? null;

        $ingredientes = Ingrediente::orderBy('nombre')->get();

        $historial = collect();

        if ($ingredienteId) {
            $historial = CompraDetalle::query()
                ->with('compra.proveedor')
                ->join('compras', 'compra_detalles.compra_id', '=', 'compras.id')
                ->where('compra_detalles.ingrediente_id', $ingredienteId)
                ->select('compra_detalles.*')
                ->orderByDesc('compras.fecha')
                ->get();
        }

        return view('finanzas.reportes.precios-proveedor', compact('historial', 'ingredientes', 'ingredienteId'));
    }

    public function gastosCategoria(Request $request)
    {
        $validated = $request->validate([
            'mes' => ['nullable', 'date_format:Y-m'],
        ]);

        $mes = $validated['mes'] ?? now()->format('Y-m');
        $desde = Carbon::createFromFormat('Y-m', $mes)->startOfMonth();
        $hasta = Carbon::createFromFormat('Y-m', $mes)->endOfMonth();

        $categorias = CategoriaGasto::query()
            ->withSum(['gastos' => function ($q) use ($desde, $hasta) {
                $q->registrados()->whereBetween('fecha', [$desde, $hasta]);
            }], 'monto')
            ->orderBy('nombre')
            ->get()
            ->map(function ($cat) {
                return [
                    'nombre' => $cat->nombre,
                    'tipo' => $cat->tipo->label(),
                    'total' => (float) ($cat->gastos_sum_monto ?? 0),
                ];
            })
            ->filter(fn ($c) => $c['total'] > 0)
            ->sortByDesc('total');

        $totalGeneral = $categorias->sum('total');

        $meses = $this->mesesDisponibles();

        return view('finanzas.reportes.gastos-categoria', compact('categorias', 'totalGeneral', 'meses', 'mes'));
    }

    public function tendenciaVentas(Request $request)
    {
        $validated = $request->validate([
            'periodo' => ['nullable', 'in:mensual,semanal'],
        ]);

        $periodo = $validated['periodo'] ?? 'mensual';

        if ($periodo === 'semanal') {
            $rangos = collect(range(7, 0))->map(function ($i) {
                $inicio = now()->subWeeks($i)->startOfWeek();

                return [
                    'periodo' => "Semana del {$inicio->format('d/m')}",
                    'inicio' => $inicio,
                    'fin' => $inicio->copy()->endOfWeek(),
                ];
            });

            $ventas = $this->ventasPagadasEnRangos($rangos);
            $semanas = $this->resumirVentasPorRango($rangos, $ventas);
        } else {
            $rangos = collect(range(11, 0))->map(function ($i) {
                $inicio = now()->subMonths($i)->startOfMonth();

                return [
                    'periodo' => $inicio->translatedFormat('F Y'),
                    'inicio' => $inicio,
                    'fin' => $inicio->copy()->endOfMonth(),
                ];
            });

            $ventas = $this->ventasPagadasEnRangos($rangos);
            $meses = $this->resumirVentasPorRango($rangos, $ventas);
        }

        return view('finanzas.reportes.tendencia-ventas', compact(
            $periodo === 'semanal' ? 'semanas' : 'meses',
            'periodo'
        ));
    }

    private function mesesDisponibles()
    {
        return collect(range(0, 11))->map(function ($i) {
            $fecha = now()->subMonths($i);

            return [
                'value' => $fecha->format('Y-m'),
                'label' => $fecha->translatedFormat('F Y'),
            ];
        });
    }

    private function variacion(float $actual, float $anterior): ?float
    {
        if ($anterior == 0.0) {
            return null;
        }

        return (($actual - $anterior) / abs($anterior)) * 100;
    }

    private function ventasPagadasEnRangos($rangos)
    {
        return Venta::query()
            ->where('estado', VentaEstado::Pagada->value)
            ->whereBetween('created_at', [$rangos->first()['inicio'], $rangos->last()['fin']])
            ->get(['id', 'total', 'created_at']);
    }

    private function resumirVentasPorRango($rangos, $ventas)
    {
        return $rangos->map(function ($rango) use ($ventas) {
            $ventasDelPeriodo = $ventas->filter(fn ($venta) => $venta->created_at->betweenIncluded($rango['inicio'], $rango['fin']));

            return [
                'periodo' => $rango['periodo'],
                'ventas' => $ventasDelPeriodo->count(),
                'ingresos' => (float) $ventasDelPeriodo->sum('total'),
            ];
        });
    }
}

