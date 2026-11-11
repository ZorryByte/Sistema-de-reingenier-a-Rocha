<x-app-layout>
<div class="py-6 px-4 max-w-[1400px] mx-auto" x-data="ventaForm()" x-cloak>

    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-3">
            <a href="{{ route('ventas.index') }}"
               class="text-bakery-muted/50 hover:text-bakery-muted transition p-1 rounded-lg hover:bg-bakery-warm"
               aria-label="Volver a ventas">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <div>
                <h2 class="text-2xl font-display font-bold text-bakery-dark">Nueva venta</h2>
                <p class="text-sm text-bakery-muted mt-0.5">Selecciona productos, ajusta cantidades y registra el pago</p>
            </div>
        </div>
        <div class="text-right hidden sm:block">
            <p class="text-xs text-bakery-muted/50">{{ now()->locale('es')->translatedFormat('l d \d\e M, Y') }}</p>
        </div>
    </div>

    @if(session('error'))
        <div class="mb-4 bg-bakery-terracotta/10 border border-bakery-terracotta/20 text-bakery-terracotta text-sm rounded-xl px-4 py-3 flex items-start gap-2">
            <svg class="w-5 h-5 flex-shrink-0 mt-0.5 text-bakery-terracotta" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
            </svg>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    @if($errors->any())
        <div role="alert" class="mb-4 bg-bakery-terracotta/10 border border-bakery-terracotta/20 text-bakery-terracotta text-sm rounded-xl px-4 py-3">
            <ul class="list-disc list-inside space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Required field legend --}}
    <p class="text-xs text-bakery-muted mb-4 italic">Los campos marcados con * son obligatorios</p>

    <div class="grid grid-cols-1 xl:grid-cols-12 gap-6">

        {{-- ==================== COLUMNA IZQUIERDA: Cat&aacute;logo + Carrito ==================== --}}
        <div class="xl:col-span-7 space-y-6">

            {{-- Cat&aacute;logo de productos --}}
            <div class="form-card">
                <h3 class="section-title mb-3">Cat&aacute;logo</h3>

                {{-- Buscador full-width --}}
                <div class="relative mb-3">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-bakery-muted/50" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input type="text" x-model="busqueda"
                           class="form-input pl-10 py-3 text-sm"
                           placeholder="Buscar producto o presentaci&oacute;n...">
                </div>

                {{-- Pills de filtros r&aacute;pidos --}}
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

                {{-- Accordion de productos --}}
                <template x-for="(presentaciones, productoNombre) in productosFiltrados" :key="productoNombre">
                    <div class="mb-2 last:mb-0">
                        {{-- Header del producto (clickable) --}}
                        <button type="button" @click="toggleProduct(productoNombre)"
                                class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl
                                       hover:bg-bakery-warm transition duration-150 text-left group">
                            <div class="flex items-center gap-2 min-w-0">
                                {{-- Chevron animado --}}
                                <svg class="w-4 h-4 text-bakery-muted/40 transition-transform duration-150 flex-shrink-0"
                                     :class="expandedProduct === productoNombre ? 'rotate-90' : ''"
                                     fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                                </svg>
                                <span class="text-sm font-semibold text-bakery-dark truncate" x-text="productoNombre"></span>
                            </div>
                            {{-- Contador de presentaciones --}}
                            <span class="text-xs font-medium text-bakery-muted/50 bg-bakery-warm px-2 py-0.5 rounded-full ml-2 flex-shrink-0"
                                  x-text="presentaciones.length"></span>
                        </button>

                        {{-- Presentaciones expandidas --}}
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
                                    <button type="button" @click="agregarItemDesdeCatalogo(pres)"
                                            :class="flashId === pres.id ? 'bg-bakery-sage/20 border-bakery-sage/40' : ''"
                                            class="text-left p-3 rounded-xl border bg-bakery-warm/50
                                                   transition duration-150 group relative"
                                            :style="`animation-delay: ${idx * 50}ms`"
                                            :disabled="pres.stock === 0"
                                            :title="pres.stock === 0 ? 'Sin stock disponible' : (pres.stock < 5 ? 'Stock bajo: ' + pres.stock : 'Stock: ' + pres.stock)">
                                        {{-- Name --}}
                                        <p class="text-sm font-medium text-bakery-dark group-hover:text-bakery-brown truncate"
                                           :class="pres.stock === 0 ? 'opacity-40' : ''"
                                           x-text="pres.presentacion"></p>
                                        {{-- Price --}}
                                        <p class="text-base font-bold mt-1"
                                           :class="pres.stock === 0 ? 'text-bakery-muted/50' : 'text-bakery-gold'"
                                           x-text="formatMoney(pres.precio)"></p>
                                        {{-- Stock badge --}}
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
                                                    <span x-text="pres.stock"></span>
                                                </span>
                                            </template>
                                            <template x-if="pres.stock >= 5">
                                                <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full text-xs font-medium bg-bakery-sage/20 text-bakery-sage">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                                    <span x-text="pres.stock"></span>
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

            {{-- Carrito de items --}}
            <div class="form-card">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="section-title mb-0">
                        Productos en venta
                        <span class="ml-1 text-bakery-muted/50 font-normal normal-case" x-text="`(${items.length})`"></span>
                    </h3>
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
                                {{-- Presentaci&oacute;n --}}
                                <div class="sm:col-span-4">
                                    <label class="form-label form-label-required">Producto</label>
                                    <select x-model="item.presentacion_id"
                                            @change="onPresentacionChange(index)"
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

                                {{-- Cantidad con botones +/- --}}
                                <div class="sm:col-span-2">
                                    <label class="form-label form-label-required">Cantidad</label>
                                    <div class="flex items-center gap-1">
                                        <button type="button" @click="decrementarCantidad(index)"
                                                class="w-7 h-7 flex items-center justify-center rounded-lg border border-bakery-amber/20 text-bakery-muted hover:bg-bakery-warm transition text-sm">
                                            &minus;
                                        </button>
                                        <input type="number" x-model.number="item.cantidad"
                                               @input.debounce.150ms="calcularTotales"
                                               :name="`items[${index}][cantidad]`"
                                               :step="item.tipo_venta === 'unidad' ? '1' : '0.0001'"
                                               :min="item.tipo_venta === 'unidad' ? '1' : '0.0001'"
                                                class="form-input py-2 px-2 text-xs text-center w-full" required>
                                        <button type="button" @click="incrementarCantidad(index)"
                                                class="w-7 h-7 flex items-center justify-center rounded-lg border border-bakery-amber/20 text-bakery-muted hover:bg-bakery-warm transition text-sm">
                                            +
                                        </button>
                                    </div>
                                    <template x-if="getFracciones(item.tipo_venta).length > 0">
                                        <div class="flex gap-1 mt-1">
                                            <template x-for="frac in getFracciones(item.tipo_venta)" :key="frac">
                                                <button type="button" @click="setFraccion(index, frac)"
                                                        class="px-1.5 py-0.5 text-xs rounded border border-bakery-amber/20 text-bakery-muted hover:bg-bakery-warm transition">
                                                    <span x-text="frac === 0.25 ? '¼' : frac === 0.5 ? '½' : '¾'"></span>
                                                </button>
                                            </template>
                                        </div>
                                    </template>
                                </div>

                                {{-- Descuento --}}
                                <div class="sm:col-span-2">
                                    <label class="form-label">Desc. $</label>
                                    <input type="number" x-model.number="item.descuento"
                                           @input.debounce.150ms="calcularTotales"
                                           :name="`items[${index}][descuento]`"
                                           step="0.01" min="0"
                                           class="form-input py-2 px-3 text-xs"
                                           placeholder="0.00">
                                </div>

                                {{-- Subtotal --}}
                                <div class="sm:col-span-2">
                                    <label class="form-label">Subtotal</label>
                                    <p class="text-sm font-bold text-bakery-dark py-2" x-text="formatMoney(item.subtotal)"></p>
                                </div>

                                {{-- Notas --}}
                                <div class="sm:col-span-2">
                                    <label class="form-label">Notas</label>
                                    <input type="text"
                                           :name="`items[${index}][notas]`"
                                                class="form-input py-2.5 px-3 text-xs"
                                                placeholder="Opcional">
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                {{-- Total --}}
                <div class="total-line">
                    <span class="total-label">Total de la venta</span>
                    <span class="total-value" x-text="formatMoney(totalGeneral)"></span>
                </div>
            </div>
        </div>

        {{-- ==================== COLUMNA DERECHA: Pagos + Resumen ==================== --}}
        <div class="xl:col-span-5">
            <div class="sticky top-6 space-y-6">

                <x-cliente-selector form="nueva-venta" />

                {{-- Resumen de items --}}
                <div class="form-card">
                    <h3 class="section-title">Resumen</h3>

                    <div class="space-y-2 max-h-48 overflow-y-auto pr-1">
                        <template x-for="(item, index) in items" :key="`res-${item.key}`">
                            <div class="flex items-center justify-between py-1.5 text-sm" x-show="item.presentacion_id">
                                <div class="flex-1 min-w-0">
                                    <span class="text-bakery-dark truncate block" x-text="getNombrePresentacion(item.presentacion_id)"></span>
                                    <span class="text-xs text-bakery-muted/50" x-text="`×${window.RochaFormat.quantity(item.cantidad)}`"></span>
                                </div>
                                <span class="font-medium text-bakery-dark ml-2" x-text="formatMoney(item.subtotal)"></span>
                            </div>
                        </template>
                        <template x-if="items.every(i => !i.presentacion_id)">
                            <p class="text-xs text-bakery-muted/50 text-center py-4">Agrega productos para ver el resumen</p>
                        </template>
                    </div>
                </div>

                {{-- Pagos --}}
                <div class="form-card">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="section-title mb-0">Pago</h3>
                        <button type="button" @click="agregarPago"
                                class="btn-ghost text-bakery-gold hover:text-bakery-brown hover:bg-bakery-gold/10"
                                aria-label="Agregar método de pago">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                            </svg>
                            M&eacute;todo
                        </button>
                    </div>

                    @error('pagos') <p class="form-error mb-3">{{ $message }}</p> @enderror

                    <div class="space-y-3">
                        <template x-for="(pago, index) in pagos" :key="pago.key">
                            <div class="p-3 rounded-xl border border-bakery-sage/30 bg-bakery-sage/5 relative">

                                <button type="button" @click="eliminarPago(index)"
                                        x-show="pagos.length > 1"
                                        class="data-row-remove"
                                        aria-label="Eliminar método de pago">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>

                                <div class="grid grid-cols-12 gap-2">
                                    {{-- M&eacute;todo --}}
                                    <div class="col-span-12 sm:col-span-4">
                                        <label class="form-label form-label-required">M&eacute;todo</label>
                                        <select x-model="pago.metodo"
                                                :name="`pagos[${index}][metodo]`"
                                                class="form-select py-2.5 px-3 text-xs" required>
                                            <option value="efectivo">
                                                Efectivo
                                            </option>
                                            <option value="tarjeta">
                                                Tarjeta
                                            </option>
                                            <option value="transferencia">
                                                Transferencia
                                            </option>
                                            <option value="otro">
                                                Otro
                                            </option>
                                        </select>
                                    </div>

                                    {{-- Monto --}}
                                    <div class="col-span-7 sm:col-span-5">
                                        <label class="form-label form-label-required">Monto $</label>
                                        <input type="number" x-model.number="pago.monto"
                                               @input.debounce.150ms="calcularCambio"
                                               :name="`pagos[${index}][monto]`"
                                               step="0.01" min="0.01"
                                                class="form-input py-2.5 px-3 text-xs" required>
                                    </div>

                                    {{-- Referencia --}}
                                    <div class="col-span-5 sm:col-span-3">
                                        <label class="form-label">Ref.</label>
                                        <input type="text" x-model="pago.referencia"
                                               :name="`pagos[${index}][referencia]`"
                                               class="form-input py-2 px-3 text-xs"
                                               placeholder="Opcional">
                                    </div>
                                </div>

                                {{-- Botones r&aacute;pidos de monto (solo efectivo) --}}
                                <div x-show="pago.metodo === 'efectivo' && totalGeneral > 0" class="flex flex-wrap gap-1 mt-2">
                                    <template x-for="montoRapido in montosRapidos" :key="montoRapido">
                                        <button type="button" @click="pago.monto = montoRapido; calcularCambio()"
                                                :class="pago.monto === montoRapido ? 'bg-bakery-gold/20 text-bakery-brown' : 'bg-white text-bakery-muted hover:bg-bakery-gold/10'"
                                                class="px-2 py-1 text-xs font-medium rounded-lg border border-bakery-amber/20 transition">
                                            $<span x-text="montoRapido"></span>
                                        </button>
                                    </template>
                                    <button type="button" @click="pago.monto = totalGeneral; calcularCambio()"
                                            :class="Math.abs(pago.monto - totalGeneral) < 0.01 ? 'bg-bakery-gold/20 text-bakery-brown' : 'bg-white text-bakery-muted hover:bg-bakery-gold/10'"
                                            class="px-2 py-1 text-xs font-medium rounded-lg border border-bakery-amber/20 transition">
                                        Exacto
                                    </button>
                                </div>
                            </div>
                        </template>
                    </div>

                    {{-- Totales de pago --}}
                    <div class="mt-4 pt-4 border-t border-bakery-amber/20 space-y-2">
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-bakery-muted">Total pagado</span>
                            <span class="text-lg font-bold text-bakery-sage" x-text="formatMoney(totalPagado)"></span>
                        </div>

                        {{-- Barra de progreso de pago --}}
                        <div x-show="totalGeneral > 0" class="w-full bg-bakery-warm rounded-full h-2 overflow-hidden">
                            <div class="h-full rounded-full transition-all duration-300"
                                 :class="cambio >= 0 ? 'bg-bakery-sage/100' : 'bg-bakery-amber'"
                                 :style="`width: ${Math.min(100, (totalPagado / totalGeneral) * 100)}%`"></div>
                        </div>

                        <div class="flex justify-between items-center">
                            <span class="text-sm text-bakery-muted">Cambio</span>
                            <span class="text-lg font-bold"
                                  :class="cambio >= 0 ? 'text-bakery-sage' : 'text-bakery-terracotta'"
                                  x-text="formatMoney(Math.max(0, cambio))"></span>
                        </div>
                    </div>

                    <div x-show="totalPagado < totalGeneral && totalGeneral > 0"
                         class="mt-3 p-3 bg-bakery-gold/10 border border-bakery-amber/20 rounded-xl"
                         x-transition>
                        <p class="text-xs text-bakery-brown">
                            Falta por pagar: <strong x-text="formatMoney(totalGeneral - totalPagado)"></strong>
                        </p>
                    </div>
                </div>

                {{-- Notas generales --}}
                <div class="form-card-compact">
                    <label class="text-xs font-medium text-bakery-muted mb-1 block">
                        Notas <span class="text-bakery-muted/50 font-normal">(opcional)</span>
                    </label>
                    <textarea name="notas" rows="2"
                              class="form-textarea py-2 px-3 text-xs"
                              placeholder="Observaciones sobre la venta...">{{ old('notas') }}</textarea>
                </div>

                {{-- Bot&oacute;n confirmar --}}
                <button type="button" x-ref="openSale" @click="confirmarVenta"
                        :disabled="!puedeConfirmar"
                        :class="puedeConfirmar
                            ? 'bg-bakery-sage hover:bg-bakery-sage/90 shadow-lg shadow-bakery-sage/20'
                            : 'bg-bakery-amber/30 cursor-not-allowed shadow-none'"
                        class="w-full py-3.5 text-white text-sm font-bold rounded-xl transition duration-150 flex items-center justify-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                    </svg>
                    Confirmar venta
                </button>
            </div>
        </div>
    </div>

    {{-- Formulario oculto para submit real --}}
    <form id="nueva-venta" x-ref="form" action="{{ route('ventas.store') }}" method="POST" class="hidden">
        @csrf
    </form>

    {{-- Modal de confirmaci&oacute;n --}}
    <div x-show="mostrarModal"
         aria-modal="true"
         role="dialog"
         @click.self="mostrarModal = false"
         @keydown.escape.window="mostrarModal = false"
         @keydown.tab.window="if (mostrarModal) window.rochaTrapTab($event, $refs.modalConfirmacion)"
         aria-label="Confirmar venta"
         class="fixed inset-0 bg-bakery-dark/50 backdrop-blur-sm flex items-center justify-center z-50 p-4"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">

        <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full overflow-hidden"
             x-ref="modalConfirmacion"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95 translate-y-4"
             x-transition:enter-end="opacity-100 scale-100 translate-y-0">

            {{-- Header del modal --}}
            <div class="bg-bakery-gold px-6 py-4">
                <div class="flex items-center gap-3">
                    <div class="bg-white/20 rounded-full p-2">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-white">Confirmar venta</h3>
                </div>
            </div>

            {{-- Cuerpo tipo recibo --}}
            <div class="px-6 py-4 space-y-3 max-h-72 overflow-y-auto">
                {{-- Items --}}
                <div class="space-y-1.5">
                    <template x-for="(item, index) in items" :key="`modal-${item.key}`">
                        <div class="flex justify-between text-sm py-1" x-show="item.presentacion_id">
                            <span class="text-bakery-muted truncate pr-2">
                                <span x-text="getNombrePresentacion(item.presentacion_id)"></span>
                                <span class="text-bakery-muted/50" x-text="` ×${window.RochaFormat.quantity(item.cantidad)}`"></span>
                            </span>
                            <span class="font-medium text-bakery-dark" x-text="formatMoney(item.subtotal)"></span>
                        </div>
                    </template>
                </div>

                <div class="border-t border-bakery-amber/20 pt-3">
                    <div class="flex justify-between">
                        <span class="text-sm font-semibold text-bakery-dark">Total</span>
                        <span class="text-xl font-bold text-stone-900" x-text="formatMoney(totalGeneral)"></span>
                    </div>
                </div>

                {{-- Pagos --}}
                <div class="space-y-1.5">
                    <p class="text-xs font-semibold text-bakery-muted uppercase tracking-wide">Pagos</p>
                    <template x-for="(pago, index) in pagos" :key="`modal-pago-${pago.key}`">
                        <div class="flex justify-between text-sm py-1">
                            <span class="text-bakery-muted capitalize" x-text="pago.metodo"></span>
                            <span class="font-medium text-bakery-sage" x-text="formatMoney(pago.monto)"></span>
                        </div>
                    </template>
                </div>

                @if($errors->any())
                <div class="bg-bakery-terracotta/10 border border-bakery-terracotta/20 rounded-xl px-3 py-2">
                    @foreach($errors->all() as $error)
                        <p class="text-xs text-bakery-terracotta">{{ $error }}</p>
                    @endforeach
                </div>
                @endif
            </div>

            {{-- Footer del modal --}}
            <div class="border-t border-bakery-amber/20 px-6 py-4 flex gap-3">
                <button type="button" x-ref="cancelSale" @click="mostrarModal = false"
                        class="flex-1 btn-secondary">Cancelar</button>
                <button type="button" @click="procesarVenta"
                        :disabled="procesando"
                        class="flex-1 btn-primary bg-bakery-sage hover:bg-bakery-sage/90 disabled:bg-bakery-amber/30">
                    <span x-show="!procesando">Confirmar</span>
                    <span x-show="procesando">
                        <svg class="animate-spin w-4 h-4 inline mr-1" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        Procesando...
                    </span>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
