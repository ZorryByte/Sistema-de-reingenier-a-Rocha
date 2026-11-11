<x-app-layout>
<div class="py-8 px-6 max-w-4xl mx-auto" x-data="pedidoForm()" x-cloak>

    {{-- Loading state for double-submit prevention --}}
    <div x-show="submitting" x-transition.delay.200ms class="fixed inset-0 bg-bakery-dark/30 backdrop-blur-sm z-50 flex items-center justify-center">
        <div class="bg-white rounded-2xl p-6 shadow-2xl flex items-center gap-4">
            <svg class="animate-spin w-8 h-8 text-bakery-gold" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 0 12h4z"></path>
            </svg>
            <span class="text-bakery-dark font-semibold">Guardando pedido...</span>
        </div>
    </div>

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center gap-3 mb-6">
        <a href="{{ route('pedidos.index') }}"
           class="text-bakery-muted/50 hover:text-bakery-muted transition p-1 rounded-lg hover:bg-bakery-warm self-start"
           aria-label="Volver a pedidos">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
            </svg>
        </a>
        <div>
            <h2 class="text-2xl font-display font-bold text-bakery-dark" x-text="esOrden ? 'Nueva orden' : 'Nuevo pedido'"></h2>
            <p class="text-sm text-bakery-muted mt-0.5" x-text="esOrden ? 'Orden inmediata, sin fecha de entrega' : 'Define la fecha de entrega y los productos solicitados'"></p>
        </div>
    </div>

    {{-- Toggle Pedido / Orden --}}
    <div class="flex items-center gap-1 bg-bakery-warm rounded-xl p-1 mb-6 max-w-xs">
        <button type="button" @click="esOrden = false"
                :class="!esOrden ? 'bg-white text-bakery-dark shadow-sm' : 'text-bakery-muted hover:text-bakery-dark'"
                class="flex-1 py-2.5 px-4 text-sm font-semibold rounded-lg transition-all duration-150 text-center min-h-[44px]">
            Pedido
        </button>
        <button type="button" @click="esOrden = true"
                :class="esOrden ? 'bg-bakery-gold/20 text-bakery-brown shadow-sm' : 'text-bakery-muted hover:text-bakery-dark'"
                class="flex-1 py-2.5 px-4 text-sm font-semibold rounded-lg transition-all duration-150 text-center min-h-[44px]">
            Orden
        </button>
    </div>

    {{-- Required field legend --}}
    <p class="text-xs text-bakery-muted mb-4 italic">Los campos marcados con * son obligatorios</p>

    @if($errors->any())
        <div class="mb-4 bg-bakery-terracotta/10 border border-bakery-terracotta/20 text-bakery-terracotta text-sm rounded-xl px-4 py-3">
            <ul class="list-disc list-inside space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if(session('error'))
        <div class="mb-4 bg-bakery-terracotta/10 border border-bakery-terracotta/20 text-bakery-terracotta text-sm rounded-xl px-4 py-3 flex items-start gap-2">
            <svg class="w-5 h-5 flex-shrink-0 mt-0.5 text-bakery-terracotta" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
            </svg>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <form action="{{ route('pedidos.store') }}" method="POST" class="space-y-6" @submit="submitting = true">
        @csrf

        {{-- CAMBIO 1:
            Este input estaba fuera del <form>, por eso Laravel no recibía si era "pedido" u "orden".
            Ahora sí se envía correctamente.
        --}}
        <input type="hidden" name="tipo" :value="esOrden ? 'orden' : 'pedido'">

        <x-cliente-selector requerido="!esOrden" />

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            {{-- Columna principal --}}
            <div class="lg:col-span-2 space-y-6">

                {{-- Cat&aacute;logo r&aacute;pido --}}
                <div class="form-card">
                    <h3 class="section-title mb-3">Cat&aacute;logo</h3>

                    <div class="relative mb-3">
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-bakery-muted/50" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <input type="text" x-model="busqueda"
                               class="form-input pl-10 py-3 text-sm"
                               placeholder="Buscar producto o presentaci&oacute;n...">
                    </div>

                    <div class="flex flex-wrap gap-2 mb-4 overflow-x-auto pb-1">
                        <template x-for="filtro in filtrosRapidos" :key="filtro.label">
                            <button type="button"
                                    @click="setFilter(filtro.filtro)"
                                    :class="activeFilter === filtro.filtro
                                        ? 'bg-bakery-gold/15 border-bakery-gold/40 text-bakery-brown'
                                        : 'bg-white border-bakery-amber/20 text-bakery-muted hover:bg-bakery-warm hover:text-bakery-dark'"
                                    class="px-3 py-1.5 text-xs font-medium rounded-full border transition duration-150 whitespace-nowrap">
                                <span x-text="filtro.label"></span>
                            </button>
                        </template>
                    </div>

                    <template x-for="(presentaciones, productoNombre) in productosFiltrados" :key="productoNombre">
                        <div class="mb-2 last:mb-0">
                            <button type="button" @click="toggleProduct(productoNombre)"
                                    class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl
                                           hover:bg-bakery-warm transition duration-150 text-left group">
                                <div class="flex items-center gap-2 min-w-0">
                                    <svg class="w-4 h-4 text-bakery-muted/40 transition-transform duration-150 flex-shrink-0"
                                         :class="expandedProduct === productoNombre ? 'rotate-90' : ''"
                                         fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                                    </svg>
                                    <span class="text-sm font-semibold text-bakery-dark truncate" x-text="productoNombre"></span>
                                </div>
                                <span class="text-xs font-medium text-bakery-muted/50 bg-bakery-warm px-2 py-0.5 rounded-full ml-2 flex-shrink-0"
                                      x-text="presentaciones.length"></span>
                            </button>

                            <div x-show="expandedProduct === productoNombre"
                                 x-transition:enter="transition ease-out duration-200"
                                 x-transition:enter-start="opacity-0 -translate-y-1"
                                 x-transition:enter-end="opacity-100 translate-y-0"
                                 x-transition:leave="transition ease-in duration-150"
                                 x-transition:leave-start="opacity-100 translate-y-0"
                                 x-transition:leave-end="opacity-0 -translate-y-1"
                                 class="px-3 pb-3">
                                <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                                    <template x-for="(pres, idx) in presentaciones" :key="pres.id">
                                        <button type="button" @click="agregarDesdeCatalogo(pres)"
                                                :class="flashId === pres.id ? 'bg-bakery-sage/20 border-bakery-sage/40' : ''"
                                                class="text-left p-3 rounded-xl border bg-bakery-warm/50
                                                       transition duration-150 group relative
                                                       disabled:opacity-50 disabled:cursor-not-allowed"
                                                :style="`animation-delay: ${idx * 50}ms`"
                                                :disabled="pres.stock === 0"
                                                :title="pres.stock === 0 ? 'Sin stock disponible' : (pres.stock < 5 ? 'Stock bajo: ' + pres.stock + ' unds.' : 'Stock: ' + pres.stock + ' unds.')">
                                            <p class="text-sm font-medium text-bakery-dark truncate"
                                               x-text="pres.presentacion"></p>
                                            <p class="text-base font-bold mt-1"
                                               :class="pres.stock === 0 ? 'text-bakery-muted/50' : 'text-bakery-gold'"
                                               x-text="formatMoney(pres.precio)"></p>
                                            <div class="mt-1.5 flex items-center gap-1 flex-wrap">
                                                <template x-if="pres.stock === 0">
                                                    <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full text-xs font-medium bg-bakery-warm text-bakery-muted">
                                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 12H4"/></svg>
                                                        Agotado
                                                    </span>
                                                </template>
                                                <template x-if="pres.stock > 0 && pres.stock < 5">
                                                    <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-700">
                                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                                        <span x-text="pres.stock + ' uds'"></span>
                                                    </span>
                                                </template>
                                                <template x-if="pres.stock >= 5">
                                                    <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full text-xs font-medium bg-bakery-sage/20 text-bakery-sage">
                                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                                        <span x-text="pres.stock + ' uds'"></span>
                                                    </span>
                                                </template>
                                                <template x-if="!pres.tiene_receta">
                                                    <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full text-xs font-medium bg-bakery-amber/10 text-bakery-amber" title="Sin receta">
                                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                                        </svg>
                                                    </span>
                                                </template>
                                            </div>
                                        </button>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </template>

                    <template x-if="Object.keys(productosFiltrados).length === 0">
                        <div class="empty-state">
                            <svg class="empty-state-icon" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                            <p class="empty-state-text">No se encontraron productos</p>
                        </div>
                    </template>
                </div>

                {{-- Items del pedido --}}
                <div class="form-card">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="section-title mb-0">Productos</h3>
                            <p class="section-hint">Selecciona los productos y cantidades</p>
                        </div>
                        <button type="button" @click="agregarItem"
                                class="btn-ghost text-bakery-gold hover:text-bakery-brown hover:bg-bakery-gold/10"
                                aria-label="Agregar producto">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                            </svg>
                            Agregar
                        </button>
                    </div>

                    @error('items') <p class="form-error mb-3">{{ $message }}</p> @enderror

                    <div class="space-y-3">
                        <template x-for="(item, index) in items" :key="item.key">
                            <div class="data-row"
                                 x-transition:enter="fade-enter"
                                 x-transition:enter-start="opacity-0 scale-95"
                                 x-transition:enter-end="opacity-100 scale-100"
                                 x-transition:leave="fade-leave"
                                 x-transition:leave-start="opacity-100 scale-100"
                                 x-transition:leave-end="opacity-0 scale-95">

                                <button type="button" @click="eliminarItem(index)"
                                        x-show="items.length > 1"
                                        class="data-row-remove"
                                        aria-label="Eliminar producto">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>

                                <div class="grid grid-cols-1 sm:grid-cols-12 gap-3">
                                    <div class="sm:col-span-6">
                                        <label class="text-xs font-medium text-bakery-muted mb-1 block">
                                            Presentaci&oacute;n <span class="text-bakery-terracotta font-bold">*</span>
                                        </label>

                                        {{-- CAMBIO 2:
                                            Se agregó x-model="item.presentacion_id".
                                            Antes Alpine cambiaba item.presentacion_id, pero el <select> real podía seguir vacío.
                                            Con esto, lo seleccionado desde el catálogo sí se manda a Laravel.
                                        --}}
                                        <select x-model="item.presentacion_id"
                                                :name="`items[${index}][presentacion_id]`"
                                                class="form-select py-2 px-3 text-xs" required>
                                            <option value="">Seleccionar...</option>
                                            @foreach($presentaciones as $productoNombre => $preses)
                                                <optgroup label="{{ $productoNombre }}">
                                                    @foreach($preses as $p)
                                                        <option value="{{ $p->id }}">
                                                            {{ $p->nombre }} — {{ \App\Support\DisplayNumber::money($p->precio) }}
                                                        </option>
                                                    @endforeach
                                                </optgroup>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="sm:col-span-2">
                                        <label class="text-xs font-medium text-bakery-muted mb-1 block">
                                            Cantidad <span class="text-bakery-terracotta font-bold">*</span>
                                        </label>
                                        <div class="flex items-center gap-1">
                                            <button type="button" @click="if(item.cantidad > 1) { item.cantidad--; }"
                                                    class="w-7 h-7 flex items-center justify-center rounded-lg border border-bakery-amber/20 text-bakery-muted hover:bg-bakery-warm transition text-sm">
                                                &minus;
                                            </button>
                                            <input type="number" x-model.number="item.cantidad"
                                                   :name="`items[${index}][cantidad]`"
                                                   step="0.01" min="0.01"
                                                   class="form-input py-2 px-2 text-xs text-center w-full" required>
                                            <button type="button" @click="item.cantidad += 1"
                                                    class="w-7 h-7 flex items-center justify-center rounded-lg border border-bakery-amber/20 text-bakery-muted hover:bg-bakery-warm transition text-sm">
                                                +
                                            </button>
                                        </div>
                                        <template x-if="item.presentacion_id && getStockDisponible(item.presentacion_id) !== null && item.cantidad > getStockDisponible(item.presentacion_id)">
                                            <p class="mt-1 text-xs text-amber-700 flex items-center gap-1">
                                                <svg class="w-3 h-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                                </svg>
                                                <span x-text="'Stock disponible: ' + getStockDisponible(item.presentacion_id)"></span>
                                            </p>
                                        </template>
                                    </div>

                                    <div class="sm:col-span-4">
                                        <label class="text-xs font-medium text-bakery-muted mb-1 block">
                                            Notas <span class="text-bakery-muted/50">(opcional)</span>
                                        </label>
                                        <input type="text" :name="`items[${index}][notas]`"
                                               class="form-input py-2 px-3 text-xs"
                                               placeholder="Ej: sin gluten">
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            {{-- Columna lateral --}}
            <div class="space-y-6">

                {{-- Fecha de entrega (solo pedidos) --}}
                <div class="form-card" x-data="datePicker()" x-show="!esOrden" x-transition>
                    <div class="mb-3">
                        <h3 class="section-title mb-0 flex items-center gap-2">
                            <svg class="w-3.5 h-3.5 text-bakery-gold" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" clip-rule="evenodd"/>
                            </svg>
                            Fecha de entrega
                            <span class="text-bakery-terracotta text-base leading-none">*</span>
                        </h3>
                    </div>

                    <div class="space-y-3">
                        <div class="grid grid-cols-2 gap-2">
                            <button type="button" @click="setDate('hoy')"
                                    :class="activeShortcut === 'hoy' ? 'bg-bakery-gold/20 border-bakery-gold/50 text-bakery-brown shadow-sm' : 'bg-white border-bakery-amber/30 text-bakery-muted hover:bg-bakery-warm hover:border-bakery-amber/50 hover:text-bakery-dark'"
                                    class="py-2.5 px-3 text-xs font-semibold rounded-lg border transition-all duration-150 text-center">
                                <span>Hoy</span>
                                <span class="block text-[10px] font-normal opacity-60" x-text="todayLabel"></span>
                            </button>
                            <button type="button" @click="setDate('manana')"
                                    :class="activeShortcut === 'manana' ? 'bg-bakery-gold/20 border-bakery-gold/50 text-bakery-brown shadow-sm' : 'bg-white border-bakery-amber/30 text-bakery-muted hover:bg-bakery-warm hover:border-bakery-amber/50 hover:text-bakery-dark'"
                                    class="py-2.5 px-3 text-xs font-semibold rounded-lg border transition-all duration-150 text-center">
                                <span>Mañana</span>
                                <span class="block text-[10px] font-normal opacity-60" x-text="mananaLabel"></span>
                            </button>
                            <button type="button" @click="setDate('3dias')"
                                    :class="activeShortcut === '3dias' ? 'bg-bakery-gold/20 border-bakery-gold/50 text-bakery-brown shadow-sm' : 'bg-white border-bakery-amber/30 text-bakery-muted hover:bg-bakery-warm hover:border-bakery-amber/50 hover:text-bakery-dark'"
                                    class="py-2.5 px-3 text-xs font-semibold rounded-lg border transition-all duration-150 text-center">
                                <span>3 días</span>
                                <span class="block text-[10px] font-normal opacity-60" x-text="tresDiasLabel"></span>
                            </button>
                            <button type="button" @click="setDate('semana')"
                                    :class="activeShortcut === 'semana' ? 'bg-bakery-gold/20 border-bakery-gold/50 text-bakery-brown shadow-sm' : 'bg-white border-bakery-amber/30 text-bakery-muted hover:bg-bakery-warm hover:border-bakery-amber/50 hover:text-bakery-dark'"
                                    class="py-2.5 px-3 text-xs font-semibold rounded-lg border transition-all duration-150 text-center">
                                <span>1 semana</span>
                                <span class="block text-[10px] font-normal opacity-60" x-text="semanaLabel"></span>
                            </button>
                        </div>

                        <div class="relative">
                            <div class="absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none z-10">
                                <svg class="w-4 h-4 text-bakery-gold" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/>
                                </svg>
                            </div>

                            {{-- CAMBIO 3:
                                Pedido sí necesita fecha.
                                Orden NO necesita fecha porque es inmediata del mesero.
                                x-show solo oculta, no elimina el input del formulario.
                                Por eso se agrega:
                                - :required="!esOrden" para que solo sea obligatorio en pedidos.
                                - :disabled="esOrden" para que una orden no envíe fecha_entrega_estimada.
                            --}}
                            <input type="datetime-local" name="fecha_entrega_estimada" @error('fecha_entrega_estimada') aria-invalid="true" aria-describedby="pedido-fecha-error" @enderror x-model="fechaHora"
                                   :min="minDate"
                                   :required="!esOrden"
                                   :disabled="esOrden"
                                   class="form-input pl-10 pr-4 py-2.5 text-sm w-full transition-all duration-150"
                                   :class="fechaHora ? 'border-bakery-gold/60 focus:border-bakery-gold focus:ring-bakery-gold/30' : 'border-bakery-amber/40 focus:border-bakery-gold focus:ring-bakery-gold/30'">
                        </div>

                        @error('fecha_entrega_estimada')
                            <p id="pedido-fecha-error" class="form-error">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Info orden inmediata --}}
                <div class="form-card bg-bakery-gold/10 border-bakery-gold/20" x-show="esOrden" x-transition>
                    <div class="flex items-start gap-3">
                        <div class="flex-shrink-0 w-10 h-10 rounded-xl bg-bakery-gold/20 flex items-center justify-center">
                            <svg class="w-5 h-5 text-bakery-gold" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-semibold text-bakery-brown">Orden inmediata</h3>
                            <p class="text-xs text-bakery-brown/70 mt-0.5">
                                Las órdenes se preparan de inmediato. No requieren fecha de entrega.
                            </p>
                        </div>
                    </div>
                </div>

                {{-- Notas generales --}}
                <div class="form-card-compact">
                    <label class="text-xs font-medium text-bakery-muted mb-1 block">
                        Notas del pedido <span class="text-bakery-muted/50">(opcional)</span>
                    </label>
                    <textarea name="notas" rows="3"
                              class="form-textarea py-2 px-3 text-xs"
                              placeholder="Indicaciones especiales, direcci&oacute;n de entrega, etc.">{{ old('notas') }}</textarea>
                </div>

                {{-- Info --}}
                <div class="form-card-compact bg-bakery-amber/10 border-bakery-amber/20">
                    <div class="flex items-start gap-2">
                        <svg class="w-4 h-4 text-bakery-gold mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                        </svg>
                        <p class="text-xs text-bakery-brown">
                            Los pedidos en preparaci&oacute;n se pueden convertir en venta al momento de la entrega, descontando autom&aacute;ticamente el stock.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Footer sticky --}}
        <div class="sticky bottom-0 left-0 right-0 bg-bakery-cream/95 backdrop-blur-sm border-t border-bakery-amber/20 -mx-4 sm:-mx-6 px-4 sm:px-6 py-3 flex flex-col sm:flex-row gap-2 sm:justify-end">
            <a href="{{ route('pedidos.index') }}" class="btn-secondary w-full sm:w-auto text-center">Cancelar</a>
            <button type="submit" :disabled="submitting" class="btn-primary disabled:opacity-60 disabled:cursor-not-allowed w-full sm:w-auto">
                <svg x-show="!submitting" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                </svg>
                <svg x-show="submitting" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 0 12h4z"></path>
                </svg>
                <span x-text="submitting ? 'Guardando...' : (esOrden ? 'Crear orden' : 'Crear pedido')"></span>
            </button>
        </div>
    </form>
