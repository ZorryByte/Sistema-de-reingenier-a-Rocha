<x-app-layout>
@php
    $rows = [
        [
            'label' => 'Ingresos registrados',
            'actual' => $ventas,
            'anterior' => $ventasAnterior,
            'change' => $comparativo['ventas'],
            'note' => 'Ventas pagadas del periodo.',
            'tone' => 'text-emerald-700',
        ],
        [
            'label' => 'Compras registradas de ingredientes',
            'actual' => $compras,
            'anterior' => $comprasAnterior,
            'change' => $comparativo['compras'],
            'note' => 'Entrada de inventario; validar tratamiento con contador.',
            'tone' => 'text-bakery-brown',
        ],
        [
            'label' => 'Gastos registrados',
            'actual' => $gastos,
            'anterior' => $gastosAnterior,
            'change' => $comparativo['gastos'],
            'note' => 'Gastos operativos capturados.',
            'tone' => 'text-bakery-terracotta',
        ],
        [
            'label' => 'Resultado antes de costo de ventas',
            'actual' => $resultado,
            'anterior' => $resultadoAnterior,
            'change' => $comparativo['resultado'],
            'note' => 'Ingresos menos gastos externos. No descuenta costo de ventas.',
            'tone' => $resultado >= 0 ? 'text-emerald-700' : 'text-red-700',
        ],
    ];

    $formatChange = function ($value) {
        if ($value === null) {
            return 'Sin base anterior';
        }

        return ($value >= 0 ? '+' : '').number_format($value, 1).'%';
    };

    $flujoTotal = max(0.01, abs((float) $ventas) + abs((float) $compras) + abs((float) $gastos));
@endphp

<div class="finance-page">
    <div class="mb-6 finance-header">
        <div>
            <p class="finance-kicker">Apoyo contable: {{ $periodo['desde'] }} - {{ $periodo['hasta'] }}</p>
            <h2 class="finance-title">Resultado antes de costo de ventas</h2>
            <p class="finance-description">
                Resumen segun ventas pagadas, compras y gastos registrados. Sirve para revision del contador, no reemplaza cierre fiscal, conciliacion bancaria ni estados financieros formales.
            </p>
        </div>

        <form method="GET" action="{{ route('finanzas.reportes.resultados') }}" class="finance-filter w-full sm:w-auto">
            <label for="mes" class="form-label">Periodo</label>
            <select id="mes" name="mes" class="form-select sm:w-52">
                @foreach($meses as $m)
                    <option value="{{ $m['value'] }}" {{ $mes == $m['value'] ? 'selected' : '' }}>{{ $m['label'] }}</option>
                @endforeach
            </select>
            <button type="submit" class="btn-secondary mt-2 w-full text-xs">Aplicar</button>
        </form>
    </div>

    <section class="finance-hero-panel mb-6">
        <div class="grid grid-cols-1 lg:grid-cols-[minmax(0,1fr)_19rem]">
            <div class="p-5 sm:p-6">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <p class="text-xs font-semibold text-bakery-muted">Resultado antes de costo de ventas</p>
                        <p class="mt-2 text-4xl font-display font-bold tabular-nums {{ $resultado >= 0 ? 'text-emerald-700' : 'text-red-700' }}">
                            {{ \App\Support\DisplayNumber::money($resultado) }}
                        </p>
                        <p class="mt-2 max-w-2xl text-sm leading-relaxed text-bakery-muted">
                            {{ $resultado >= 0 ? 'Cobertura positiva antes del costo de productos vendidos.' : 'Los gastos externos superan los ingresos registrados.' }}
                        </p>
                    </div>
                    <span class="finance-chip">{{ $formatChange($comparativo['resultado']) }} contra {{ $periodo['anterior'] }}</span>
                </div>

                <div class="mt-6 space-y-4">
                    @foreach(collect($rows)->take(3) as $row)
                        @php
                            $width = (abs((float) $row['actual']) / $flujoTotal) * 100;
                            $barClass = $row['label'] === 'Ingresos registrados' ? 'bg-emerald-600' : ($row['label'] === 'Gastos registrados' ? 'bg-bakery-terracotta' : 'bg-bakery-brown');
                        @endphp
                        <div>
                            <div class="flex items-center justify-between gap-3 text-sm">
                                <span class="font-semibold text-bakery-dark">{{ $row['label'] }}</span>
                                <span class="tabular-nums {{ $row['tone'] }}">{{ \App\Support\DisplayNumber::money($row['actual']) }}</span>
                            </div>
                            <div class="mt-2 h-2 overflow-hidden rounded-full bg-bakery-warm">
                                <span class="block h-full rounded-full {{ $barClass }}" style="width: {{ $width }}%"></span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <aside class="finance-hero-dark p-5">
                <p class="text-xs font-bold text-white/60">Alcance del calculo</p>
                <ul class="mt-4 space-y-3 text-xs leading-relaxed text-white/70">
                    <li>Incluye ventas con estado pagada.</li>
                    <li>Las compras se muestran aparte como inventario.</li>
                    <li>No incluye conciliacion bancaria, impuestos ni ajustes contables.</li>
                </ul>
            </aside>
        </div>
    </section>

    <div class="finance-data-strip mb-6 bg-white px-4 sm:px-5 md:grid-cols-4">
        @foreach($rows as $row)
            <div class="finance-data-cell">
                <p class="text-xs font-semibold text-bakery-muted">{{ $row['label'] }}</p>
                <p class="mt-2 text-2xl font-bold tabular-nums {{ $row['tone'] }}">{{ \App\Support\DisplayNumber::money($row['actual']) }}</p>
                <div class="mt-3 flex items-center justify-between gap-2 text-xs">
                    <span class="text-bakery-muted">{{ $periodo['anterior'] }}</span>
                    <span class="font-semibold {{ ($row['change'] ?? 0) >= 0 ? 'text-emerald-700' : 'text-red-700' }}">{{ $formatChange($row['change']) }}</span>
                </div>
                <p class="mt-2 text-xs leading-relaxed text-bakery-muted">{{ $row['note'] }}</p>
            </div>
        @endforeach
    </div>

    <div class="finance-table-wrap">
        <div class="flex flex-col gap-1 border-b border-bakery-amber/10 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h3 class="text-sm font-bold text-bakery-dark">Gastos por categoria</h3>
                <p class="text-xs text-bakery-muted">Detalle usado en el resumen del periodo.</p>
            </div>
            <span class="text-xs font-semibold text-bakery-brown">{{ $gastosPorCategoria->count() }} categorias</span>
        </div>
        <div class="p-4">
            @forelse($gastosPorCategoria as $gc)
                <div class="flex items-center justify-between border-b border-bakery-amber/5 py-2 last:border-0">
                    <span class="text-sm text-bakery-dark">{{ $gc->categoria->nombre }}</span>
                    <span class="text-sm font-semibold text-bakery-terracotta">{{ \App\Support\DisplayNumber::money($gc->total) }}</span>
                </div>
            @empty
                <div class="py-8 text-center text-sm text-bakery-muted">
                    No hay gastos registrados en este periodo. Si el negocio si tuvo gastos, registra esos gastos antes de compartir el resumen.
                </div>
            @endforelse
        </div>
    </div>
</div>
</x-app-layout>

