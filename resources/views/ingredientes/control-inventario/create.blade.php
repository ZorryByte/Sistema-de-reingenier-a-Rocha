<x-app-layout>
@php
    $ingredienteId = old('ingrediente_id', $ingredienteSeleccionado?->id);
    $backRoute = $ingredienteSeleccionado
        ? route('ingredientes.historial', $ingredienteSeleccionado)
        : route('ingredientes.index');
@endphp

<div class="inventory-page" x-data="ingredienteMovimientoForm()">
<div class="mx-auto w-full max-w-6xl">

    <div x-show="submitting" x-transition.delay.200ms class="fixed inset-0 bg-bakery-dark/30 backdrop-blur-sm z-50 flex items-center justify-center">
        <div class="bg-white rounded-2xl p-6 shadow-2xl flex items-center gap-4">
            <svg class="animate-spin w-8 h-8 text-bakery-gold" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
            <span class="text-bakery-dark font-semibold">Registrando movimiento...</span>
        </div>
    </div>

    <div class="inventory-header mb-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div class="flex items-start gap-3">
                <a href="{{ $backRoute }}"
                   class="mt-1 inline-flex min-h-[40px] min-w-[40px] items-center justify-center rounded-xl text-bakery-muted hover:bg-bakery-warm hover:text-bakery-dark transition"
                   aria-label="Volver a ingredientes">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                    </svg>
                </a>
                <div>
                    <p class="dashboard-kicker">Inventario de ingredientes</p>
                    <h2 class="mt-2 text-2xl font-display font-bold tracking-tight text-bakery-dark sm:text-3xl">Registrar entrada o merma</h2>
                    <p class="mt-1 max-w-2xl text-sm leading-relaxed text-bakery-muted">Usa esta pantalla para inventario inicial, ajustes por conteo, devoluciones sin compra o mermas de materia prima.</p>
                </div>
            </div>
            <div class="inventory-muted-panel sm:max-w-xs">
                <p class="text-xs font-semibold text-bakery-dark">Antes de guardar</p>
                <p class="mt-1 text-xs leading-relaxed text-bakery-muted">Si recibiste ingredientes de un proveedor, registra una compra para conservar proveedor, precio e historial contable.</p>
            </div>
        </div>
    </div>

    @if(session('error'))
        <div class="mb-4 bg-bakery-terracotta/10 border border-bakery-terracotta/20 text-bakery-terracotta text-sm rounded-xl px-4 py-3">
            {{ session('error') }}
        </div>
    @endif

    @if($errors->any())
        <div class="mb-4 bg-bakery-terracotta/10 border border-bakery-terracotta/20 text-bakery-terracotta text-sm rounded-xl px-4 py-3">
            <ul class="list-disc list-inside space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('ingredientes.control-inventario.store') }}" method="POST" class="space-y-6" @submit="submitting = true">
        @csrf

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-[minmax(0,1fr)_360px]">
            <div class="space-y-6">
                <div class="form-card">
                    <div class="mb-4 flex items-start justify-between gap-3">
                        <div>
                            <h3 class="section-title mb-1">Tipo de movimiento</h3>
                            <p class="text-xs text-bakery-muted">Elige si el ajuste suma stock o descuenta una perdida.</p>
                        </div>
                        <span class="rounded-full bg-bakery-warm px-3 py-1 text-[11px] font-semibold text-bakery-muted">Paso 1</span>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        @php
                            $tipos = [
                                'entrada' => ['label' => 'Entrada por ajuste', 'icon' => 'M12 4v16m8-8H4', 'desc' => 'Inicial, conteo o devolucion'],
                                'merma' => ['label' => 'Merma', 'icon' => 'M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16', 'desc' => 'Danado, vencido o correccion'],
                            ];
                        @endphp
                        @foreach($tipos as $valor => $info)
                        <label class="relative">
                            <input type="radio" name="tipo" value="{{ $valor }}"
                                   {{ old('tipo', 'entrada') === $valor ? 'checked' : '' }}
                                   x-model="tipo"
                                   class="peer sr-only">
                            <div class="movement-choice">
                                <svg class="mb-3 h-6 w-6 text-bakery-gold" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $info['icon'] }}"/>
                                </svg>
                                <span class="movement-choice-title">{{ $info['label'] }}</span>
                                <p class="movement-choice-text">{{ $info['desc'] }}</p>
                            </div>
                        </label>
                        @endforeach
                    </div>
                    @error('tipo') <p class="form-error">{{ $message }}</p> @enderror
                </div>

                <div class="form-card">
                    <div class="mb-4 flex items-start justify-between gap-3">
                        <div>
                            <h3 class="section-title mb-1">Detalle del ingrediente</h3>
                            <p class="text-xs text-bakery-muted">Selecciona el ingrediente, cantidad y motivo del movimiento.</p>
                        </div>
                        <span class="rounded-full bg-bakery-warm px-3 py-1 text-[11px] font-semibold text-bakery-muted">Paso 2</span>
                    </div>

                    <div class="space-y-4">
                        <div>
                            <label class="form-label form-label-required" for="ingrediente_id">Ingrediente</label>
                            <select id="ingrediente_id" name="ingrediente_id" x-model="ingredienteId" class="form-select" required>
                                <option value="">Seleccionar ingrediente...</option>
                                @foreach($ingredientes as $ing)
                                <option value="{{ $ing->id }}"
                                        data-unidad="{{ \App\Support\UnitFormatter::short($ing->unidad_medida) }}"
                                        data-stock="{{ $ing->stock_actual }}"
                                        data-costo="{{ $ing->costo_unitario }}"
                                        @selected((string) $ingredienteId === (string) $ing->id)>
                                    {{ $ing->nombre }} ({{ \App\Support\UnitFormatter::short($ing->unidad_medida) }}) | Stock: {{ $ing->stock_actual }}
                                </option>
                                @endforeach
                            </select>
                            @error('ingrediente_id') <p class="form-error">{{ $message }}</p> @enderror
                        </div>

                        <div x-show="ingredienteId"
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0 -translate-y-1"
                             x-transition:enter-end="opacity-100 translate-y-0"
                             class="not-muted-info">
                            <span class="font-medium">Stock actual:</span>
                            <span x-text="stockActual"></span>
                        </div>

                        <div>
                            <label class="form-label form-label-required" for="cantidad">Cantidad</label>
                            <input type="number" id="cantidad" name="cantidad" value="{{ old('cantidad') }}"
                                   step="0.01" min="0.01"
                                   class="form-input" placeholder="0.00" required
                                   x-model="cantidad">
                            @error('cantidad') <p class="form-error">{{ $message }}</p> @enderror
                        </div>

                        <div x-show="tipo === 'entrada'" x-transition class="rounded-xl border border-bakery-amber/20 bg-bakery-gold/10 p-3">
                            <p class="text-sm font-semibold text-bakery-dark">Entrada manual controlada</p>
                            <p class="mt-1 text-xs leading-relaxed text-bakery-muted">Compras queda pausado en esta fase. Usa esta entrada solo para ajustes, inventario inicial o correcciones autorizadas.</p>
                        </div>

                        <div x-show="tipo === 'entrada'" x-transition>
                            <label class="form-label form-label-required" for="categoria_entrada">Motivo de entrada</label>
                            <select id="categoria_entrada" name="categoria" class="form-select" :disabled="tipo !== 'entrada'" :required="tipo === 'entrada'">
                                <option value="">Seleccionar motivo...</option>
                                <option value="inventario_inicial" @selected(old('categoria') === 'inventario_inicial')>Inventario inicial</option>
                                <option value="ajuste_conteo" @selected(old('categoria') === 'ajuste_conteo')>Ajuste por conteo fisico</option>
                                <option value="devolucion" @selected(old('categoria') === 'devolucion')>Devolucion a inventario</option>
                                <option value="donacion" @selected(old('categoria') === 'donacion')>Donacion o ingreso sin proveedor</option>
                                <option value="correccion_costo" @selected(old('categoria') === 'correccion_costo')>Correccion de costo</option>
                                <option value="otro" @selected(old('categoria') === 'otro')>Otro ajuste</option>
                            </select>
                            @error('categoria') <p class="form-error">{{ $message }}</p> @enderror
                        </div>

                        <div x-show="tipo === 'entrada'" x-transition>
                            <label class="form-label form-label-required">Modo del costo</label>
                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                <label class="relative">
                                    <input type="radio" name="modo_costo" value="precio_actual"
                                           x-model="modoCosto"
                                           class="peer sr-only">
                                    <div class="min-h-[92px] rounded-xl border border-bakery-amber/20 p-3 cursor-pointer transition peer-checked:border-bakery-sage peer-checked:bg-bakery-sage/10 hover:bg-bakery-warm">
                                        <p class="text-sm font-semibold text-bakery-dark">Precio actual</p>
                                        <p class="mt-1 text-xs text-bakery-muted">Mantiene el precio de compra.</p>
                                        <p class="mt-2 text-xs font-medium text-bakery-sage" x-text="formatMoney(precioCompraActual) + ' total'"></p>
                                    </div>
                                </label>

                                <label class="relative">
                                    <input type="radio" name="modo_costo" value="nuevo_precio"
                                           x-model="modoCosto"
                                           class="peer sr-only">
                                    <div class="min-h-[92px] rounded-xl border border-bakery-amber/20 p-3 cursor-pointer transition peer-checked:border-bakery-gold peer-checked:bg-bakery-gold/10 hover:bg-bakery-warm">
                                        <p class="text-sm font-semibold text-bakery-dark">Nuevo precio</p>
                                        <p class="mt-1 text-xs text-bakery-muted">Recalcula costo promedio.</p>
                                        <p class="mt-2 text-xs font-medium text-bakery-brown" x-show="costoCambia" x-text="formatMoney(costoResultante) + ' / ' + unidadMedida"></p>
                                    </div>
                                </label>
                            </div>
                            @error('modo_costo') <p class="form-error">{{ $message }}</p> @enderror
                        </div>

                        <div x-show="tipo === 'entrada' && modoCosto === 'nuevo_precio'" x-transition>
                            <label class="form-label form-label-required" for="precio_unitario">Nuevo costo unitario ($/<span x-text="unidadMedida"></span>)</label>
                            <input type="number" id="precio_unitario" name="precio_unitario" value="{{ old('precio_unitario') }}"
                                   step="0.0001" min="0.0001"
                                   class="form-input" placeholder="0.0000"
                                   x-model="precioUnitario"
                                   :disabled="modoCosto !== 'nuevo_precio'"
                                   :required="tipo === 'entrada' && modoCosto === 'nuevo_precio'">
                            @error('precio_unitario') <p class="form-error">{{ $message }}</p> @enderror
                            @error('costo_total') <p class="form-error">{{ $message }}</p> @enderror
                            <p class="mt-1 text-xs text-bakery-muted/50">Para compras reales, usa Compras. Este costo queda como ajuste auditado.</p>
                        </div>

                        <div x-show="tipo === 'merma'">
                            <label class="form-label" for="categoria_merma">
                                Razon <span class="text-bakery-muted/50 font-normal">(opcional)</span>
                            </label>
                            <select id="categoria_merma" name="categoria" class="form-select" :disabled="tipo !== 'merma'">
                                <option value="">Sin especificar</option>
                                <option value="caducidad" @selected(old('categoria') === 'caducidad')>Caducidad</option>
                                <option value="manipulacion" @selected(old('categoria') === 'manipulacion')>Manipulacion / Caida</option>
                                <option value="presentacion" @selected(old('categoria') === 'presentacion')>Presentacion / Corte</option>
                                <option value="diferencia_conteo" @selected(old('categoria') === 'diferencia_conteo')>Diferencia por conteo</option>
                                <option value="otro" @selected(old('categoria') === 'otro')>Otro</option>
                            </select>
                            @error('categoria') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="space-y-6">
                <div class="form-card">
                    <h3 class="section-title">Info adicional</h3>

                    <div class="space-y-4">
                        <div>
                            <label class="form-label" for="fecha">Fecha</label>
                            <input type="date" id="fecha" name="fecha" value="{{ old('fecha', now()->format('Y-m-d')) }}" class="form-input">
                        </div>

                        <div>
                            <label class="form-label" for="notas">
                                Notas <span class="text-bakery-muted/50 font-normal">(opcional)</span>
                            </label>
                            <textarea id="notas" name="notas" rows="3"
                                      class="form-textarea"
                                      placeholder="Motivo, proveedor, observaciones...">{{ old('notas') }}</textarea>
                        </div>
                    </div>
                </div>

                <div class="form-card lg:sticky lg:top-6" x-show="ingredienteId && cantidad > 0"
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0 scale-95"
                     x-transition:enter-end="opacity-100 scale-100">
                    <h3 class="section-title">Previsualizacion</h3>
                    <div class="grid grid-cols-[1fr_auto_1fr] items-center gap-3 rounded-2xl bg-bakery-warm/70 p-4">
                        <div>
                            <p class="mb-1 text-[10px] font-semibold uppercase tracking-[0.12em] text-bakery-muted">Actual</p>
                            <p class="text-xl font-bold text-bakery-dark tabular-nums" x-text="stockActual"></p>
                        </div>
                        <svg class="w-5 h-5 text-bakery-gold flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                        </svg>
                        <div class="text-right">
                            <p class="mb-1 text-[10px] font-semibold uppercase tracking-[0.12em] text-bakery-muted">Resultante</p>
                            <p class="text-xl font-bold tabular-nums"
                               :class="stockResultante < 0 ? 'text-bakery-terracotta' : 'text-bakery-brown'"
                               x-text="stockResultante"></p>
                        </div>
                    </div>
                    <p x-show="stockResultante < 0" class="mt-1 text-xs text-bakery-terracotta text-center">Stock no puede ser negativo</p>
                    <div x-show="tipo === 'entrada'" class="mt-4 border-t border-bakery-amber/10 pt-4">
                        <div class="grid grid-cols-[1fr_auto_1fr] items-center gap-3">
                            <div>
                                <p class="mb-1 text-[10px] font-semibold uppercase tracking-[0.12em] text-bakery-muted">Costo actual</p>
                                <p class="text-sm font-semibold text-bakery-dark tabular-nums" x-text="formatMoney(costoActual)"></p>
                            </div>
                            <svg class="w-4 h-4 text-bakery-gold flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                            </svg>
                            <div class="text-right">
                                <p class="mb-1 text-[10px] font-semibold uppercase tracking-[0.12em] text-bakery-muted">Costo final</p>
                                <p class="text-sm font-semibold tabular-nums" :class="costoCambia ? 'text-bakery-brown' : 'text-bakery-sage'" x-text="formatMoney(costoResultante)"></p>
                            </div>
                        </div>
                        <p x-show="modoCosto === 'precio_actual'" class="mt-2 text-xs text-bakery-sage text-center">El precio de compra se mantiene y el costo unitario se recalcula.</p>
                    </div>
                </div>

                <div class="form-card-compact bg-bakery-gold/10 border-bakery-amber/20">
                    <div class="flex items-start gap-2">
                        <svg class="w-4 h-4 text-bakery-gold mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                        </svg>
                        <div>
                            <p class="text-xs font-medium text-bakery-brown">Tipos de movimiento</p>
                            <p class="mt-1 text-xs text-bakery-brown">
                                <strong>Entrada:</strong> inventario inicial, conteo o devolucion.<br>
                                <strong>Merma:</strong> ingrediente danado, vencido o correccion de stock.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="sticky bottom-0 -mx-4 mt-2 flex flex-col-reverse gap-3 border-t border-bakery-amber/20 bg-bakery-cream/95 px-4 py-4 backdrop-blur-sm sm:-mx-6 sm:flex-row sm:justify-end sm:px-6">
            <a href="{{ $backRoute }}" class="btn-secondary">Cancelar</a>
            <button type="submit" :disabled="submitting || stockResultante < 0" class="btn-primary disabled:opacity-60 disabled:cursor-not-allowed">
                <svg x-show="!submitting" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                </svg>
                <svg x-show="submitting" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                <span x-text="submitting ? 'Guardando...' : 'Registrar movimiento'"></span>
            </button>
        </div>
    </form>