</div>

<script>
const catalogo = @json($catalogo);

function datePicker() {
    return {
        fechaHora: '',
        activeShortcut: 'hoy',
        minDate: '',

        init() {
            const now = new Date();
            now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
            this.minDate = now.toISOString().slice(0, 16);
            const previous = @js(old('fecha_entrega_estimada'));
            if (typeof previous === 'string' && previous) {
                this.fechaHora = previous;
                this.activeShortcut = '';
            } else {
                this.setDate('hoy');
            }
        },

        setDate(tipo) {
            const now = new Date();
            let target = new Date(now);

            switch (tipo) {
                case 'hoy': break;
                case 'manana': target.setDate(now.getDate() + 1); break;
                case '3dias': target.setDate(now.getDate() + 3); break;
                case 'semana': target.setDate(now.getDate() + 7); break;
            }

            target.setHours(12, 0, 0, 0);
            target.setMinutes(target.getMinutes() - target.getTimezoneOffset());
            this.fechaHora = target.toISOString().slice(0, 16);
            this.activeShortcut = tipo;
        },

        get todayLabel() {
            const d = new Date();
            return d.toLocaleDateString('es-ES', { day: 'numeric', month: 'short' });
        },

        get mananaLabel() {
            const d = new Date();
            d.setDate(d.getDate() + 1);
            return d.toLocaleDateString('es-ES', { day: 'numeric', month: 'short' });
        },

        get tresDiasLabel() {
            const d = new Date();
            d.setDate(d.getDate() + 3);
            return d.toLocaleDateString('es-ES', { day: 'numeric', month: 'short' });
        },

        get semanaLabel() {
            const d = new Date();
            d.setDate(d.getDate() + 7);
            return d.toLocaleDateString('es-ES', { day: 'numeric', month: 'short' });
        },
    }
}

