<x-app-layout>
@php
    $formatMoney = fn ($value) => \App\Support\DisplayNumber::money($value);
    $formatChange = function ($value) {
        if ($value === null) {
            return 'Sin periodo anterior';
        }

        return ($value >= 0 ? '+' : '').number_format((float) $value, 1).'%';
    };

    $initialRange = $rangosFinancieros['mes'];
    $initialInsight = $insightsFinancieros['mes'];
    $chartPayload = collect($initialRange['items'])->values()->toJson(JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_TAG | JSON_HEX_QUOT);
    $profitabilityPayload = collect($initialRange['items'])->values()->toJson(JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_TAG | JSON_HEX_QUOT);
    $trendBarPayload = collect($tendenciaBarras)->values()->toJson(JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_TAG | JSON_HEX_QUOT);
    $inventoryPayload = collect($inventarioGrafico)->toJson(JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_TAG | JSON_HEX_QUOT);
    $rangePayload = collect($rangosFinancieros)->map(fn ($range) => [
        'range' => $range['range'],
        'label' => $range['label'],
        'window' => $range['window'],
        'items' => collect($range['items'])->values(),
        'cantidad' => $range['cantidad'],
        'total' => $range['total'],
        'insight' => $insightsFinancieros[$range['range']] ?? ['caption' => '', 'tono' => 'neutral'],
    ])->toJson(JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_TAG | JSON_HEX_QUOT);

    $alertTone = fn ($tono) => match($tono) {
        'danger' => 'finance-status-danger',
        'warning' => 'finance-status-warning',
        'success' => 'finance-status-success',
        default => 'finance-status-muted',
    };

@endphp

