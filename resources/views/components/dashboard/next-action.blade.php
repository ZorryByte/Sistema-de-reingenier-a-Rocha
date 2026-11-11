@props([
    'ventasHoy' => 0,
    'pedidosPendientes' => 0,
    'cocinaPedidosCount' => 0,
    'cocinaOrdenesCount' => 0,
    'stockCritico' => [],
    'stockNegativo' => 0,
])

@php
    $isAdmin = auth()->user()?->esAdmin() ?? false;
    $stockAlertas = count($stockCritico) + ((int) $stockNegativo > 0 ? 1 : 0);
    $cocinaTotal = (int) $cocinaPedidosCount + (int) $cocinaOrdenesCount + (int) $pedidosPendientes;

    if ($stockAlertas > 0 && $isAdmin) {
        $eyebrow = 'Prioridad';
        $title = 'Revisar inventario';
        $body = $stockAlertas . ' ingrediente' . ($stockAlertas === 1 ? '' : 's') . ' necesita atenci&oacute;n antes de vender.';
        $href = route('control-inventario.index');
        $label = 'Abrir inventario';
        $tone = 'warning';
    } elseif ($stockAlertas > 0) {
        $eyebrow = 'Prioridad';
        $title = 'Avisar a administraci&oacute;n';
        $body = $stockAlertas . ' ingrediente' . ($stockAlertas === 1 ? '' : 's') . ' aparece con stock bajo. Avisa al responsable.';
        $href = null;
        $label = 'Requiere admin';
        $tone = 'warning';
    } elseif ($cocinaTotal > 0) {
        $eyebrow = 'Ahora';
        $title = 'Ordenar la cola';
        $body = 'Hay trabajo activo en cocina. Revisa tiempos y entregas.';
        $href = route('pedidos.index');
        $label = 'Ver pedidos';
        $tone = 'active';
    } elseif ((int) $ventasHoy === 0) {
        $eyebrow = 'Inicio';
        $title = 'Registrar primera venta';
        $body = 'Caja a&uacute;n sin movimiento. Registra la primera venta.';
        $href = route('ventas.create');
        $label = 'Nueva venta';
        $tone = 'active';
    } else {
        $eyebrow = 'Estable';
        $title = 'Mantener ritmo';
        $body = 'Operaci&oacute;n estable. Mant&eacute;n ventas y cocina al d&iacute;a.';
        $href = route('ventas.create');
        $label = 'Nueva venta';
        $tone = 'calm';
    }
@endphp

<section class="dashboard-surface dashboard-next-action h-full" aria-label="Siguiente accion operativa" data-tone="{{ $tone }}" x-data="{ state: 'success' }" data-state="success">
    <div class="dashboard-section-head">
        <div>
            <p class="dashboard-caption">{{ $eyebrow }}</p>
            <h2 class="dashboard-h2">Siguiente acci&oacute;n</h2>
        </div>
        <span class="dashboard-action-badge" aria-hidden="true"></span>
    </div>

    <div class="mt-8">
        <h3 class="dashboard-h3">{!! $title !!}</h3>
        <p class="dashboard-body mt-3">{!! $body !!}</p>
    </div>

    <div class="mt-8">
        @if($href)
            <a href="{{ $href }}" class="dashboard-action-button">
                {{ $label }}
                <svg class="dashboard-icon-action" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
        @else
            <span class="dashboard-action-muted">{{ $label }}</span>
        @endif
    </div>
</section>
