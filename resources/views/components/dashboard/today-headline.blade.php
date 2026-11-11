@props([
    'ventasHoy' => 0,
    'ventasAyer' => 0,
    'pedidosPendientes' => 0,
    'cocinaPedidosCount' => 0,
    'cocinaOrdenesCount' => 0,
    'stockCritico' => [],
    'stockNegativo' => 0,
])

@php
    $ventasHoyCount = (int) $ventasHoy;
    $ventasAyerCount = is_array($ventasAyer) ? (int) ($ventasAyer['count'] ?? 0) : (int) $ventasAyer;

    $stockCriticoCount = is_array($stockCritico) ? count($stockCritico) : (int) $stockCritico;
    $stockNegativoCount = (int) $stockNegativo;
    $ingredientesCriticos = $stockCriticoCount + $stockNegativoCount;

    $cocinaTotal = (int) $cocinaPedidosCount + (int) $cocinaOrdenesCount;
    $pendientesCount = (int) $pedidosPendientes;

    if ($pendientesCount > 0 || $ingredientesCriticos > 0) {
        $calidadText = 'en alerta';
        $colorClass = 'text-bakery-terracotta/85';
    } elseif ($ventasHoyCount === 0 && $cocinaTotal === 0) {
        $calidadText = 'lista para abrir';
        $colorClass = 'text-bakery-brown';
    } elseif ($cocinaTotal > 5 && $ventasHoyCount > $ventasAyerCount) {
        $calidadText = 'intensa';
        $colorClass = 'text-bakery-amber';
    } else {
        $calidadText = 'en calma';
        $colorClass = 'text-bakery-sage';
    }

    $segments = [];
    if ($cocinaTotal > 0) {
        $segments[] = $cocinaTotal . ' pedidos en cocina';
    }
    if ($pendientesCount > 0) {
        $segments[] = $pendientesCount . ' pendientes';
    }
    if ($ingredientesCriticos > 0) {
        $segments[] = $ingredientesCriticos . ' ingredientes cr&iacute;ticos';
    }
    $subtext = implode(' &middot; ', $segments);

    if ($ventasHoyCount === 0) {
        if ($pendientesCount === 0 && $ingredientesCriticos === 0 && $cocinaTotal === 0) {
            $insight = 'Caja a&uacute;n sin movimiento. Cocina e inventario en orden.';
        } else {
            $attention = [];
            if ($pendientesCount > 0) {
                $attention[] = $pendientesCount . ' pendiente' . ($pendientesCount === 1 ? '' : 's') . ' en cocina';
            }
            if ($ingredientesCriticos > 0) {
                $attention[] = $ingredientesCriticos . ' ingrediente' . ($ingredientesCriticos === 1 ? '' : 's') . ' en stock bajo';
            }
            $insight = 'A&uacute;n sin ventas hoy. Revisar ' . implode(' y ', $attention) . '.';
        }
    } elseif ($ventasAyerCount === 0) {
        $insight = 'Sin ventas ayer para comparar';
    } else {
        $pct = (int) round((($ventasHoyCount - $ventasAyerCount) / $ventasAyerCount) * 100);
        $insight = $pct > 0
            ? 'Hoy +' . $pct . '% vs ayer'
            : ($pct < 0 ? 'Hoy ' . $pct . '% vs ayer' : 'Hoy igual vs ayer');
    }
@endphp

<section class="dashboard-headline" aria-label="Estado de la panaderia hoy" data-state="success">
    <h2 class="dashboard-h2">
        Hoy, la operaci&oacute;n est&aacute; <span class="{{ $colorClass }}">{{ $calidadText }}</span>
    </h2>
    @if(!empty($subtext))
        <p class="dashboard-body mt-1">{!! $subtext !!}</p>
    @endif
    <p class="dashboard-caption mt-1">{!! $insight !!}</p>
</section>
