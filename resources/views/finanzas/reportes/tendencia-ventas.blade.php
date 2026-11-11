<x-app-layout>
@php
    $rows = $periodo === 'semanal' ? $semanas : $meses;
    $maxIngresos = max(0.01, (float) $rows->max('ingresos'));
    $totalIngresos = (float) $rows->sum('ingresos');
    $totalVentas = (int) $rows->sum('ventas');
    $mejorPeriodo = $rows->sortByDesc('ingresos')->first();
@endphp

<div class="finance-page">
    <div class="finance-stack">
        <header class="finance-header">
            <div>
                <p class="finance-kicker">Ingresos pagados</p>
                <h2 class="finance-title">Tendencia de ventas</h2>
                <p class="finance-description">Evolucion de ventas pagadas e ingresos para revisar continuidad del flujo operativo.</p>
            </div>

            <form method="GET" action="{{ route('finanzas.reportes.tendencia-ventas') }}" class="finance-filter w-full lg:w-auto">
                <label for="periodo" class="form-label">Periodo</label>
                <select id="periodo" name="periodo" class="form-select sm:w-64" onchange="this.form.submit()">
                    <option value="mensual" {{ $periodo == 'mensual' ? 'selected' : '' }}>Mensual (ultimos 12 meses)</option>
                    <option value="semanal" {{ $periodo == 'semanal' ? 'selected' : '' }}>Semanal (ultimas 8 semanas)</option>
                </select>
            </form>
        </header>

        <section class="finance-hero-panel">
            <div class="grid grid-cols-1 lg:grid-cols-[minmax(0,1fr)_18rem]">
                <div class="p-5 sm:p-6">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <h3 class="finance-section-title">Forma del ingreso</h3>
                            <p class="mt-1 text-xs text-bakery-muted">Cada barra compara ingresos pagados contra el mejor periodo visible.</p>
                        </div>
                        <div class="text-sm font-semibold text-emerald-700 tabular-nums">{{ \App\Support\DisplayNumber::money($totalIngresos) }}</div>
                    </div>

                    <div class="mt-5 hidden h-56 items-end gap-2 border-b border-bakery-amber/15 pb-4 md:flex lg:gap-3" aria-hidden="true">
                        @foreach($rows as $row)
                            @php($height = $row['ingresos'] > 0 ? ($row['ingresos'] / $maxIngresos) * 100 : 0)
                            <div class="flex min-w-0 flex-1 flex-col items-center gap-2">
                                <div class="flex h-44 w-full items-end justify-center">
                                    <span class="finance-chart-bar w-full max-w-7 bg-emerald-600" style="height: {{ $height }}%" title="{{ $row['periodo'] }}: {{ \App\Support\DisplayNumber::money($row['ingresos']) }}"></span>
                                </div>
                                <span class="max-w-full truncate text-[11px] font-semibold text-bakery-muted">{{ $row['periodo'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                <aside class="finance-hero-dark p-5">
                    <p class="text-xs font-bold text-white/60">Resumen</p>
                    <dl class="mt-4 space-y-4">
                        <div>
                            <dt class="text-xs text-white/55">Ventas pagadas</dt>
                            <dd class="mt-1 text-2xl font-bold tabular-nums">{{ $totalVentas }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-white/55">Mejor periodo</dt>
                            <dd class="mt-1 text-sm font-semibold">{{ $mejorPeriodo['periodo'] ?? 'Sin datos' }}</dd>
                            <dd class="mt-1 text-xs text-white/55">{{ \App\Support\DisplayNumber::money((float) ($mejorPeriodo['ingresos'] ?? 0)) }}</dd>
                        </div>
                    </dl>
                </aside>
            </div>
        </section>

        <section class="finance-table-wrap">
            <div class="flex items-center justify-between border-b border-bakery-amber/10 px-4 py-3">
                <h3 class="finance-section-title">Detalle por periodo</h3>
                <span class="text-xs font-semibold text-bakery-brown">{{ $rows->count() }} periodos</span>
            </div>
            <div class="finance-table-scroll" tabindex="0" role="region" aria-label="Ingresos por periodo">
                <table class="finance-table min-w-[520px]">
                    <thead>
                        <tr>
                            <th>Periodo</th>
                            <th>Ventas</th>
                            <th>Ingresos</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($rows as $row)
                            <tr class="finance-row">
                                <td class="font-medium text-bakery-dark">{{ $row['periodo'] }}</td>
                                <td class="text-bakery-dark tabular-nums">{{ $row['ventas'] }}</td>
                                <td class="font-semibold text-emerald-700 tabular-nums">{{ \App\Support\DisplayNumber::money($row['ingresos']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</div>
</x-app-layout>
