@props([
    'orders' => [],
    'pedidos' => 0,
    'ordenes' => 0,
    'pendientes' => 0,
])

@php
    $total = count($orders);
    $visibleOrders = collect($orders)->take(5);
    $operacionLabel = $total > 0
        ? $total . ' en preparaci&oacute;n'
        : 'Sin preparaci&oacute;n activa';
@endphp

<section class="dashboard-surface dashboard-kitchen dashboard-surface-focus" x-data="{ state: 'success' }" data-state="success">
    <header class="dashboard-section-head dashboard-kitchen-head">
        <div class="flex min-w-0 items-start gap-4">
            <svg class="dashboard-icon-title mt-1 text-bakery-brown" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 9h18M5 9V7a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v2M7 9v8a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V9M9 13h6"/>
            </svg>
            <div class="min-w-0">
                <h2 class="dashboard-h2">Cola de trabajo</h2>
                <p class="dashboard-body mt-1">
                    {!! $operacionLabel !!}. Revisa tiempos antes de prometer entregas.
                </p>
            </div>
        </div>
        <div class="dashboard-kitchen-summary" aria-label="Resumen de cola">
            <span><strong>{{ $pedidos }}</strong> pedidos</span>
            <span><strong>{{ $ordenes }}</strong> &oacute;rdenes</span>
            @if($pendientes > 0)
                <span class="is-warning"><strong>{{ $pendientes }}</strong> pendientes</span>
            @endif
        </div>
    </header>

    {{-- Skeleton state --}}
    <div class="dashboard-kitchen-list" x-show="state === 'loading'" x-cloak>
        @for($i = 0; $i < 4; $i++)
            <div class="dashboard-skeleton h-20 rounded-xl"></div>
        @endfor
    </div>

    <div x-show="state === 'error'" x-cloak class="dashboard-error-block" role="alert">
        Dato no disponible. <button type="button" class="font-bold text-bakery-brown underline-offset-4 hover:underline" x-on:click="$store.dashboard.retry()">Reintentar</button>
    </div>

    {{-- Success state --}}
    <div x-show="state === 'success'">
        @if(empty($orders))
            <div class="dashboard-empty dashboard-kitchen-empty">
                <p class="font-semibold text-bakery-dark">Cocina al d&iacute;a</p>
                <p>Buen momento para revisar mostrador o inventario.</p>
            </div>
        @else
            <div class="dashboard-kitchen-list">
                @foreach($visibleOrders as $pedido)
                    @php
                        $minutos = $pedido['minutos'];
                        $tipo = $pedido['tipo'] === 'orden' ? 'Orden' : 'Pedido';
                        $esUrgente = $minutos < 30;
                        $esAtencion = $minutos < 60 && !$esUrgente;
                    @endphp
                    <a href="{{ route('pedidos.show', $pedido['id']) }}" class="dashboard-kitchen-row {{ $esUrgente ? 'is-urgent' : ($esAtencion ? 'is-warning' : '') }}">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2.5">
                                <span class="dashboard-type-badge">{{ $tipo }}</span>
                                <p class="dashboard-kitchen-id">#{{ $pedido['id'] }}</p>
                                @if(!empty($pedido['estado']))
                                    <span class="dashboard-kitchen-state">{{ $pedido['estado'] }}</span>
                                @endif
                            </div>
                            <p class="dashboard-kitchen-person">{{ $pedido['usuario'] }}</p>
                        </div>

                        <div class="dashboard-time-pill">
                            @if($minutos < 60)
                                {{ $minutos }} min
                            @elseif($minutos < 1440)
                                {{ round($minutos / 60, 1) }} h
                            @else
                                {{ round($minutos / 1440, 1) }} d
                            @endif
                        </div>
                    </a>
                @endforeach
            </div>

            @if($total > 5)
                <a href="{{ route('pedidos.index') }}" class="dashboard-inline-link">Ver cola completa</a>
            @endif
        @endif
    </div>
</section>
