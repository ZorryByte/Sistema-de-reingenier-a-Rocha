<x-app-layout>
<div class="py-8 px-6 max-w-4xl mx-auto">

    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('control-inventario.index') }}"
           class="text-bakery-muted/50 hover:text-bakery-muted transition p-1 rounded-lg hover:bg-bakery-warm">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
            </svg>
        </a>
        <div>
            <h2 class="text-2xl font-display font-bold text-bakery-dark">Movimiento #{{ $movimiento->id }}</h2>
            <p class="text-sm text-bakery-muted mt-0.5">{{ $movimiento->fecha->format('d/m/Y H:i') }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <div class="lg:col-span-2 space-y-6">

            <div class="form-card">
                <h3 class="section-title">Detalle del movimiento</h3>

                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <p class="text-bakery-muted text-xs">Tipo</p>
                        <span class="text-xs font-medium px-2.5 py-1 rounded-full border {{ $movimiento->getTipoColor() }}">
                            {{ $movimiento->getTipoLabel() }}
                        </span>
                    </div>
                    <div>
                        <p class="text-bakery-muted text-xs">Origen</p>
                        <p class="font-medium text-bakery-dark">{{ $movimiento->getOrigenLabel() }}</p>
                    </div>
                    <div>
                        <p class="text-bakery-muted text-xs">Ingrediente</p>
                        <p class="font-medium text-bakery-dark">{{ $movimiento->ingrediente->nombre ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-bakery-muted text-xs">Cantidad</p>
                        <p class="font-semibold {{ in_array($movimiento->tipo, ['salida','merma','venta']) ? 'text-bakery-terracotta' : 'text-bakery-sage' }}">
                            {{ in_array($movimiento->tipo, ['salida','merma','venta']) ? '-' : '+' }}{{ \App\Support\DisplayNumber::quantity((float) $movimiento->cantidad) }}
                        </p>
                    </div>
                    @if($movimiento->referencia_type && $movimiento->referencia_id)
                    <div>
                        <p class="text-bakery-muted text-xs">Referencia</p>
                        <p class="font-medium text-bakery-dark">{{ $movimiento->getReferenciaLabel() }}</p>
                    </div>
                    @endif
                    <div>
                        <p class="text-bakery-muted text-xs">Stock anterior</p>
                        <p class="font-medium text-bakery-dark">{{ \App\Support\DisplayNumber::quantity((float) $movimiento->stock_anterior) }}</p>
                    </div>
                    <div>
                        <p class="text-bakery-muted text-xs">Stock resultante</p>
                        <p class="font-medium text-bakery-dark">{{ \App\Support\DisplayNumber::quantity((float) $movimiento->stock_resultante) }}</p>
                    </div>
                    @if($movimiento->costo_anterior !== null && $movimiento->costo_resultante !== null)
                    <div>
                        <p class="text-bakery-muted text-xs">Costo anterior</p>
                        <p class="font-medium text-bakery-dark">{{ \App\Support\DisplayNumber::unitCost((float) $movimiento->costo_anterior) }}</p>
                    </div>
                    <div>
                        <p class="text-bakery-muted text-xs">Costo resultante</p>
                        <p class="font-medium {{ (float) $movimiento->costo_anterior === (float) $movimiento->costo_resultante ? 'text-bakery-sage' : 'text-bakery-brown' }}">
                            {{ \App\Support\DisplayNumber::unitCost((float) $movimiento->costo_resultante) }}
                        </p>
                    </div>
                    @endif
                    @if($movimiento->categoria)
                    <div>
                        <p class="text-bakery-muted text-xs">Categor&iacute;a</p>
                        <p class="font-medium text-bakery-dark">{{ ucfirst($movimiento->categoria) }}</p>
                    </div>
                    @endif
                    <div>
                        <p class="text-bakery-muted text-xs">Usuario</p>
                        <p class="font-medium text-bakery-dark">{{ $movimiento->usuario->name ?? '-' }}</p>
                    </div>
                </div>

                @if($movimiento->notas)
                <div class="mt-4 pt-4 border-t border-bakery-amber/10">
                    <p class="text-bakery-muted text-xs">Notas</p>
                    <p class="text-sm text-bakery-dark mt-1">{{ $movimiento->notas }}</p>
                </div>
                @endif
            </div>

            @if($movimiento->venta_id || $movimiento->pedido_id)
            <div class="form-card">
                <h3 class="section-title">Entidades relacionadas</h3>
                <div class="flex gap-4 text-sm">
                    @if($movimiento->venta_id)
                    <a href="{{ route('ventas.show', $movimiento->venta_id) }}" class="text-bakery-gold hover:underline">
                        Venta #{{ $movimiento->venta_id }}
                    </a>
                    @endif
                    @if($movimiento->pedido_id)
                    <a href="{{ route('pedidos.show', $movimiento->pedido_id) }}" class="text-bakery-gold hover:underline">
                        Pedido #{{ $movimiento->pedido_id }}
                    </a>
                    @endif
                </div>
            </div>
            @endif

            @if($movimiento->reverted_at)
            <div class="form-card bg-bakery-terracotta/10 border-bakery-terracotta/20">
                <div class="flex items-start gap-2">
                    <svg class="w-4 h-4 text-bakery-terracotta mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                    <div>
                        <p class="text-xs font-medium text-bakery-terracotta">Movimiento revertido</p>
                        <p class="text-xs text-bakery-terracotta/80 mt-1">
                            Revertido el {{ $movimiento->reverted_at->format('d/m/Y H:i') }}
                            por {{ $movimiento->usuarioReverso->name ?? 'desconocido' }}
                        </p>
                    </div>
                </div>
            </div>
            @endif
        </div>

        <div class="space-y-6">
            @if($movimiento->esReversible())
            <div class="form-card">
                <h3 class="section-title">Acciones</h3>
                <div x-data="modalFocusTrap()">
                    <button type="button" x-ref="trigger" @click="open = true" class="btn-secondary w-full text-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
                        </svg>
                        Revertir movimiento
                    </button>

                    <div x-show="open" x-cloak
                         aria-modal="true"
                         role="dialog" aria-label="Revertir movimiento"
                         class="fixed inset-0 z-50 flex items-center justify-center bg-bakery-dark/50"
                         @click.self="open = false"
                         @keydown.escape.window="open = false"
                         @keydown.tab.window="if (open) window.rochaTrapTab($event, $refs.modalContent)">
                        <div class="bg-white rounded-2xl shadow-xl p-6 max-w-sm w-full mx-4"
                             x-ref="modalContent">
                            <h3 class="text-lg font-display font-bold text-bakery-dark mb-2">Revertir movimiento</h3>
                            <p class="text-sm text-bakery-muted mb-4">
                                Se marcar&aacute; este movimiento como revertido.
                            </p>
                            <form action="{{ route('control-inventario.revertir', $movimiento) }}" method="POST">
                                @csrf
                                <label class="flex items-center gap-2 text-sm text-bakery-dark mb-4 cursor-pointer">
                                    <input type="checkbox" name="restaurar_stock" value="1" checked class="rounded border-bakery-amber/30">
                                    Restaurar stock al ingrediente
                                </label>
                                <div class="flex gap-2 justify-end">
                                    <button type="button" x-ref="dismiss" @click="open = false" class="btn-secondary text-sm">Cancelar</button>
                                    <button type="submit" class="btn-danger text-sm">Confirmar</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <div class="form-card">
                <h3 class="section-title">&Uacute;ltimos registros del ingrediente</h3>
                @forelse($historial as $h)
                <div class="flex items-center justify-between py-2 {{ !$loop->last ? 'border-b border-bakery-amber/5' : '' }}">
                    <div>
                        <span class="text-xs font-medium px-2 py-0.5 rounded-full border {{ $h->getTipoColor() }}">
                            {{ $h->getTipoLabel() }}
                        </span>
                        <span class="text-xs text-bakery-muted ml-1">{{ $h->fecha->format('d/m') }}</span>
                    </div>
                    <span class="text-xs font-semibold {{ in_array($h->tipo, ['salida','merma','venta']) ? 'text-bakery-terracotta' : 'text-bakery-sage' }}">
                        {{ in_array($h->tipo, ['salida','merma','venta']) ? '-' : '+' }}{{ \App\Support\DisplayNumber::quantity((float) $h->cantidad) }}
                    </span>
                </div>
                @empty
                <p class="text-xs text-bakery-muted/50">Sin historial adicional.</p>
                @endforelse

                @if($movimiento->ingrediente_id)
                <a href="{{ route('ingredientes.historial', $movimiento->ingrediente_id) }}" class="block mt-3 text-xs text-bakery-gold hover:underline text-center">
                    Ver historial completo
                </a>
                @endif
            </div>
        </div>
    </div>
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