const catalogo = @json($catalogo);
const precios = @json($precios);

function ventaForm() {
    const oldItems = @js(old('items', []));
    const oldPayments = @js(old('pagos', []));
    const items = Object.values(oldItems ?? {}).filter(item => item && typeof item === 'object').map((item, key) => ({
        key, presentacion_id: item.presentacion_id ?? '', cantidad: item.cantidad === undefined ? 1 : Number(item.cantidad),
        descuento: item.descuento ?? 0, notas: item.notas ?? '', subtotal: 0,
        tipo_venta: catalogo.find(product => product.id == item.presentacion_id)?.tipo_venta || 'unidad',
    }));
    const payments = Object.values(oldPayments ?? {}).filter(payment => payment && typeof payment === 'object').map((payment, key) => ({
        key, metodo: payment.metodo ?? 'efectivo', monto: Number(payment.monto ?? 0), referencia: payment.referencia ?? '',
    }));
    let itemKey = Math.max(1, items.length);
    let pagoKey = Math.max(1, payments.length);

    const productosMap = {};
    catalogo.forEach(p => {
        if (!productosMap[p.producto]) productosMap[p.producto] = [];
        productosMap[p.producto].push(p);
    });

    const nombreMap = {};
    catalogo.forEach(p => { nombreMap[p.id] = p.presentacion; });

    return {
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
        nombreMap: nombreMap,

        items: items.length ? items : [{ key: 0, presentacion_id: '', cantidad: 1, descuento: 0, subtotal: 0, tipo_venta: 'unidad' }],
        pagos: payments.length ? payments : [{ key: 0, metodo: 'efectivo', monto: 0, referencia: '' }],

        totalGeneral: 0,
        totalPagado: 0,
        cambio: 0,
        mostrarModal: false,
        procesando: false,

        init() {
            this.calcularTotales();
            this.$watch('mostrarModal', (open) => this.$nextTick(() => {
                (open ? this.$refs.cancelSale : this.$refs.openSale)?.focus();
            }));
        },

        montosRapidos: [10, 20, 50, 100, 200, 500],

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

        get puedeConfirmar() {
            return this.items.length > 0 &&
                   this.items.every(i => i.presentacion_id && i.cantidad > 0 &&
                       (i.tipo_venta !== 'unidad' || Number.isInteger(i.cantidad))) &&
                   this.pagos.length > 0 &&
                   this.pagos.every(p => p.monto > 0) &&
                   this.totalGeneral > 0 &&
                   this.totalPagado >= this.totalGeneral;
        },

        agregarItemDesdeCatalogo(pres) {
            // Prevent adding out-of-stock items from catalog
            if (pres.stock === 0) return;

            const existente = this.items.find(i => i.presentacion_id == pres.id);
            if (existente) {
                existente.cantidad += this.getIncremento(existente.tipo_venta);
            } else {
                const vacioIdx = this.items.findIndex(i => i.presentacion_id === '');
                if (vacioIdx !== -1) {
                    this.items[vacioIdx].presentacion_id = pres.id;
                    this.items[vacioIdx].cantidad = 1;
                    this.items[vacioIdx].descuento = 0;
                    this.items[vacioIdx].subtotal = 0;
                    this.items[vacioIdx].tipo_venta = pres.tipo_venta || 'unidad';
                } else {
                    this.items.push({
                        key: itemKey++,
                        presentacion_id: pres.id,
                        cantidad: 1,
                        descuento: 0,
                        subtotal: 0,
                        tipo_venta: pres.tipo_venta || 'unidad'
                    });
                }
            }
            this.flashId = pres.id;
            setTimeout(() => { this.flashId = null; }, 300);
            this.calcularTotales();
        },

        agregarItem() {
            this.items.push({ key: itemKey++, presentacion_id: '', cantidad: 1, descuento: 0, subtotal: 0, tipo_venta: 'unidad' });
        },

        eliminarItem(index) {
            if (this.items.length > 1) {
                this.items.splice(index, 1);
                this.calcularTotales();
            }
        },

        agregarPago() {
            this.pagos.push({ key: pagoKey++, metodo: 'efectivo', monto: 0, referencia: '' });
        },

        eliminarPago(index) {
            if (this.pagos.length > 1) {
                this.pagos.splice(index, 1);
                this.calcularCambio();
            }
        },

        calcularTotales() {
            let totalCents = 0;
            this.items.forEach(item => {
                const priceCents = scaledInteger(precios[item.presentacion_id], 2);
                const quantityUnits = scaledInteger(item.cantidad, 4);
                const grossCents = Number((BigInt(priceCents) * BigInt(quantityUnits) + 5000n) / 10000n);
                const lineCents = Math.max(0, grossCents - scaledInteger(item.descuento, 2));
                item.subtotal = lineCents / 100;
                totalCents += lineCents;
            });
            this.totalGeneral = totalCents / 100;
            this.calcularCambio();
        },

        getIncremento(tipoVenta) {
            return tipoVenta === 'peso' ? 0.1 : 1;
        },

        getTipoVenta(presentacionId) {
            const item = this.items.find(i => i.presentacion_id == presentacionId);
            return item?.tipo_venta || 'unidad';
        },

        getFracciones(tipoVenta) {
            if (tipoVenta === 'peso' || tipoVenta === 'porcion') {
                return [0.25, 0.5, 0.75];
            }
            return [];
        },

        incrementarCantidad(index) {
            const item = this.items[index];
            const inc = item.tipo_venta === 'unidad' ? 1 : 0.1;
            item.cantidad = roundTo(parseFloat(item.cantidad) + inc, 4);
            this.calcularTotales();
        },

        decrementarCantidad(index) {
            const item = this.items[index];
            if (item.tipo_venta === 'unidad') {
                if (item.cantidad > 1) {
                    item.cantidad = roundTo(parseFloat(item.cantidad) - 1, 4);
                }
            } else {
                if (item.cantidad > 0.0001) {
                    item.cantidad = Math.max(0.0001, roundTo(parseFloat(item.cantidad) - 0.1, 4));
                }
            }
            this.calcularTotales();
        },

        setFraccion(index, fraccion) {
            this.items[index].cantidad = fraccion;
            this.calcularTotales();
        },

        calcularCambio() {
            const paidCents = this.pagos.reduce((sum, payment) => sum + scaledInteger(payment.monto, 2), 0);
            this.totalPagado = paidCents / 100;
            this.cambio = (paidCents - scaledInteger(this.totalGeneral, 2)) / 100;
        },

        getNombrePresentacion(id) {
            return this.nombreMap[id] || 'Sin seleccionar';
        },

        onPresentacionChange(index) {
            const presId = this.items[index].presentacion_id;
            const pres = catalogo.find(p => p.id == presId);
            if (pres) {
                this.items[index].tipo_venta = pres.tipo_venta || 'unidad';
            }
            this.calcularTotales();
        },

        formatMoney(value) {
            return window.RochaFormat.money(Number(value || 0));
        },

        confirmarVenta() {
            this.calcularTotales();
            if (!this.puedeConfirmar) return;
            this.mostrarModal = true;
        },

        procesarVenta() {
            this.calcularTotales();
            if (!this.puedeConfirmar || this.procesando) return;

            const form = this.$refs.form;
            const precios = @json($precios);

            // Limpiar inputs anteriores
            form.querySelectorAll('input[name^="items"], input[name^="pagos"]').forEach(el => el.remove());

            // Agregar items
            this.items.forEach((item, index) => {
                if (!item.presentacion_id) return;
                ['presentacion_id', 'cantidad', 'descuento', 'notas'].forEach(field => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = `items[${index}][${field}]`;
                    input.value = field === 'notas' ? (item[field] || '') : (item[field] ?? '');
                    form.appendChild(input);
                });
                // Enviar precio unitario para validacion en backend
                const precioInput = document.createElement('input');
                precioInput.type = 'hidden';
                precioInput.name = `items[${index}][precio_unitario]`;
                precioInput.value = precios[item.presentacion_id] || 0;
                form.appendChild(precioInput);
            });

            // Agregar pagos
            this.pagos.forEach((pago, index) => {
                if (!pago.metodo || pago.monto <= 0) return;
                ['metodo', 'monto', 'referencia'].forEach(field => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = `pagos[${index}][${field}]`;
                    input.value = field === 'referencia' ? (pago[field] || '') : (pago[field] ?? '');
                    form.appendChild(input);
                });
            });

            this.procesando = true;
            form.submit();
        },
    }
}

function scaledInteger(value, decimals) {
    const number = Number(value);
    if (!Number.isFinite(number)) return 0;

    const [coefficient, exponent = '0'] = String(number).split('e');
    const scaled = Number(coefficient + 'e' + (Number(exponent) + decimals));
    return Number.isSafeInteger(Math.round(scaled)) ? Math.round(scaled) : 0;
}

function roundTo(num, decimals) {
    const factor = Math.pow(10, decimals);
    return Math.round(num * factor) / factor;
}
</script>

<style>
[x-cloak] { display: none !important; }
</style>
</x-app-layout>
