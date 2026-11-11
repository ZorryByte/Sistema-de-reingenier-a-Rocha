<x-app-layout>
@php
    $totalPedido = $pedido->detalles->sum(fn ($detalle) => $detalle->subtotalCents()) / 100;
    $erroresEntrega = $errors->getBag('default');
@endphp
<div class="py-8 px-6 max-w-5xl mx-auto" x-data="pedidoShow()">

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-6">
        <div class="flex items-center gap-3">
            {{-- Accessible label for screen readers --}}
            <a href="{{ route('pedidos.index') }}"
                aria-label="Volver a pedidos"
               class="text-bakery-muted/50 hover:text-bakery-muted transition p-1 rounded-lg hover:bg-bakery-warm">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <div>
                <h2 class="text-2xl font-display font-bold text-bakery-dark">
                    {{ $pedido->esOrden() ? 'Orden' : 'Pedido' }} #{{ $pedido->id }}
                </h2>
                <p class="text-sm text-bakery-muted mt-0.5">
                    {{ $pedido->usuario->name }} &middot; Creado {{ $pedido->fecha->translatedFormat('d M Y, H:i') }}
                </p>
            </div>
        </div>
        @php
            $coloresEstado = [
                'En Preparacion' => 'badge-warning',
                'Orden Lista' => 'badge-neutral',
                'Entregado' => 'badge-success',
                'Cancelado' => 'badge-danger',
            ];
        @endphp
        <div class="flex items-center gap-3">
            @if($pedido->estado === App\Enums\PedidoEstado::Entregado)
            <a href="{{ route('pedidos.pdf', $pedido) }}"
               class="bg-bakery-warm hover:bg-bakery-amber/20 text-bakery-dark text-sm font-medium px-4 py-2 rounded-xl transition flex items-center gap-2 whitespace-nowrap">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v6m3-3H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Descargar PDF
            </a>
            @endif
            <span class="{{ $coloresEstado[$pedido->estado->label()] ?? 'badge-neutral' }}">
                {{ $pedido->estado->label() }}
            </span>
        </div>
    </div>

    {{-- Flash messages --}}
    @if(session('success'))
        <div class="mb-4 flash-success text-sm rounded-xl px-4 py-3">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="mb-4 bg-bakery-terracotta/10 border border-bakery-terracotta/20 text-bakery-terracotta text-sm rounded-xl px-4 py-3">
            {{ session('error') }}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Columna principal --}}
        <div class="lg:col-span-2 space-y-6">

            {{-- Fecha de entrega HERO --}}
            @if($pedido->fecha_entrega_estimada)
                @php
                    $diffHours = now()->diffInHours($pedido->fecha_entrega_estimada, false);
                    $diffDays = now()->diffInDays($pedido->fecha_entrega_estimada, false);
                    $isPast = $diffHours < 0;
                    $isUrgent = !$isPast && $diffHours < 24;
                    $isSoon = !$isPast && $diffHours < 48;
                @endphp
                <div class="form-card {{ $isPast ? 'bg-bakery-terracotta/10' : ($isUrgent ? 'bg-bakery-amber/10' : ($isSoon ? 'bg-bakery-amber/5' : 'bg-white')) }}"
                     x-data="{
                         deadline: new Date('{{ $pedido->fecha_entrega_estimada->format('Y-m-d\TH:i:s') }}').getTime(),
                         countdownText: '',
                         countdownClass: '',
                         updateCountdown() {
                             const now = Date.now();
                             const diff = this.deadline - now;
                             if (diff <= 0) {
                                 this.countdownText = 'Vencido';
                                 this.countdownClass = 'text-bakery-terracotta';
                             } else {
                                 const days = Math.floor(diff / (1000 * 60 * 60 * 24));
                                 const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                                 const mins = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
                                 if (days > 0) {
                                     this.countdownText = days + 'd ' + hours + 'h ' + mins + 'm';
                                 } else {
                                     this.countdownText = hours + 'h ' + mins + 'm';
                                 }
                                 this.countdownClass = days === 0 ? 'text-bakery-amber' : 'text-bakery-sage';
                             }
                         }
                     }"
                     x-init="updateCountdown(); setInterval(updateCountdown, 60000)">

                    <div class="flex items-center gap-4">
                        {{-- Icono calendario --}}
                        <div class="flex-shrink-0 w-14 h-14 rounded-2xl {{ $isPast ? 'bg-bakery-terracotta/10' : ($isUrgent ? 'bg-bakery-amber/10' : 'bg-bakery-sage/10') }} flex items-center justify-center">
                            <svg class="w-7 h-7 {{ $isPast ? 'text-bakery-terracotta' : ($isUrgent ? 'text-bakery-amber' : 'text-bakery-sage') }}" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/>
                            </svg>
                        </div>

                        <div class="flex-1 min-w-0">
                            <p class="text-[11px] font-semibold text-bakery-muted uppercase tracking-wider mb-1">Entrega estimada</p>
                            <p class="text-lg font-display font-bold text-bakery-dark leading-tight">
                                {{ $pedido->fecha_entrega_estimada->translatedFormat('l d \d\e M, Y') }}
                            </p>
                            <div class="flex items-center gap-1.5 mt-1">
                                <svg class="w-3.5 h-3.5 text-bakery-muted/50" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <p class="text-sm text-bakery-muted">
                                    {{ $pedido->fecha_entrega_estimada->format('H:i') }} hrs
                                </p>
                            </div>
                        </div>

                        {{-- Countdown --}}
                        <div class="text-right flex-shrink-0 pl-4 border-l border-bakery-amber/15">
                            <p class="text-[11px] text-bakery-muted/60 mb-1 uppercase tracking-wider font-medium">Tiempo restante</p>
                            <div class="flex items-center gap-2 justify-end">
                                <template x-if="countdownClass === 'text-bakery-terracotta'">
                                    <span class="relative flex h-2.5 w-2.5">
                                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-bakery-terracotta opacity-50"></span>
                                        <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-bakery-terracotta"></span>
                                    </span>
                                </template>
                                <template x-if="countdownClass === 'text-bakery-amber'">
                                    <span class="relative flex h-2.5 w-2.5">
                                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-bakery-amber opacity-40"></span>
                                        <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-bakery-amber"></span>
                                    </span>
                                </template>
                                <p class="text-xl font-display font-bold" :class="countdownClass" x-text="countdownText"></p>
                            </div>
                        </div>
                    </div>

                    @if($isPast)
                        <div class="mt-3 pt-3 border-t border-bakery-terracotta/20 flex items-center gap-2">
                            <svg class="w-4 h-4 text-bakery-terracotta flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                            </svg>
                            <p class="text-xs text-bakery-terracotta font-medium">
                                Este pedido est&aacute; vencido desde hace {{ abs($diffHours) }} horas.
                            </p>
                        </div>
                    @endif
                </div>
            @else
                <div class="form-card">
                    <div class="flex items-center gap-4">
                        <div class="flex-shrink-0 w-16 h-16 rounded-2xl bg-bakery-warm flex items-center justify-center">
                            <svg class="w-8 h-8 text-bakery-muted/50" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-bakery-muted">Sin fecha de entrega</p>
                            <p class="text-xs text-bakery-muted/50 mt-0.5">No se defini&oacute; una fecha estimada para este pedido</p>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Productos --}}
            <div class="form-card">
                <h3 class="section-title">Productos solicitados</h3>

                <div class="space-y-3">
                    @foreach($pedido->detalles as $det)
                    <div class="flex items-center justify-between py-3 px-4 rounded-xl bg-bakery-warm/70 border border-bakery-amber/20">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-bakery-amber/10 flex items-center justify-center flex-shrink-0">
                                <span class="text-sm font-bold text-bakery-brown">{{ \App\Support\DisplayNumber::quantity((float) $det->cantidad) }}</span>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-bakery-dark">
                                    {{ $det->presentacion->producto->nombre ?? 'Producto eliminado' }}
                                </p>
                                <p class="text-xs text-bakery-muted/50">
                                    {{ $det->presentacion->nombre ?? '—' }}
                                    &middot;
                                    {{ \App\Support\DisplayNumber::money($det->presentacion->precio ?? 0) }} c/u
                                </p>
                                @if($det->notas)
                                    <p class="text-xs text-bakery-muted/50 italic mt-0.5">&ldquo;{{ $det->notas }}&rdquo;</p>
                                @endif
                            </div>
                        </div>
                        <div class="text-right flex-shrink-0">
                            <p class="text-sm font-bold text-bakery-dark">
                                {{ \App\Support\DisplayNumber::money($det->subtotalCents() / 100) }}
                            </p>
                        </div>
                    </div>
                    @endforeach
                </div>

                {{-- Total estimado --}}
                <div class="total-line">
                    <span class="total-label">Total estimado</span>
                    <span class="total-value">
                        {{ \App\Support\DisplayNumber::money($totalPedido) }}
                    </span>
                </div>
            </div>

            {{-- Notas --}}
            @if($pedido->notas)
                <div class="form-card-compact">
                    <h3 class="section-title">Notas del pedido</h3>
                    <p class="text-sm text-bakery-muted whitespace-pre-line">{{ $pedido->notas }}</p>
                </div>
            @endif
        </div>

        {{-- Columna lateral --}}
        <div class="space-y-6">

            {{-- Acciones --}}
            @if($pedido->estado === App\Enums\PedidoEstado::EnPreparacion && !$pedido->venta)
                <div class="form-card">
                    <h3 class="section-title">Acciones</h3>
                    <div class="space-y-2">
                        @if($pedido->esOrden())
                            <form action="{{ route('pedidos.marcarLista', $pedido) }}" method="POST">
                                @csrf
                                <button type="submit"
                                        class="w-full btn-primary justify-center bg-bakery-gold hover:bg-bakery-brown">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                    </svg>
                                    Marcar como lista
                                </button>
                            </form>
                        @endif
                        <button @click="abrirModal('mostrarEntregar', $event.currentTarget)"
                                class="w-full btn-primary justify-center">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                            </svg>
                            Confirmar entrega
                        </button>
                        <button @click="abrirModal('mostrarCancelar', $event.currentTarget)"
                                class="w-full btn-secondary text-bakery-terracotta border-bakery-terracotta/20 hover:bg-bakery-terracotta/5 justify-center">
                            Cancelar
                        </button>
                    </div>
                </div>
            @endif

            {{-- Acciones para Orden Lista --}}
            @if($pedido->estado === App\Enums\PedidoEstado::OrdenLista && !$pedido->venta)
                <div class="form-card bg-bakery-gold/10 border-bakery-gold/20">
                    <h3 class="section-title text-bakery-brown">Orden lista</h3>
                    <p class="text-xs text-bakery-brown/70 mb-3">Esta orden est&aacute; lista para entregar.</p>
                    <div class="space-y-2">
                        <button @click="abrirModal('mostrarEntregar', $event.currentTarget)"
                                class="w-full btn-primary justify-center">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                            </svg>
                            Confirmar entrega
                        </button>
                        <button @click="abrirModal('mostrarCancelar', $event.currentTarget)"
                                class="w-full btn-secondary text-bakery-terracotta border-bakery-terracotta/20 hover:bg-bakery-terracotta/5 justify-center">
                            Cancelar
                        </button>
                    </div>
                </div>
            @endif

            {{-- Venta asociada --}}
            @if($pedido->venta)
                <div class="form-card bg-bakery-sage/10 border-bakery-sage/20">
                    <div class="flex items-center gap-2 mb-2">
                        <svg class="w-4 h-4 text-bakery-sage" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <h3 class="text-xs font-semibold text-bakery-sage uppercase tracking-wider">Entregado</h3>
                    </div>
                    <p class="text-sm text-bakery-sage">
                        Este pedido tiene una venta registrada.
                        <a href="{{ route('ventas.show', $pedido->venta) }}" class="font-semibold underline hover:no-underline">
                            Ver venta #{{ $pedido->venta->id }}
                        </a>
                    </p>
                </div>
            @endif

            {{-- Resumen --}}
            <div class="form-card">
                <h3 class="section-title">Resumen</h3>
                @if($pedido->cliente)
                    <p class="mb-3 break-words text-sm text-bakery-dark"><span class="font-semibold">Cliente</span> · {{ $pedido->cliente->nombre }}</p>
                @endif
                <div class="space-y-3 text-sm">
                    <div class="flex justify-between py-2 border-b border-bakery-amber/10">
                        <span class="text-bakery-muted">Creado por</span>
                        <span class="font-medium text-bakery-dark">{{ $pedido->usuario->name }}</span>
                    </div>
                    <div class="flex justify-between py-2 border-b border-bakery-amber/10">
                        <span class="text-bakery-muted">Fecha de creaci&oacute;n</span>
                        <div class="text-right">
                            <span class="font-medium text-bakery-dark block">{{ $pedido->fecha->format('d/m/Y') }}</span>
                            <span class="text-[11px] text-bakery-muted/50">{{ $pedido->fecha->format('H:i') }} hrs</span>
                        </div>
                    </div>
                    <div class="flex justify-between py-2 border-b border-bakery-amber/10">
                        <span class="text-bakery-muted">Productos</span>
                        <span class="font-medium text-bakery-dark">{{ $pedido->detalles->count() }}</span>
                    </div>
                    <div class="flex justify-between py-2">
                        <span class="text-bakery-muted">Estado</span>
                        <span class="font-medium text-bakery-dark">{{ $pedido->estado->label() }}</span>
                    </div>
                </div>
            </div>

            {{-- Timeline --}}
            <div class="form-card-compact">
                <h3 class="section-title">L&iacute;nea de tiempo</h3>
                <div class="space-y-3">
                    @forelse($pedido->historial as $evento)
                        <div class="flex items-start gap-3">
                            <div class="mt-1 h-6 w-6 flex-shrink-0 rounded-md bg-bakery-warm text-center text-xs font-bold leading-6 text-bakery-brown" aria-hidden="true">
                                {{ strtoupper(substr($evento->evento, 0, 1)) }}
                            </div>
                            <div class="min-w-0">
                                <p class="text-xs font-semibold text-bakery-dark">
                                    {{ match($evento->evento) {
                                        'creado' => $pedido->esOrden() ? 'Orden creada' : 'Pedido creado',
                                        'actualizado' => 'Productos actualizados',
                                        'orden_lista' => 'Orden lista',
                                        'entregado' => 'Entregado y vendido',
                                        'cancelado' => 'Registro cancelado',
                                        default => ucfirst(str_replace('_', ' ', $evento->evento)),
                                    } }}
                                </p>
                                <p class="text-xs text-bakery-muted">
                                    {{ $evento->usuario?->name ?? 'Sistema' }} · {{ $evento->created_at->format('d/m/Y H:i') }}
                                </p>
                            </div>
                        </div>
                    @empty
                        <p class="text-xs text-bakery-muted">Creado {{ $pedido->fecha->diffForHumans() }}</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Cancelar --}}
    <div x-show="mostrarCancelar" x-cloak
         aria-modal="true"
         role="dialog"
         class="fixed inset-0 bg-bakery-dark/50 backdrop-blur-sm flex items-center justify-center z-50 p-4"
         @click.self="mostrarCancelar = false"
         @keydown.escape.window="mostrarCancelar = false"
         @keydown.tab.window="if (mostrarCancelar) window.rochaTrapTab($event, $refs.modalCancelar)"
         aria-label="Cancelar pedido"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
        <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full overflow-hidden"
             x-ref="modalCancelar"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95 translate-y-4"
             x-transition:enter-end="opacity-100 scale-100 translate-y-0">
            <div class="bg-bakery-terracotta px-6 py-4">
                <div class="flex items-center gap-3">
                    <div class="bg-white/20 rounded-full p-2">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4.5c-.77-.833-2.694-.833-3.464 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-white">Cancelar pedido</h3>
                </div>
            </div>
            <div class="px-6 py-4">
                <p class="text-sm text-bakery-muted">
                    &iquest;Est&aacute;s seguro de cancelar el pedido #{{ $pedido->id }}? Esta acci&oacute;n no se puede deshacer.
                </p>
            </div>
            <div class="border-t border-bakery-amber/20 px-6 py-4 flex gap-3">
                <button type="button" x-ref="volverCancelar" @click="mostrarCancelar = false" class="flex-1 btn-secondary">Volver</button>
                <form action="{{ route('pedidos.cancelar', $pedido) }}" method="POST" class="flex-1">
                    @csrf @method('PATCH')
                    <button type="submit" class="w-full btn-danger">Confirmar cancelaci&oacute;n</button>
                </form>
            </div>
        </div>
    </div>

    {{-- Modal Entregar --}}
    <div x-show="mostrarEntregar" x-cloak
         aria-modal="true"
         role="dialog"
         class="fixed inset-0 bg-bakery-dark/50 backdrop-blur-sm flex items-center justify-center z-50 p-4"
         @click.self="mostrarEntregar = false"
         @keydown.escape.window="mostrarEntregar = false"
         @keydown.tab.window="if (mostrarEntregar) window.rochaTrapTab($event, $refs.modalEntregar)"
         aria-label="Entregar pedido"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
        <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full overflow-hidden"
             x-ref="modalEntregar"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95 translate-y-4"
             x-transition:enter-end="opacity-100 scale-100 translate-y-0">
            <div class="bg-bakery-sage px-6 py-4">
                <div class="flex items-center gap-3">
                    <div class="bg-white/20 rounded-full p-2">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-white">Confirmar entrega</h3>
                </div>
            </div>

            <div class="px-6 py-4 space-y-4 max-h-72 overflow-y-auto">
                {{-- Resumen items --}}
                <div class="space-y-1.5">
                    @foreach($pedido->detalles as $det)
                    <div class="flex justify-between text-sm py-1">
                        <span class="text-bakery-muted">
                            {{ \App\Support\DisplayNumber::quantity((float) $det->cantidad) }}&times; {{ $det->presentacion->producto->nombre ?? 'N/A' }}
                            ({{ $det->presentacion->nombre ?? '—' }})
                        </span>
                        <span class="font-medium text-bakery-dark">
                            {{ \App\Support\DisplayNumber::money($det->subtotalCents() / 100) }}
                        </span>
                    </div>
                    @endforeach
                </div>

                <div class="border-t border-bakery-amber/20 pt-3">
                    <div class="flex justify-between">
                        <span class="text-sm font-semibold text-bakery-dark">Total</span>
                        <span class="text-xl font-bold text-bakery-dark">
                            {{ \App\Support\DisplayNumber::money($totalPedido) }}
                        </span>
                    </div>
                </div>

                <div class="bg-bakery-amber/10 border border-bakery-amber/20 rounded-xl px-3 py-2">
                    <p class="text-xs text-bakery-brown">
                        Al confirmar se descontar&aacute; stock de las presentaciones y se generar&aacute; una venta.
                    </p>
                </div>

                <form action="{{ route('pedidos.entregar', $pedido) }}" method="POST" class="space-y-3" id="form-entregar" @submit="entregando = true">
                    @csrf
                    <div>
                        <label class="text-xs font-medium text-bakery-muted mb-1 block" for="entrega-metodo">M&eacute;todo de pago</label>
                        <select id="entrega-metodo" name="pagos[0][metodo]" class="form-select py-2 px-3 text-xs">
                            <option value="efectivo" @selected(old('pagos.0.metodo', 'efectivo') === 'efectivo')>Efectivo</option>
                            <option value="tarjeta" @selected(old('pagos.0.metodo', 'efectivo') === 'tarjeta')>Tarjeta</option>
                            <option value="transferencia" @selected(old('pagos.0.metodo', 'efectivo') === 'transferencia')>Transferencia</option>
                            <option value="otro" @selected(old('pagos.0.metodo', 'efectivo') === 'otro')>Otro</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-xs font-medium text-bakery-muted mb-1 block" for="entrega-monto">Monto $</label>
                        <input id="entrega-monto" type="number" name="pagos[0][monto]" step="0.01" min="0.01"
                               value="{{ old('pagos.0.monto', number_format($totalPedido, 2, '.', '')) }}"
                               @if($erroresEntrega->any()) aria-invalid="true" aria-describedby="entrega-error" @endif
                               class="form-input py-2 px-3 text-xs" required>
                    </div>
                    @if($erroresEntrega->any())
                        <ul id="entrega-error" role="alert" class="form-error">
                            @foreach($erroresEntrega->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    @endif
                    <div>
                        <label for="entrega-referencia" class="text-xs font-medium text-bakery-muted mb-1 block">
                            Referencia <span class="text-bakery-muted/50 font-normal">(opcional)</span>
                        </label>
                        <input id="entrega-referencia" type="text" name="pagos[0][referencia]" maxlength="100"
                               value="{{ old('pagos.0.referencia') }}"
                               class="form-input py-2 px-3 text-xs"
                               placeholder="N° autorizaci&oacute;n, etc.">
                    </div>
                </form>
            </div>

            <div class="border-t border-bakery-amber/20 px-6 py-4 flex gap-3">
                <button type="button" x-ref="volverEntregar" @click="mostrarEntregar = false" class="flex-1 btn-secondary">Cancelar</button>
                <button type="submit" form="form-entregar" :disabled="entregando"
                        class="flex-1 btn-primary justify-center">
                    Confirmar entrega
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function pedidoShow() {
    return {
        mostrarCancelar: false,
        mostrarEntregar: @js($erroresEntrega->any()),
        entregando: false,
        trigger: null,
        init() {
            if (this.mostrarEntregar) {
                this.$nextTick(() => this.$refs.modalEntregar.querySelector('#entrega-monto')?.focus());
            }
            for (const action of ['Cancelar', 'Entregar']) {
                this.$watch('mostrar' + action, (open) => this.$nextTick(() => {
                    (open ? this.$refs['volver' + action] : this.trigger)?.focus();
                }));
            }
        },
        abrirModal(property, trigger) {
            this.trigger = trigger;
            this[property] = true;
        },
    }
}
</script>

<style>[x-cloak] { display: none !important; }</style>
</x-app-layout>