<main class="finance-page finance-analytic-page">
    <div class="finance-analytic-shell">
        <header class="finance-hero-bare finance-reveal" aria-label="Encabezado financiero">
            <div class="min-w-0">
                <p class="finance-caption">Lectura automatizada</p>
                <h1 class="finance-h1">Finanzas</h1>
                <p class="finance-body mt-2">
                    {{ $periodo['desde'] }} a {{ $periodo['hasta'] }}. Dinero recibido, gastos y compras.
                </p>
            </div>

            <div class="finance-actions">
                <a href="{{ route('finanzas.libro-contable.index') }}" class="btn-primary">Libro contable</a>
                <a href="{{ route('finanzas.reportes.resultados') }}" class="btn-secondary">Resultados</a>
                <a href="{{ route('finanzas.reportes.valor-inventario') }}" class="btn-ghost">Valor de inventario</a>
            </div>
        </header>

        <section class="finance-headline finance-reveal" aria-label="Lectura financiera del periodo">
            <h2 class="finance-h2">{{ $lecturaFinanciera['headline'] }}</h2>
            <p class="finance-body mt-1">{{ $lecturaFinanciera['detalle'] }}</p>
            <p class="finance-caption mt-2">
                Resultado {{ $formatChange($variaciones['resultado']) }} vs {{ $periodo['anterior'] }}.
            </p>
        </section>

        <section class="finance-zone-top finance-reveal" aria-label="Resultado, tendencia y revision">
            <div class="lg:col-span-8">
                <section
                    class="finance-surface finance-chart-surface"
                    aria-label="Tendencia financiera por rango"
                    data-finance-range-widget
                    data-endpoint="{{ route('finanzas.resumen') }}"
                    data-ranges='{{ $rangePayload }}'
                >
                    <header class="finance-section-head">
                        <div>
                            <p class="finance-caption">Resultado</p>
                            <h2 class="finance-h2">Tendencia de 6 meses</h2>
                        </div>
                        <div class="text-left sm:text-right">
                            <p class="finance-number" data-finance-range-total>{{ $formatMoney($initialRange['total']) }}</p>
                            <p class="finance-caption" data-finance-range-window>{{ $initialRange['window'] }}</p>
                        </div>
                    </header>

                    <div class="finance-tabs" role="tablist" aria-label="Rango de lectura financiera">
                        @foreach(['mes' => 'Mes', 'trimestre' => 'Trimestre', 'anio' => 'Ano'] as $range => $label)
                            <button
                                type="button"
                                role="tab"
                                class="finance-tab"
                                id="finance-tab-{{ $range }}"
                                data-finance-range-tab="{{ $range }}"
                                aria-controls="finance-tab-panel"
                                aria-selected="{{ $range === 'mes' ? 'true' : 'false' }}"
                            >
                                {{ $label }}
                            </button>
                        @endforeach
                    </div>

                    <div id="finance-tab-panel" role="tabpanel" aria-labelledby="finance-tab-mes">
                        <div class="finance-chart-frame" aria-busy="false">
                            <canvas
                                data-finance-range-chart
                                data-chart='{{ $chartPayload }}'
                                aria-label="Dinero recibido, compras, gastos y resultado por rango"
                                role="img"
                            ></canvas>
                        </div>
                        <p class="finance-caption" data-finance-range-loading role="status" hidden>Cargando periodo…</p>

                        <div class="finance-chart-insight" role="status" aria-live="polite" data-finance-range-insight>
                            {{ $initialInsight['caption'] }}
                        </div>
                    </div>

                    <p class="finance-error-block mt-3" role="alert" data-finance-range-error hidden>
                        No se pudo cargar el periodo solicitado. Se conserva el periodo anterior.
                        <button type="button" class="btn-ghost" data-finance-retry>Reintentar</button>
                    </p>
                </section>
            </div>

            <aside class="lg:col-span-4">
                <section class="finance-surface h-full" aria-label="Revision prioritaria">
                    <div class="finance-section-head">
                        <div>
                            <p class="finance-caption">Control</p>
                            <h2 class="finance-h2">Alertas</h2>
                        </div>
                    </div>

                    <div class="finance-priority-list">
                        @foreach($alertas->take(5) as $alerta)
                            <a href="{{ $alerta['url'] }}" class="finance-priority-row {{ $alertTone($alerta['tono']) }}">
                                <span>
                                    <span class="block text-sm font-bold text-bakery-dark">{{ $alerta['titulo'] }}</span>
                                    <span class="mt-1 block text-xs leading-relaxed text-bakery-muted">{{ $alerta['detalle'] }}</span>
                                </span>
                                <span class="text-xs font-bold text-bakery-brown">Revisar</span>
                            </a>
                        @endforeach
                    </div>
                </section>
            </aside>
        </section>

        <section class="finance-zone-mid finance-reveal" aria-label="Rentabilidad y analisis">
            <div class="lg:col-span-9">
                <section class="finance-surface" aria-label="Graficos de rentabilidad">
                    <div class="finance-section-head">
                        <div>
                            <p class="finance-caption">Como va la contaduria</p>
                            <h2 class="finance-h2">Rentabilidad</h2>
                        </div>
                        <a href="{{ route('finanzas.reportes.rentabilidad') }}" class="finance-section-link">Ver reporte</a>
                    </div>

                    <div class="finance-profit-grid">
                        <article class="finance-profit-chart">
                            <div class="finance-profit-head">
                                <p class="finance-caption">Resultado</p>
                                <h3 class="finance-h3">Margen</h3>
                            </div>
                            <div class="finance-mini-chart-frame">
                                <canvas data-finance-profit-chart="margen" data-items='{{ $profitabilityPayload }}' aria-label="Margen por mes" role="img"></canvas>
                            </div>
                        </article>

                        <article class="finance-profit-chart">
                            <div class="finance-profit-head">
                                <p class="finance-caption">Gastos</p>
                                <h3 class="finance-h3">Peso sobre dinero</h3>
                            </div>
                            <div class="finance-mini-chart-frame">
                                <canvas data-finance-profit-chart="gastos" data-items='{{ $profitabilityPayload }}' aria-label="Gastos sobre dinero recibido" role="img"></canvas>
                            </div>
                        </article>

                        <article class="finance-profit-chart">
                            <div class="finance-profit-head">
                                <p class="finance-caption">Compras</p>
                                <h3 class="finance-h3">Reposicion</h3>
                            </div>
                            <div class="finance-mini-chart-frame">
                                <canvas data-finance-profit-chart="compras" data-items='{{ $profitabilityPayload }}' aria-label="Compras sobre dinero recibido" role="img"></canvas>
                            </div>
                        </article>
                    </div>
                </section>
            </div>

            <aside class="lg:col-span-3">
                <section class="finance-surface h-full" aria-label="Compras por proveedor">
                    <div class="finance-section-head">
                        <div>
                            <p class="finance-caption">Compras</p>
                            <h2 class="finance-h2">Proveedores</h2>
                        </div>
                        <a href="{{ route('finanzas.reportes.precios-proveedor') }}" class="finance-section-link" aria-label="Ver precios por proveedor">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 7h10M9 12h10M9 17h10M5 7h.01M5 12h.01M5 17h.01"/>
                            </svg>
                        </a>
                    </div>

                    @if(count($proveedoresBarra) > 0)
                        <div class="finance-provider-bars">
                            @foreach($proveedoresBarra as $proveedor)
                                <a href="{{ $proveedor['url'] }}" class="finance-provider-row">
                                    <span class="finance-provider-icon">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 7h13v10H3z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 10h3l2 3v4h-5zM7 17a2 2 0 1 0 0 4 2 2 0 0 0 0-4ZM18 17a2 2 0 1 0 0 4 2 2 0 0 0 0-4Z"/>
                                        </svg>
                                    </span>
                                    <span class="min-w-0">
                                        <span class="finance-provider-top">
                                            <span class="truncate">{{ $proveedor['nombre'] }}</span>
                                            <span>{{ $formatMoney($proveedor['total']) }}</span>
                                        </span>
                                        <span class="finance-provider-track">
                                            <span style="width: {{ $proveedor['porcentaje'] }}%"></span>
                                        </span>
                                        <span class="finance-provider-meta">{{ $proveedor['compras'] }} compras</span>
                                    </span>
                                </a>
                            @endforeach
                        </div>
                    @else
                        <a href="{{ route('finanzas.proveedores.index') }}" class="finance-provider-empty">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 21a8 8 0 0 1 16 0"/>
                            </svg>
                            <span>Sin compras por proveedor este periodo.</span>
                        </a>
                    @endif
                </section>
            </aside>
        </section>

        <section class="finance-zone-bottom finance-reveal" aria-label="Tendencias e inventario">
            <div class="lg:col-span-12">
                <section class="finance-surface finance-closing-surface">
                    <div class="finance-section-head">
                        <div>
                            <p class="finance-caption">Cierre visual</p>
                            <h2 class="finance-h2">Tendencias e inventario</h2>
                        </div>
                        <a href="{{ route('finanzas.reportes.valor-inventario') }}" class="finance-section-link">Ver inventario</a>
                    </div>

                    <div class="finance-closing-grid">
                        <article class="finance-closing-chart">
                            <div class="finance-profit-head">
                                <p class="finance-caption">Ultimos meses</p>
                                <h3 class="finance-h3">Resultado, gastos y compras</h3>
                            </div>
                            <div class="finance-bar-chart-frame">
                                <canvas data-finance-trend-bars data-items='{{ $trendBarPayload }}' aria-label="Tendencias financieras en barras" role="img"></canvas>
                            </div>
                        </article>

                        <article class="finance-closing-chart">
                            <div class="finance-profit-head">
                                <p class="finance-caption">Inventario</p>
                                <h3 class="finance-h3">{{ $formatMoney($inventarioGrafico['total']) }}</h3>
                            </div>
                            <div class="finance-donut-chart-frame">
                                <canvas data-finance-inventory-donut data-items='{{ $inventoryPayload }}' aria-label="Valor de inventario por tipo" role="img"></canvas>
                            </div>
                        </article>
                    </div>
                </section>
            </div>
        </section>
    </div>
</main>
</x-app-layout>
