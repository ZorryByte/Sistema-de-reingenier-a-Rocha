<?php

namespace App\Services;

use App\Models\Gasto;
use App\Models\RecordatorioGasto;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class GastosAnalisisService
{
    public function resumenListado(Builder $query): array
    {
        $registrados = (clone $query)->registrados();

        return [
            'total' => (float) (clone $registrados)->sum('monto'),
            'registros' => (clone $registrados)->count(),
            'categorias' => (clone $registrados)->distinct()->count('categoria_id'),
            'anulados' => (clone $query)->where('estado', 'anulado')->count(),
        ];
    }

    public function periodo(CarbonInterface $desde, CarbonInterface $hasta): array
    {
        $dias = $desde->diffInDays($hasta) + 1;
        $hastaAnterior = $desde->copy()->subDay()->endOfDay();
        $desdeAnterior = $hastaAnterior->copy()->subDays($dias - 1)->startOfDay();
        $actual = $this->basePeriodo($desde, $hasta);
        $anterior = $this->basePeriodo($desdeAnterior, $hastaAnterior);
        $total = (float) (clone $actual)->sum('monto');
        $totalAnterior = (float) (clone $anterior)->sum('monto');
        $distribucion = $this->distribucion($actual);

        return [
            'total' => $total,
            'variacion' => $totalAnterior > 0 ? (($total - $totalAnterior) / $totalAnterior) * 100 : null,
            'fijos' => (float) (clone $actual)->whereHas('categoria', fn ($q) => $q->where('tipo', 'fijo'))->sum('monto'),
            'variables' => (float) (clone $actual)->whereHas('categoria', fn ($q) => $q->where('tipo', 'variable'))->sum('monto'),
            'distribucion' => $distribucion,
            'categoria_principal' => $distribucion->first(),
            'categoria_creciente' => $this->categoriaCreciente($actual, $anterior),
            'promedio_mensual' => $this->promedioMensual(),
            'proveedores' => $this->proveedores($actual),
            'recordatorios_pendientes' => RecordatorioGasto::pendientes()->count(),
            'variacion_recordatorios' => $this->variacionRecordatorios($actual),
        ];
    }

    public function serieSeisMeses(): Collection
    {
        $inicio = now()->subMonthsNoOverflow(5)->startOfMonth();
        $fin = now()->endOfMonth();
        $driver = DB::connection()->getDriverName();
        $periodoSql = $driver === 'sqlite'
            ? "strftime('%Y-%m', fecha)"
            : "DATE_FORMAT(fecha, '%Y-%m')";
        $totales = Gasto::query()
            ->registrados()
            ->whereBetween('fecha', [$inicio, $fin])
            ->selectRaw("$periodoSql as periodo, SUM(monto) as total, COUNT(*) as cantidad")
            ->groupBy('periodo')
            ->get()
            ->keyBy('periodo');

        return collect(range(0, 5))->map(function (int $offset) use ($inicio, $totales) {
            $mes = $inicio->copy()->addMonthsNoOverflow($offset);
            $key = $mes->format('Y-m');

            return [
                'key' => $key,
                'label' => ucfirst($mes->locale('es')->translatedFormat('M')),
                'total' => round((float) ($totales->get($key)?->total ?? 0), 2),
                'cantidad' => (int) ($totales->get($key)?->cantidad ?? 0),
            ];
        });
    }

    private function basePeriodo(CarbonInterface $desde, CarbonInterface $hasta): Builder
    {
        return Gasto::query()->registrados()->whereBetween('fecha', [$desde, $hasta]);
    }

    private function distribucion(Builder $query): Collection
    {
        return (clone $query)
            ->select('categoria_id', DB::raw('SUM(monto) as total, COUNT(*) as cantidad'))
            ->groupBy('categoria_id')
            ->with('categoria:id,nombre,tipo')
            ->get()
            ->map(fn ($fila) => [
                'id' => $fila->categoria_id,
                'nombre' => $fila->categoria?->nombre ?? 'Sin categoría',
                'tipo' => $fila->categoria?->tipo?->value,
                'total' => (float) $fila->total,
            ])
            ->sortByDesc('total')
            ->values();
    }

    private function categoriaCreciente(Builder $actual, Builder $anterior): ?array
    {
        $actuales = $this->distribucion($actual)->keyBy('id');
        $anteriores = $this->distribucion($anterior)->keyBy('id');

        return $actuales->map(function (array $categoria, int $id) use ($anteriores) {
            $categoria['aumento'] = $categoria['total'] - ($anteriores->get($id)['total'] ?? 0);

            return $categoria;
        })->sortByDesc('aumento')->filter(fn ($categoria) => $categoria['aumento'] > 0)->first();
    }

    private function promedioMensual(): float
    {
        $desde = now()->subMonthsNoOverflow(5)->startOfMonth();

        return round((float) Gasto::registrados()->where('fecha', '>=', $desde)->sum('monto') / 6, 2);
    }

    private function proveedores(Builder $query): Collection
    {
        return (clone $query)
            ->whereNotNull('proveedor_id')
            ->select('proveedor_id', DB::raw('SUM(monto) as total, COUNT(*) as cantidad'))
            ->groupBy('proveedor_id')
            ->with('proveedor:id,nombre')
            ->orderByDesc('total')
            ->limit(5)
            ->get()
            ->map(fn ($fila) => [
                'nombre' => $fila->proveedor?->nombre ?? 'Sin proveedor',
                'total' => (float) $fila->total,
            ]);
    }

    private function variacionRecordatorios(Builder $query): ?float
    {
        $variaciones = (clone $query)
            ->whereNotNull('recordatorio_gasto_id')
            ->get()
            ->filter(fn ($gasto) => (float) $gasto->monto_estimado_recordatorio > 0)
            ->map(fn ($gasto) => (((float) $gasto->monto - (float) $gasto->monto_estimado_recordatorio) / (float) $gasto->monto_estimado_recordatorio) * 100);

        return $variaciones->isEmpty() ? null : round((float) $variaciones->avg(), 1);
    }
}