function pedidoForm() {
    const previous = @js(old('items', []));
    const items = Object.values(previous ?? {}).filter(item => item && typeof item === 'object').map((item, key) => ({
        key, presentacion_id: item.presentacion_id ?? '', cantidad: item.cantidad === undefined ? 1 : Number(item.cantidad), notas: item.notas ?? '',
    }));
    let key = Math.max(1, items.length);

    const productosMap = {};
    catalogo.forEach(p => {
        if (!productosMap[p.producto]) productosMap[p.producto] = [];
        productosMap[p.producto].push(p);
    });

    return {
        submitting: false,
        esOrden: @js(old('tipo') === 'orden'),

        busqueda: '',
        expandedProduct: null,
        activeFilter: '',
        flashId: null,
        filtrosRapidos: [
            { label: 'Todos',     filtro: '' },
            { label: 'Panes',     filtro: 'pan' },
            { label: 'Pasteles',  filtro: 'pastel' },
            { label: 'Postres',   filtro: 'postre' },
            { label: 'Caf&eacute;s',     filtro: 'caf' },
            { label: 'Bebidas',   filtro: 'bebida' },
            { label: 'Galletas',  filtro: 'galleta' },
        ],
        productosMap: productosMap,

        items: items.length ? items : [{ key: 0, presentacion_id: '', cantidad: 1, notas: '' }],
        fecha: '',
        hora: '12:00',
        shortcutActive: '',

        toggleProduct(nombre) {
            this.expandedProduct = this.expandedProduct === nombre ? null : nombre;
        },

        setFilter(filtro) {
            this.activeFilter = filtro;
            this.busqueda = filtro;
        },

        get productosFiltrados() {
            let resultado = this.productosMap;
            const q = this.busqueda.toLowerCase().trim();
            if (q) {
                const filtrados = {};
                Object.entries(this.productosMap).forEach(([nombre, preses]) => {
                    const match = nombre.toLowerCase().includes(q) ||
                                  preses.some(p => p.presentacion.toLowerCase().includes(q));
                    if (match) filtrados[nombre] = preses;
                });
                resultado = filtrados;
            }
            return resultado;
        },

        agregarDesdeCatalogo(pres) {
            if (pres.stock === 0) return;

            const existente = this.items.find(i => i.presentacion_id == pres.id);
            if (existente) {
                existente.cantidad += 1;
            } else {
                const vacioIdx = this.items.findIndex(i => i.presentacion_id === '');
                if (vacioIdx !== -1) {
                    this.items[vacioIdx].presentacion_id = pres.id;
                    this.items[vacioIdx].cantidad = 1;
                    this.items[vacioIdx].notas = '';
                } else {
                    this.items.push({ key: key++, presentacion_id: pres.id, cantidad: 1, notas: '' });
                }
            }
            this.flashId = pres.id;
            setTimeout(() => { this.flashId = null; }, 300);
        },

        getStockDisponible(presentacionId) {
            const pres = catalogo.find(p => p.id == presentacionId);
            return pres ? pres.stock : null;
        },

        setFecha(tipo) {
            const now = new Date();
            let target = new Date(now);

            switch (tipo) {
                case 'hoy':
                    break;
                case 'manana':
                    target.setDate(now.getDate() + 1);
                    break;
                case '3dias':
                    target.setDate(now.getDate() + 3);
                    break;
                case 'semana':
                    target.setDate(now.getDate() + 7);
                    break;
            }

            this.fecha = target.toISOString().split('T')[0];
            this.shortcutActive = tipo;
        },

        agregarItem() {
            this.items.push({ key: key++, presentacion_id: '', cantidad: 1, notas: '' });
        },

        eliminarItem(index) {
            if (this.items.length > 1) {
                this.items.splice(index, 1);
            }
        },

        formatMoney(value) {
            return window.RochaFormat.money(Number(value || 0));
        },
    }
}
</script>

<style>
[x-cloak] { display: none !important; }
</style>
</x-app-layout>
