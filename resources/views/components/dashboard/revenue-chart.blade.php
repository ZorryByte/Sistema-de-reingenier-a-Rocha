@props(['items' => [], 'total' => 0, 'insights' => []])

@php
    $chartItems = collect($items)->map(fn ($dia) => [
        'label' => $dia['label'] ?? \Carbon\Carbon::parse($dia['fecha'])->locale('es')->translatedFormat('D d'),
        'total' => round((float) $dia['total'], 2),
        'cantidad' => (int) ($dia['cantidad'] ?? 0),
    ])->values();

    $ventasCount = $chartItems->sum('cantidad');
    $promedio = $ventasCount > 0 ? $total / $ventasCount : 0;
    $chartPayload = $chartItems->toJson(JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_TAG | JSON_HEX_QUOT);
@endphp

<section class="dashboard-surface dashboard-surface-chart dashboard-revenue" data-endpoint="{{ route('dashboard.ventas') }}">
    <header class="dashboard-section-head">
        <div>
            <p class="dashboard-caption">Ingresos</p>
            <h2 class="dashboard-h2">Ingresos</h2>
        </div>
        <div class="text-right">
            <p class="dashboard-number tabular-nums" data-chart-total>{{ \App\Support\DisplayNumber::money($total) }}</p>
            <p class="dashboard-caption" data-chart-meta>{{ $ventasCount }} ventas &middot; Prom. {{ \App\Support\DisplayNumber::money($promedio) }}</p>
        </div>
    </header>

    <div role="tablist" class="mb-4 flex gap-1" aria-label="Rango de ingresos">
        <button
            role="tab"
            id="revenue-tab-hoy"
            data-range="hoy"
            aria-selected="true"
            tabindex="0"
            aria-controls="revenue-tab-panel"
            class="finance-tab" type="button"
        >Hoy</button>
        <button
            role="tab"
            id="revenue-tab-semana"
            data-range="semana"
            aria-selected="false"
            tabindex="-1"
            aria-controls="revenue-tab-panel"
            class="finance-tab" type="button"
        >Semana</button>
        <button
            role="tab"
            id="revenue-tab-mes"
            data-range="mes"
            aria-selected="false"
            tabindex="-1"
            aria-controls="revenue-tab-panel"
            class="finance-tab" type="button"
        >Mes</button>
    </div>

    <div id="revenue-tab-panel" role="tabpanel" aria-labelledby="revenue-tab-hoy">
        <div class="dashboard-chart-wrap relative">
            <canvas
                id="revenue-chart-canvas"
                data-dashboard-revenue-chart
                data-chart='{{ $chartPayload }}'
                aria-label="Ingresos por periodo"
                role="img"
            ></canvas>
        </div>
        <p class="dashboard-caption" data-chart-loading role="status" hidden>Cargando periodo…</p>

        <div class="dashboard-chart-insight" role="status" aria-live="polite" data-insight>
            {{ $insights['caption'] ?? 'Sin ventas en la ventana' }}
        </div>
    </div>
    <p class="dashboard-error-block mt-4" role="alert" hidden data-chart-error>
        No se pudo cargar el periodo solicitado. Se conserva el periodo anterior. <button type="button" class="font-bold text-bakery-brown underline-offset-4 hover:underline" data-dashboard-retry>Reintentar</button>
    </p>
</section>