</div>
</div>

<script>
function ingredienteMovimientoForm() {
    return {
        submitting: false,
        tipo: '{{ old('tipo', 'entrada') }}',
        ingredienteId: '{{ $ingredienteId }}',
        modoCosto: '{{ old('modo_costo', 'precio_actual') }}',
        precioUnitario: '{{ old('precio_unitario') }}',
        cantidad: '{{ old('cantidad', 0) }}',
        get selectedOption() {
            if (!this.ingredienteId) return null;
            return document.getElementById('ingrediente_id')?.querySelector(`option[value="${this.ingredienteId}"]`) || null;
        },
        get stockActual() {
            return this.selectedOption ? parseFloat(this.selectedOption.dataset.stock) || 0 : 0;
        },
        get unidadMedida() {
            return this.selectedOption?.dataset.unidad || '';
        },
        get costoActual() {
            return this.selectedOption ? parseFloat(this.selectedOption.dataset.costo) || 0 : 0;
        },
        get precioCompraActual() {
            return this.stockActual * this.costoActual;
        },
        get stockResultante() {
            const cant = parseFloat(this.cantidad) || 0;
            return this.tipo === 'entrada' ? this.stockActual + cant : this.stockActual - cant;
        },
        get costoResultante() {
            const cant = parseFloat(this.cantidad) || 0;
            const precioUnitario = parseFloat(this.precioUnitario) || 0;
            if (this.tipo !== 'entrada' || cant <= 0) {
                return this.costoActual;
            }
            const stockNuevo = this.stockActual + cant;
            const valorActual = this.precioCompraActual;
            if (this.modoCosto === 'precio_actual') {
                return stockNuevo > 0 ? valorActual / stockNuevo : 0;
            }
            const valorEntrada = cant * precioUnitario;
            return stockNuevo > 0 ? (valorActual + valorEntrada) / stockNuevo : 0;
        },
        get costoCambia() {
            return Math.abs(this.costoResultante - this.costoActual) > 0.0001;
        },
        formatMoney(value) {
            return window.RochaFormat.unitCost(Number(value || 0));
        }
    }
}
</script>
</x-app-layout>
