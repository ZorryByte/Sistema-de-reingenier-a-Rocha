@props(['items' => [], 'ventasHoy' => 0])

@php $items = collect($items); @endphp

<section
    class="dashboard-surface dashboard-products h-full"
    x-data="{ state: 'success' }"
    data-state="success"
>
    <div class="dashboard-section-head dashboard-products-head">
        <div class="flex min-w-0 items-start gap-3">
            <svg class="dashboard-icon-title mt-0.5 text-bakery-brown" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M6 9l1.5 9.5a1 1 0 0 0 1 .8h7a1 1 0 0 0 1-.8L18 9" />
                <path d="M4 9h16" />
                <path d="M8 9V6a4 4 0 0 1 8 0v3" />
            </svg>
            <div class="min-w-0">
                <h2 class="dashboard-h3">Productos con m&aacute;s salida</h2>
                <p class="dashboard-help">Seg&uacute;n ventas registradas hoy.</p>
            </div>
        </div>
        <span class="dashboard-products-meta">{{ $ventasHoy }} ventas</span>
    </div>

    <div x-show="state === 'loading'" class="dashboard-skeleton space-y-3 p-4" aria-hidden="true">
        <div class="h-4 w-3/4 rounded bg-bakery-amber/15"></div>
        <div class="h-4 w-1/2 rounded bg-bakery-amber/15"></div>
        <div class="h-4 w-2/3 rounded bg-bakery-amber/15"></div>
    </div>

    <div x-show="state === 'error'" x-cloak class="dashboard-error-block" role="alert">
        Dato no disponible. <button type="button" class="font-bold text-bakery-brown underline-offset-4 hover:underline" x-on:click="$store.dashboard.retry()">Reintentar</button>
    </div>

    <div x-show="state === 'success'">
        @if($items->isEmpty())
            <div class="dashboard-empty dashboard-products-empty">
                <p class="font-semibold text-bakery-dark">Caja a&uacute;n sin movimiento</p>
                <p>Registra la primera venta para ver qu&eacute; productos est&aacute;n saliendo m&aacute;s.</p>
            </div>
        @else
            @php $maxVentas = $items->max('total_vendido') ?? 0; @endphp
            <div class="dashboard-products-list">
                @foreach($items->take(3) as $producto)
                    @php $percentage = $maxVentas > 0 ? ($producto['total_vendido'] / $maxVentas) * 100 : 0; @endphp
                    <div class="dashboard-product-row">
                        <div class="flex items-center justify-between gap-3">
                            <span class="truncate text-sm font-semibold text-bakery-dark">{{ $producto['nombre'] }}</span>
                            <span class="text-xs font-bold text-bakery-brown tabular-nums">x{{ $producto['total_vendido'] }}</span>
                        </div>
                        <div class="dashboard-product-track">
                            <div style="width: {{ $percentage }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</section>
