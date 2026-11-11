<x-app-layout>
<div class="py-8 px-6 max-w-2xl mx-auto space-y-6">

    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            {{-- Accessible label for screen readers --}}
            <a href="{{ route('ventas.index') }}" aria-label="Volver a ventas" class="text-bakery-muted/50 hover:text-bakery-muted">←</a>
            <h2 class="text-2xl font-bold text-bakery-dark">Venta #{{ $venta->id }}</h2>
            @if($cliente = $venta->clienteAsociado())
                <p class="mt-1 break-words text-sm text-bakery-muted"><span class="font-semibold">Cliente</span> · {{ $cliente->nombre }}</p>
            @endif
        </div>

        <div class="flex gap-2">
            <a href="{{ route('ventas.pdf', $venta) }}"
            class="bg-bakery-warm hover:bg-bakery-amber/20 text-bakery-dark text-sm font-medium px-4 py-2 rounded-xl transition">
                Descargar PDF
            </a>
            @if($venta->estado instanceof \App\Enums\VentaEstado && $venta->estado === \App\Enums\VentaEstado::Pagada)
            <div x-data="modalFocusTrap()">
                <button type="button" x-ref="trigger" @click="open = true"
                    class="bg-bakery-terracotta/10 hover:bg-bakery-terracotta/20 text-bakery-terracotta text-sm font-medium px-4 py-2 rounded-xl transition">
                    Cancelar
                </button>
                <div x-show="open" x-cloak
                     aria-modal="true"
                     role="dialog" aria-label="Cancelar venta"
                     class="fixed inset-0 z-50 flex items-center justify-center bg-bakery-dark/50"
                     @click.self="open = false"
                     @keydown.escape.window="open = false"
                     @keydown.tab.window="if (open) window.rochaTrapTab($event, $refs.modalContent)"
                     x-on:open-modal.window="open = true">
                    <div class="bg-white rounded-2xl shadow-xl p-6 max-w-sm w-full mx-4"
                         x-ref="modalContent">
                        <h3 class="text-lg font-display font-bold text-bakery-dark mb-2">Cancelar Venta #{{ $venta->id }}</h3>
                        <p class="text-sm text-bakery-muted mb-4">
                            La venta quedará sin efecto y el stock de productos volverá a estar disponible.
                        </p>
                        <form action="{{ route('ventas.cancelar', $venta) }}" method="POST">
                            @csrf @method('PATCH')
                            <div class="flex gap-2 justify-end">
                                <button type="button" x-ref="dismiss" @click="open = false" class="btn-secondary text-sm">Seguir</button>
                                <button type="submit" class="bg-bakery-terracotta hover:bg-bakery-terracotta/80 text-white text-sm font-medium px-4 py-2 rounded-xl transition">
                                    Confirmar cancelación
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            @endif

        </div>
    </div>

    @if(session('success'))
        <div class="flash-success text-sm rounded-xl px-4 py-3">
            {{ session('success') }}
        </div>
    @endif

    {{-- Info --}}
    <div class="bg-white rounded-2xl shadow-sm border border-bakery-amber/10 p-6 text-sm space-y-2">
        <div class="flex justify-between text-bakery-muted">
            <span>Cajero</span>
            <span class="font-medium text-bakery-dark">{{ $venta->usuario->name }}</span>
        </div>

        <div class="flex justify-between text-bakery-muted">
            <span>Fecha</span>
            <span class="font-medium text-bakery-dark">{{ $venta->created_at->format('d/m/Y H:i') }}</span>
        </div>

        <div class="flex justify-between text-bakery-muted">
            <span>Estado</span>
            <span class="font-medium {{ $venta->estado instanceof \App\Enums\VentaEstado && $venta->estado === \App\Enums\VentaEstado::Pagada ? 'text-bakery-sage' : 'text-bakery-terracotta' }}">
                {{ $venta->estado->label() }}
            </span>
        </div>

        @if($venta->pedido)
        <div class="flex justify-between text-bakery-muted">
            <span>Pedido</span>
            <a href="{{ route('pedidos.show', $venta->pedido) }}"
            class="text-bakery-gold hover:underline font-medium">
                #{{ $venta->pedido->id }}
            </a>
        </div>
        @endif
    </div>

    {{-- Detalles --}}
    <div class="bg-white rounded-2xl shadow-sm border border-bakery-amber/10 p-6">
        <h3 class="text-sm font-semibold text-bakery-muted uppercase tracking-wide mb-4">Productos</h3>
        <div class="space-y-3">
            @foreach($venta->detalles as $det)
            <div class="flex items-center justify-between text-sm border-b border-bakery-amber/5 pb-3 last:border-0">
                <div>
                    <p class="font-medium text-bakery-dark">
                        {{ $det->presentacion->producto->nombre ?? 'Producto eliminado' }}
                    </p>
                    <p class="text-xs text-bakery-muted/50">
                        {{ $det->presentacion->nombre ?? '—' }}
                        · {{ \App\Support\DisplayNumber::money($det->precio_unitario) }} c/u
                        × {{ \App\Support\DisplayNumber::quantity((float) $det->cantidad) }}
                    </p>
                    @if($det->descuento > 0)
                        <p class="text-xs text-bakery-terracotta/60">Descuento: -{{ \App\Support\DisplayNumber::money($det->descuento) }}</p>
                    @endif
                </div>
                <p class="font-semibold text-bakery-dark">{{ \App\Support\DisplayNumber::money($det->subtotal) }}</p>
            </div>
            @endforeach
        </div>
        <div class="flex justify-between items-center pt-4 border-t border-bakery-amber/10 mt-2">
            <span class="text-sm font-semibold text-bakery-muted">Total</span>
            <span class="text-xl font-bold text-bakery-dark">{{ \App\Support\DisplayNumber::money($venta->total) }}</span>
        </div>
    </div>

    {{-- Pagos --}}
    <div class="bg-white rounded-2xl shadow-sm border border-bakery-amber/10 p-6">
        <h3 class="text-sm font-semibold text-bakery-muted uppercase tracking-wide mb-4">Pagos</h3>
        <div class="space-y-2">
            @foreach($venta->pagos as $pago)
            <div class="flex items-center justify-between text-sm">
                <div>
                    <span class="font-medium text-bakery-dark capitalize">{{ $pago->metodo }}</span>
                    @if($pago->referencia)
                        <span class="text-xs text-bakery-muted/50 ml-2">Ref: {{ $pago->referencia }}</span>
                    @endif
                </div>
                <span class="font-semibold text-bakery-sage">{{ \App\Support\DisplayNumber::money($pago->monto) }}</span>
            </div>
            @endforeach
        </div>

        @php $totalPagado = $venta->pagos->sum('monto'); @endphp
        @if($totalPagado > $venta->total)
        <div class="flex justify-between items-center pt-3 border-t border-bakery-amber/10 mt-3 text-sm">
            <span class="text-bakery-muted">Cambio</span>
            <span class="font-semibold text-bakery-sage">
                {{ \App\Support\DisplayNumber::money($totalPagado - $venta->total) }}
            </span>
        </div>
        @endif
    </div>

    @if($venta->estado instanceof \App\Enums\VentaEstado && $venta->estado === \App\Enums\VentaEstado::Cancelada)
    <div class="bg-bakery-terracotta/10 rounded-2xl border border-bakery-terracotta/20 p-6">
        <div class="flex items-start gap-2">
            <svg class="w-5 h-5 text-bakery-terracotta mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
            </svg>
            <div>
                <p class="text-sm font-semibold text-bakery-terracotta">Esta venta fue cancelada</p>
                <p class="text-xs text-bakery-terracotta/80 mt-1">
                    Los registros de control de inventario asociados fueron revertidos.
                    <a href="{{ route('control-inventario.index', ['incluir_revertidos' => 1]) }}" class="underline">Ver en historial</a>
                </p>
            </div>
        </div>
    </div>
    @endif

</div>
</x-app-layout>

<script>
function modalFocusTrap() {
    return {
        open: false,
        init() {
            this.$watch('open', (open) => this.$nextTick(() => {
                (open ? this.$refs.dismiss : this.$refs.trigger)?.focus();
            }));
        },
    };
}
</script>

