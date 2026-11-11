<?php

namespace App\Http\Controllers;

use App\Enums\VentaEstado;
use App\Models\Compra;
use App\Models\Gasto;
use App\Models\Ingrediente;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\Venta;
use App\Services\ValorInventarioService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FinanzasController extends Controller
{
    public function index(ValorInventarioService $valorInventario)
    {
        $desde = now()->startOfMonth();
        $hasta = now()->endOfMonth();
        $desdeAnterior = now()->subMonthNoOverflow()->startOfMonth();
        $hastaAnterior = now()->subMonthNoOverflow()->endOfMonth();

        $ingresos = Venta::whereBetween('created_at', [$desde, $hasta])
            ->where('estado', VentaEstado::Pagada->value)
            ->sum('total');

        $compras = Compra::whereBetween('fecha', [$desde, $hasta])->sum('total');
        $gastos = Gasto::registrados()->whereBetween('fecha', [$desde, $hasta])->sum('monto');

        $ingresosAnterior = Venta::whereBetween('created_at', [$desdeAnterior, $hastaAnterior])
            ->where('estado', VentaEstado::Pagada->value)
            ->sum('total');
        $comprasAnterior = Compra::whereBetween('fecha', [$desdeAnterior, $hastaAnterior])->sum('total');
        $gastosAnterior = Gasto::registrados()->whereBetween('fecha', [$desdeAnterior, $hastaAnterior])->sum('monto');

        $metricas = [
            'ingresos' => (float) $ingresos,
            'compras' => (float) $compras,
            'gastos' => (float) $gastos,
            'resultado' => (float) $ingresos - (float) $gastos,
        ];

        $metricasAnteriores = [
            'ingresos' => (float) $ingresosAnterior,
            'compras' => (float) $comprasAnterior,
            'gastos' => (float) $gastosAnterior,
            'resultado' => (float) $ingresosAnterior - (float) $gastosAnterior,
        ];

        $variaciones = collect($metricas)->mapWithKeys(function (float $valor, string $clave) use ($metricasAnteriores) {
            $anterior = $metricasAnteriores[$clave] ?? 0;

            return [$clave => $anterior > 0 ? (($valor - $anterior) / $anterior) * 100 : null];
        })->all();

        $ingredientesSinProveedor = Ingrediente::whereNull('proveedor_id')->count();
        $ingredientesStockBajo = Ingrediente::query()->stockBajo()->count();
        $comprasSinProveedor = Compra::whereBetween('fecha', [$desde, $hasta])
            ->whereNull('proveedor_id')
            ->count();
        $comprasSinMovimiento = Compra::whereBetween('fecha', [$desde, $hasta])
            ->whereDoesntHave('movimientos')
            ->count();
        $productosSinReceta = Producto::activos()->whereDoesntHave('recetaActiva')->count();
        $proveedoresCount = Proveedor::count();
        $alertas = collect();
        if ($metricas['resultado'] < 0) {
            $alertas->push([
                'titulo' => 'Resultado negativo',
                'detalle' => 'Los gastos superan el dinero recibido.',
                'tono' => 'danger',
                'url' => route('finanzas.reportes.resultados'),
            ]);
        }
        if ($metricas['compras'] > 0 && $metricas['ingresos'] > 0 && $metricas['compras'] > ($metricas['ingresos'] * 0.55)) {
            $alertas->push([
                'titulo' => 'Compras altas',
                'detalle' => 'Revisa si son reposicion necesaria.',
                'tono' => 'warning',
                'url' => route('finanzas.compras.index'),
            ]);
        }
        if ($proveedoresCount === 0) {
            $alertas->push([
                'titulo' => 'Faltan proveedores',
                'detalle' => 'Completa proveedores para leer mejor las compras.',
                'tono' => 'neutral',
                'url' => route('finanzas.proveedores.index'),
            ]);
        }
        if ($comprasSinMovimiento > 0) {
            $alertas->push([
                'titulo' => 'Compras sin stock',
                'detalle' => 'Revisa compras que no movieron inventario.',
                'tono' => 'warning',
                'url' => route('finanzas.compras.index'),
            ]);
        }
        if ($alertas->isEmpty()) {
            $alertas->push([
                'titulo' => 'Sin alertas importantes',
                'detalle' => 'Los datos principales estan en orden.',
                'tono' => 'success',
                'url' => route('finanzas.reportes.resultados'),
            ]);
        }

        $senalesDatos = collect([
            [
                'titulo' => 'Ingredientes sin proveedor habitual',
                'cantidad' => $ingredientesSinProveedor,
                'detalle' => 'Reduce trazabilidad de precios y compras futuras.',
                'url' => route('ingredientes.index'),
                'accion' => 'Completar ingredientes',
            ],
            [
                'titulo' => 'Ingredientes con stock bajo',
                'cantidad' => $ingredientesStockBajo,
                'detalle' => 'Ayuda a separar reposicion real de compras extraordinarias.',
                'url' => route('ingredientes.index', ['filtro' => 'stock_bajo']),
                'accion' => 'Revisar stock',
            ],
            [
                'titulo' => 'Compras sin proveedor',
                'cantidad' => $comprasSinProveedor,
                'detalle' => 'Consulta heredada para preparar la futura activacion de compras.',
                'url' => route('finanzas.compras.index'),
                'accion' => 'Ver lectura',
            ],
            [
                'titulo' => 'Compras sin movimiento de inventario',
                'cantidad' => $comprasSinMovimiento,
                'detalle' => 'Cada compra historica debe alimentar el valor de inventario.',
                'url' => route('finanzas.compras.index'),
                'accion' => 'Auditar lectura',
            ],
            [
                'titulo' => 'Productos activos sin receta',
                'cantidad' => $productosSinReceta,
                'detalle' => 'Sin receta no hay costo estimado ni descuento fiable de ingredientes.',
                'url' => route('productos.index'),
                'accion' => 'Revisar productos',
            ],
        ])->map(function (array $senal) {
            $senal['estado'] = $senal['cantidad'] > 0 ? 'pendiente' : 'ok';

            return $senal;
        });

        $periodo = [
            'actual' => $desde->locale('es')->translatedFormat('F Y'),
            'desde' => $desde->format('d/m/Y'),
            'hasta' => $hasta->format('d/m/Y'),
            'anterior' => $desdeAnterior->locale('es')->translatedFormat('F Y'),
        ];

        $senalesPendientes = $senalesDatos->where('estado', 'pendiente')->count();
        $coberturaDato = $senalesDatos->isEmpty()
            ? 100
            : round(($senalesDatos->where('estado', 'ok')->count() / $senalesDatos->count()) * 100, 1);

        $ratios = [
            [
                'clave' => 'margen-operativo',
                'label' => 'Resultado / dinero',
                'valor' => $metricas['ingresos'] > 0 ? ($metricas['resultado'] / $metricas['ingresos']) * 100 : null,
                'detalle' => 'Resultado sobre dinero recibido.',
                'formato' => 'porcentaje',
            ],
            [
                'clave' => 'gastos-ingresos',
                'label' => 'Gastos / dinero',
                'valor' => $metricas['ingresos'] > 0 ? ($metricas['gastos'] / $metricas['ingresos']) * 100 : null,
                'detalle' => 'Peso de gastos del periodo.',
                'formato' => 'porcentaje',
            ],
            [
                'clave' => 'compras-ingresos',
                'label' => 'Compras / dinero',
                'valor' => $metricas['ingresos'] > 0 ? ($metricas['compras'] / $metricas['ingresos']) * 100 : null,
                'detalle' => 'Peso de compras del periodo.',
                'formato' => 'porcentaje',
            ],
            [
                'clave' => 'cobertura-dato',
                'label' => 'Datos completos',
                'valor' => $coberturaDato,
                'detalle' => 'Senales sin pendientes.',
                'formato' => 'porcentaje',
            ],
        ];

        $lecturaFinanciera = $this->lecturaFinanciera($metricas, $ratios, $senalesPendientes);
        $rangosFinancieros = [
            'mes' => $this->serieFinanciera('mes'),
            'trimestre' => $this->serieFinanciera('trimestre'),
            'anio' => $this->serieFinanciera('anio'),
        ];
        $insightsFinancieros = collect($rangosFinancieros)
            ->mapWithKeys(fn (array $serie, string $range) => [$range => $this->insightFinanciero($serie)])
            ->all();
        $inventarioResumen = $valorInventario->resumen();
        $inventarioGrafico = [
            'labels' => ['Ingredientes', 'Productos'],
            'values' => [
                round((float) $inventarioResumen['ingredientes'], 2),
                round((float) $inventarioResumen['productos'], 2),
            ],
            'total' => round((float) $inventarioResumen['total'], 2),
        ];
        $tendenciaBarras = collect($rangosFinancieros['mes']['items'])->map(fn (array $item) => [
            'label' => $item['label'],
            'cantidad' => $item['cantidad'],
            'resultado' => $item['resultado'],
            'gastos' => $item['gastos'],
            'compras' => $item['compras'],
        ])->values()->all();
        $proveedoresPeriodo = Compra::query()
            ->with('proveedor')
            ->selectRaw('proveedor_id, SUM(total) as total_compras, COUNT(*) as compras_count')
            ->whereBetween('fecha', [$desde, $hasta])
            ->whereNotNull('proveedor_id')
            ->groupBy('proveedor_id')
            ->orderByDesc('total_compras')
            ->take(5)
            ->get()
            ->map(fn (Compra $compra) => [
                'nombre' => $compra->proveedor?->nombre ?? 'Sin proveedor',
                'total' => (float) $compra->total_compras,
                'compras' => (int) $compra->compras_count,
                'url' => $compra->proveedor ? route('finanzas.proveedores.show', $compra->proveedor) : route('finanzas.proveedores.index'),
            ]);
        $maxProveedor = max(1, (float) $proveedoresPeriodo->max('total'));
        $proveedoresBarra = $proveedoresPeriodo
            ->map(fn (array $proveedor) => $proveedor + [
                'porcentaje' => min(100, round(($proveedor['total'] / $maxProveedor) * 100, 1)),
            ])
            ->values()
            ->all();

        return view('finanzas.index', compact(
            'metricas',
            'variaciones',
            'alertas',
            'periodo',
            'lecturaFinanciera',
            'rangosFinancieros',
            'ratios',
            'insightsFinancieros',
            'inventarioGrafico',
            'tendenciaBarras',
            'proveedoresBarra'
        ));
    }

    public function resumen(Request $request): JsonResponse
    {
        $range = $request->validate([
            'range' => ['nullable', 'in:mes,trimestre,anio'],
        ])['range'] ?? 'mes';

        $serie = $this->serieFinanciera($range);
        $serie['insight'] = $this->insightFinanciero($serie);

        return response()->json($serie, 200, [], JSON_NUMERIC_CHECK);
    }

    private function lecturaFinanciera(array $metricas, array $ratios, int $senalesPendientes): array
    {
        $gastosRatio = collect($ratios)->firstWhere('clave', 'gastos-ingresos')['valor'] ?? null;
        $comprasRatio = collect($ratios)->firstWhere('clave', 'compras-ingresos')['valor'] ?? null;

        if ($metricas['resultado'] < 0) {
            return [
                'estado' => 'alerta',
                'tono' => 'danger',
                'headline' => 'Gastos por encima del dinero recibido',
                'detalle' => 'Los gastos superan el dinero recibido.',
            ];
        }

        if (($gastosRatio !== null && $gastosRatio >= 45) || ($comprasRatio !== null && $comprasRatio >= 55) || $senalesPendientes >= 3) {
            return [
                'estado' => 'presionado',
                'tono' => 'warning',
                'headline' => 'El periodo pide revision',
                'detalle' => 'Compras o gastos estan pesando mas de lo normal.',
            ];
        }

        return [
            'estado' => 'sano',
            'tono' => 'success',
            'headline' => 'Flujo operativo sano',
            'detalle' => 'El periodo va estable.',
        ];
    }

    private function serieFinanciera(string $range): array
    {
        $config = match ($range) {
            'trimestre' => [
                'label' => 'Trimestre',
                'window' => 'Ultimos 4 trimestres',
                'count' => 3,
                'start' => fn (int $i) => now()->subQuarters($i)->startOfQuarter(),
                'end' => fn (Carbon $date) => $date->copy()->endOfQuarter(),
                'labelFor' => fn (Carbon $date) => 'T'.$date->quarter.' '.$date->format('Y'),
            ],
            'anio' => [
                'label' => 'Ano',
                'window' => 'Ultimos 5 anos',
                'count' => 4,
                'start' => fn (int $i) => now()->subYears($i)->startOfYear(),
                'end' => fn (Carbon $date) => $date->copy()->endOfYear(),
                'labelFor' => fn (Carbon $date) => $date->format('Y'),
            ],
            default => [
                'label' => 'Mes',
                'window' => 'Ultimos 6 meses',
                'count' => 5,
                'start' => fn (int $i) => now()->subMonthsNoOverflow($i)->startOfMonth(),
                'end' => fn (Carbon $date) => $date->copy()->endOfMonth(),
                'labelFor' => fn (Carbon $date) => ucfirst($date->locale('es')->translatedFormat('M Y')),
            ],
        };

        $items = collect(range($config['count'], 0))->map(function (int $offset) use ($config) {
            $inicio = $config['start']($offset);
            $fin = $config['end']($inicio);
            $resumen = $this->resumenPeriodo($inicio, $fin);

            return [
                'label' => $config['labelFor']($inicio),
                'cantidad' => $resumen['cantidad'],
                'ingresos' => $resumen['ingresos'],
                'compras' => $resumen['compras'],
                'gastos' => $resumen['gastos'],
                'resultado' => $resumen['resultado'],
            ];
        })->values();

        return [
            'range' => $range,
            'label' => $config['label'],
            'window' => $config['window'],
            'items' => $items->all(),
            'cantidad' => $items->sum('cantidad'),
            'total' => $items->sum('resultado'),
            'max' => max(1, (float) $items->flatMap(fn (array $item) => [
                abs($item['ingresos']),
                abs($item['compras']),
                abs($item['gastos']),
                abs($item['resultado']),
            ])->max()),
        ];
    }

    private function resumenPeriodo(Carbon $desde, Carbon $hasta): array
    {
        $ventas = Venta::whereBetween('created_at', [$desde, $hasta])
            ->where('estado', VentaEstado::Pagada->value)
            ->selectRaw('COUNT(*) as cantidad, SUM(total) as importe')->first();
        $comprasPeriodo = Compra::whereBetween('fecha', [$desde, $hasta])
            ->selectRaw('COUNT(*) as cantidad, SUM(total) as importe')->first();
        $gastosPeriodo = Gasto::registrados()->whereBetween('fecha', [$desde, $hasta])
            ->selectRaw('COUNT(*) as cantidad, SUM(monto) as importe')->first();
        $ingresos = (float) $ventas->importe;
        $compras = (float) $comprasPeriodo->importe;
        $gastos = (float) $gastosPeriodo->importe;

        return [
            'cantidad' => (int) ($ventas->cantidad + $comprasPeriodo->cantidad + $gastosPeriodo->cantidad),
            'ingresos' => $ingresos,
            'compras' => $compras,
            'gastos' => $gastos,
            'resultado' => $ingresos - $gastos,
        ];
    }

    private function insightFinanciero(array $serie): array
    {
        $items = collect($serie['items']);
        $actual = $items->last();
        $anterior = $items->slice(-2, 1)->first();

        if (! $actual || $serie['cantidad'] === 0) {
            return [
                'caption' => 'Sin datos para comparar.',
                'tono' => 'neutral',
            ];
        }

        if (! $anterior || (float) $anterior['resultado'] === 0.0) {
            return [
                'caption' => 'Resultado del último periodo: '.\App\Support\DisplayNumber::money($actual['resultado']).'.',
                'tono' => 'neutral',
            ];
        }

        $variacion = (($actual['resultado'] - $anterior['resultado']) / abs($anterior['resultado'])) * 100;
        $tendencia = $variacion >= 0 ? '+' : '';

        return [
            'caption' => "Resultado {$tendencia}".number_format($variacion, 1).'% vs periodo anterior.',
            'tono' => $variacion >= 0 ? 'success' : 'warning',
        ];
    }
}
