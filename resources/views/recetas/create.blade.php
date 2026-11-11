<x-app-layout>
<div class="py-10 px-6 max-w-4xl mx-auto" x-data="recetaForm()" x-cloak>

    {{-- Cargando --}}
    <div x-show="submitting" x-transition.opacity class="fixed inset-0 bg-bakery-dark/30 backdrop-blur-sm z-50 flex items-center justify-center">
        <div class="bg-white rounded-2xl px-8 py-7 shadow-2xl flex items-center gap-4">
            <div class="relative w-5 h-5">
                <div class="absolute inset-0 rounded-full border-2 border-bakery-amber/20"></div>
                <div class="absolute inset-0 rounded-full border-2 border-transparent border-t-bakery-gold animate-spin"></div>
            </div>
            <span class="text-bakery-dark font-semibold text-sm">Guardando...</span>
        </div>
    </div>

    {{-- Encabezado --}}
    <div class="flex items-center gap-3 mb-10">
        <a href="{{ route('recetas.index') }}"
           class="w-9 h-9 rounded-lg flex items-center justify-center text-bakery-muted hover:text-bakery-dark hover:bg-bakery-warm transition-colors"
           aria-label="Volver">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
            </svg>
        </a>
        <div>
            <h1 class="text-xl font-display font-bold text-bakery-dark tracking-tight leading-tight">Nueva receta</h1>
            <p class="text-[13px] text-bakery-muted mt-0.5 leading-relaxed">Define los ingredientes, el rendimiento y el costo de la receta.</p>
        </div>
    </div>

    {{-- Errores --}}
    @if(session('error'))
        <div class="mb-8 bg-red-50 border border-red-200/60 text-red-700 rounded-xl px-5 py-3.5 text-[13px] leading-relaxed flex items-start gap-3" role="alert">
            <svg class="w-4 h-4 mt-0.5 flex-shrink-0 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
            </svg>
            {{ session('error') }}
        </div>
    @endif

    @if($errors->any())
        <div class="mb-8 bg-red-50 border border-red-200/60 text-red-700 rounded-xl px-5 py-3.5 text-[13px] leading-relaxed" role="alert">
            <p class="font-semibold mb-1">Errores de validaci&oacute;n:</p>
            <ul class="space-y-1 list-disc list-inside">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('recetas.store') }}" method="POST" @submit="submitting = true">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

            {{-- Columna principal --}}
            <div class="lg:col-span-2 space-y-8">

                {{-- Datos basicos --}}
                <div class="form-card">
                    <div class="mb-6">
                        <h2 class="section-title">Datos de la receta</h2>
                        <p class="section-subtitle">Informaci&oacute;n b&aacute;sica</p>
                    </div>

                    <div class="space-y-5">
                        <div>
                            <label class="form-label form-label-required" for="nombre">Nombre</label>
                            <input type="text" id="nombre" name="nombre" value="{{ old('nombre') }}"
                                   class="form-input" placeholder="Ej: Receta base croissant" required
                                   autocomplete="off">
                            @error('nombre') <p class="form-error">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="form-label form-label-required" for="categoria_receta_id">Categoria</label>
                            <select id="categoria_receta_id" name="categoria_receta_id" class="form-select" required>
                                <option value="">Seleccionar categoria...</option>
                                @foreach($categorias as $categoria)
                                    <option value="{{ $categoria->id }}" {{ old('categoria_receta_id') == $categoria->id ? 'selected' : '' }}>
                                        {{ $categoria->nombre }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="mt-2 flex items-center justify-between gap-3">
                                @error('categoria_receta_id') <p class="form-error mt-0">{{ $message }}</p> @enderror
                                <a href="{{ route('recetas.categorias.index') }}" class="ml-auto text-xs font-semibold text-bakery-gold hover:text-bakery-brown">
                                    Gestionar categorias
                                </a>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="form-label form-label-required" for="tiempo_preparacion">Tiempo de preparaci&oacute;n</label>
                                <div class="relative">
                                    <input type="number" id="tiempo_preparacion" name="tiempo_preparacion" value="{{ old('tiempo_preparacion') }}"
                                           class="form-input pr-12" placeholder="45" min="1" required>
                                    <span class="absolute right-4 top-1/2 -translate-y-1/2 text-xs text-bakery-muted/60 pointer-events-none">min</span>
                                </div>
                                @error('tiempo_preparacion') <p class="form-error">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="form-label form-label-required" for="lote_unidades">
                                    Unds. por lote
                                    <button type="button" @click="showBatchHelp = !showBatchHelp"
                                            class="inline-flex items-center justify-center w-4 h-4 rounded-full bg-bakery-amber/20 text-bakery-muted hover:text-bakery-gold hover:bg-bakery-amber/40 transition-colors ml-1"
                                            aria-label="Informaci&oacute;n sobre lotes"
                                            title="Qu&eacute; es un lote?">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z"/>
                                        </svg>
                                    </button>
                                </label>
                                <div class="relative">
                                    <input type="number" id="lote_unidades" name="lote_unidades" value="{{ old('lote_unidades') }}"
                                           x-model.number="loteUnidades"
                                           @input="calcularCostos()"
                                           class="form-input pr-14" placeholder="12" min="1" required
                                           aria-describedby="lote-unidades-help">
                                    <span class="absolute right-4 top-1/2 -translate-y-1/2 text-xs text-bakery-muted/60 pointer-events-none">uds</span>
                                </div>
                                <div id="lote-unidades-help" x-show="showBatchHelp" x-transition
                                     class="mt-2 bg-bakery-amber/10 border border-bakery-amber/20 rounded-lg px-3 py-2.5 text-xs text-bakery-dark leading-relaxed"
                                     role="tooltip">
                                    <p class="font-semibold mb-0.5">Qu&eacute; es un lote?</p>
                                    <p>Un lote es el grupo m&iacute;nimo de producci&oacute;n. Si defines <strong>12 unds. por lote</strong>, cada vez que produzcas 1 lote obtendr&aacute;s 12 unds. y se consumir&aacute;n los ingredientes indicados.</p>
                                </div>
                                @error('lote_unidades') <p class="form-error">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div>
                            <label class="form-label" for="descripcion">
                                Descripci&oacute;n
                                <span class="font-normal text-bakery-muted/50 text-xs ml-1">(opcional)</span>
                            </label>
                            <textarea id="descripcion" name="descripcion" rows="3"
                                      class="form-textarea"
                                      placeholder="Notas de preparaci&oacute;n, temperatura, tiempos de reposo...">{{ old('descripcion') }}</textarea>
                        </div>
                    </div>
                </div>

                {{-- Ingredientes --}}
                <div class="form-card">
                    <div class="flex items-start justify-between mb-2">
                        <div>
                            <h2 class="section-title mb-1">Ingredientes</h2>
                            <p class="section-subtitle">Ingredientes necesarios para producir 1 lote</p>
                        </div>
                        <button type="button" @click="agregarIngrediente()"
                                class="text-[13px] font-semibold text-bakery-gold hover:text-bakery-brown transition-colors"
                                aria-label="Agregar ingrediente">
                            + Agregar
                        </button>
                    </div>

                    {{-- Lote activo --}}
                    <div class="mb-5 rounded-xl border border-bakery-gold/20 bg-gradient-to-r from-bakery-gold/5 to-bakery-amber/10 px-4 py-3">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-bakery-gold/20 flex items-center justify-center flex-shrink-0">
                                <svg class="w-4 h-4 text-bakery-gold" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <p class="text-sm font-semibold text-bakery-dark">
                                    1 lote = <span x-text="loteUnidades || '-'" class="text-bakery-gold"></span> unds.
                                </p>
                                <p class="text-xs text-bakery-muted mt-0.5">
                                    Los ingredientes que definas aqu&iacute; se consumen <strong>por cada lote</strong> que produzcas
                                </p>
                            </div>
                        </div>
                    </div>

                    @error('ingredientes') <p class="form-error mb-4">{{ $message }}</p> @enderror

                    {{-- Aviso duplicado --}}
                    <div x-show="duplicadoDetectado" x-transition
                         class="mb-5 bg-amber-50 border border-amber-200/60 text-amber-800 rounded-xl px-4 py-3 text-[13px] leading-relaxed flex items-start gap-2"
                         role="alert">
                        <svg class="w-4 h-4 mt-0.5 flex-shrink-0 text-amber-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                        <span>Hay ingredientes repetidos. Cada ingrediente debe ser &uacute;nico en la receta.</span>
                    </div>

                    {{-- Filas --}}
                    <div class="space-y-3">
                        <template x-for="(ing, index) in ingredientes" :key="ing.key">
                            <div class="relative rounded-xl border border-bakery-amber/15 px-4 py-4 hover:border-bakery-amber/30 transition-colors"
                                 :class="{'border-red-300 bg-red-50/30': esDuplicado(index)}">

                                <button type="button" @click="eliminarIngrediente(index)"
                                        x-show="ingredientes.length > 1"
                                        class="absolute top-3 right-3 w-6 h-6 rounded-md flex items-center justify-center text-bakery-muted/30 hover:text-bakery-terracotta hover:bg-bakery-terracotta/10 transition-all"
                                        aria-label="Eliminar ingrediente">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>

                                <div class="grid grid-cols-12 gap-3 pr-6">
                                    <div class="col-span-7">
                                        <label class="text-[11px] font-semibold text-bakery-muted/70 uppercase tracking-wide mb-1.5 block">Ingrediente</label>
                                        <select :name="`ingredientes[${index}][ingrediente_id]`"
                                                x-model="ing.ingrediente_id"
                                                @change="calcularCostos()"
                                                class="form-select py-2.5 text-[13px]" required>
                                            <option value="">Seleccionar...</option>
                                            @foreach($ingredientes as $ingrediente)
                                            <option value="{{ $ingrediente->id }}">
                                                {{ $ingrediente->nombre }} ({{ \App\Support\UnitFormatter::short($ingrediente->unidad_medida) }})
                                            </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-span-5">
                                        <label class="text-[11px] font-semibold text-bakery-muted/70 uppercase tracking-wide mb-1.5 block">Cantidad por lote</label>
                                        <div class="relative">
                                            <input type="number" :name="`ingredientes[${index}][cantidad]`"
                                                   x-model.number="ing.cantidad"
                                                   @input="calcularCostos()"
                                                   step="0.0001" min="0.0001"
                                                   class="form-input py-2.5 text-[13px] pr-12"
                                                   placeholder="0.00" required
                                                   :aria-label="`Cantidad de ${nombreIngrediente(ing.ingrediente_id) || 'ingrediente'}`">
                                            <span class="absolute right-3.5 top-1/2 -translate-y-1/2 text-xs text-bakery-muted/50 pointer-events-none"
                                                  x-text="unidadIngrediente(ing.ingrediente_id)"></span>
                                        </div>
                                    </div>
                                </div>

                                <div x-show="ing.ingrediente_id && ing.cantidad" x-transition class="mt-3 pt-3 border-t border-bakery-amber/10 flex items-center justify-between">
                                    <div class="text-[11px] text-bakery-muted">
                                        <span x-text="costoUnitarioTexto(ing.ingrediente_id)"></span>
                                    </div>
                                    <div class="text-[13px] font-semibold text-bakery-dark tabular-nums"
                                         x-text="subtotalTexto(index)"></div>
                                </div>
                            </div>
                        </template>
                    </div>

                    <div x-show="ingredientes.length === 0" class="text-center py-8">
                        <svg class="mx-auto w-10 h-10 text-bakery-amber/30 mb-2" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25"/>
                        </svg>
                        <p class="text-[13px] text-bakery-muted">Sin ingredientes</p>
                        <button type="button" @click="agregarIngrediente()" class="text-[13px] text-bakery-gold hover:text-bakery-brown mt-1 underline underline-offset-2 decoration-bakery-gold/40">
                            Agregar primer ingrediente
                        </button>
                    </div>
                </div>
            </div>

            {{-- Lateral --}}
            <div class="space-y-8">

                {{-- Producto --}}
                <div class="form-card">
                    <div class="mb-6">
                        <h2 class="section-title">Siguiente paso</h2>
                        <p class="section-subtitle">Puedes asociar producto ahora o despu&eacute;s.</p>
                    </div>

                    <div class="mb-5 rounded-xl border border-bakery-sage/20 bg-bakery-sage/10 px-4 py-3">
                        <p class="text-sm font-semibold text-bakery-dark">Flujo recomendado</p>
                        <p class="mt-1 text-xs leading-relaxed text-bakery-muted">Guarda esta receta. Despu&eacute;s crea el producto con sus presentaciones y conecta la receta si hace falta.</p>
                    </div>

                    {{-- Alternar --}}
                    <div class="mb-5">
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" x-model="asociarProducto" class="sr-only peer">
                            <div class="w-9 h-5 bg-bakery-amber/20 peer-focus:ring-2 peer-focus:ring-bakery-gold/20 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-bakery-sage"></div>
                            <span class="ml-2.5 text-[13px] font-medium text-bakery-dark">Asociar ahora</span>
                        </label>
                        <p class="text-xs text-bakery-muted/60 mt-2 leading-relaxed">
                            Si no asocias, la receta quedar&aacute; como <span class="font-medium text-bakery-dark">independiente</span>
                        </p>
                    </div>

                    <div x-show="asociarProducto" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0">
                        <div class="space-y-5">
                            <div>
                                <label class="form-label" for="producto_id">Producto</label>
                                <select id="producto_id" name="producto_id" x-model="productoId" @change="filtrarPresentaciones()"
                                        class="form-select">
                                    <option value="">Seleccionar producto...</option>
                                    @foreach($productos as $prod)
                                        <option value="{{ $prod->id }}"
                                                {{ old('producto_id', $producto?->id) == $prod->id ? 'selected' : '' }}>
                                            {{ $prod->nombre }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('producto_id') <p class="form-error">{{ $message }}</p> @enderror
                            </div>

                            <div x-show="presentacionesFiltradas.length > 0" x-transition>
                                <label class="form-label">
                                    Presentaci&oacute;n
                                    <span class="font-normal text-bakery-muted/50 text-xs ml-1">(opcional)</span>
                                </label>
                                <select name="presentacion_id" x-model="presentacionId" class="form-select">
                                    <option value="">General para todas</option>
                                    <template x-for="pres in presentacionesFiltradas" :key="pres.id">
                                        <option :value="pres.id" x-text="pres.nombre"></option>
                                    </template>
                                </select>
                                <p class="text-xs text-bakery-muted/60 mt-2 leading-relaxed">
                                    Si no seleccionas, ser&aacute; la receta general del producto
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Costos --}}
                <div class="form-card bg-bakery-warm border-bakery-amber/30">
                    <div class="mb-4">
                        <h2 class="section-title">Costo estimado</h2>
                        <p class="section-subtitle">Desglose por ingrediente</p>
                    </div>

                    <div class="space-y-3">
                        <template x-for="(ing, index) in ingredientes.filter(i => i.ingrediente_id && i.cantidad)" :key="'cost-' + index">
                            <div class="space-y-1">
                                <div class="flex items-center justify-between text-[13px]">
                                    <span class="text-bakery-muted truncate" x-text="nombreIngrediente(ing.ingrediente_id)"></span>
                                    <span class="font-medium tabular-nums ml-2" x-text="subtotalTexto(index)"></span>
                                </div>
                                <div class="h-1 rounded-full bg-bakery-amber/10 overflow-hidden">
                                    <div class="h-full rounded-full bg-bakery-gold/50 transition-all duration-300"
                                         :style="`width: ${costoLote > 0 ? ((costosDB[ing.ingrediente_id] || 0) * (ing.cantidad || 0) / costoLote * 100) : 0}%`"></div>
                                </div>
                            </div>
                        </template>

                        <div x-show="costoLote > 0" class="pt-4 border-t border-bakery-amber/20 space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-semibold text-bakery-muted uppercase">Costo por lote</span>
                                <span class="text-lg font-bold text-bakery-gold tabular-nums" x-text="formatMoney(costoLote)"></span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-semibold text-bakery-muted uppercase">Costo por und.</span>
                                <span class="text-sm font-semibold text-bakery-dark tabular-nums" x-text="formatMoney(costoUnidad)"></span>
                            </div>
                            <div class="pt-2 border-t border-bakery-amber/10">
                                <p class="text-[11px] text-bakery-muted/70 leading-relaxed">
                                    <span x-text="loteUnidades || '-'"></span> unds. por lote &middot;
                                    <span x-text="ingredientes.filter(i => i.ingrediente_id && i.cantidad).length"></span> ingredientes
                                </p>
                            </div>
                        </div>

                        <div x-show="costoLote === 0" class="text-center py-3">
                            <svg class="mx-auto w-8 h-8 text-bakery-amber/25 mb-1.5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <p class="text-xs text-bakery-muted">Agrega ingredientes para ver el costo</p>
                        </div>
                    </div>
                </div>

                {{-- Info --}}
                <div class="px-1">
                    <p class="text-[13px] text-bakery-muted/60 leading-relaxed">
                        Las cantidades definidas se descuentan del inventario <strong>por cada lote</strong> que produzcas.
                    </p>
                </div>
            </div>
        </div>

        {{-- Acciones --}}
        <div class="sticky bottom-0 mt-10 bg-bakery-cream/95 backdrop-blur-md border-t border-bakery-amber/10 -mx-6 px-6 py-4 flex items-center justify-between">
            <p class="text-xs text-bakery-muted/50 hidden sm:block">
                <span class="text-bakery-terracotta">*</span> campos obligatorios
            </p>
            <div class="flex items-center gap-3 ml-auto">
                <a href="{{ route('recetas.index') }}" class="btn-secondary">Cancelar</a>
                <button type="submit" :disabled="submitting || duplicadoDetectado"
                        class="btn-primary disabled:opacity-50 disabled:cursor-not-allowed"
                        :title="duplicadoDetectado ? 'Elimina los ingredientes duplicados para continuar' : ''"
                        :aria-disabled="duplicadoDetectado ? 'true' : 'false'">
                    <svg x-show="submitting" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    <span x-text="submitting ? 'Guardando...' : 'Guardar receta'"></span>
                </button>
            </div>
        </div>
    </form>
</div>

<script>
function recetaForm() {
    let key = 1;
    const ingredientesDB = @json($ingredientes->mapWithKeys(fn ($i) => [$i->id => \App\Support\UnitFormatter::short($i->unidad_medida)]));
    const nombresDB = @json($ingredientes->mapWithKeys(fn ($i) => [$i->id => $i->nombre]));
    const costosDB = @json($costosIngredientes);

    return {
        submitting: false,
        showBatchHelp: false,
        asociarProducto: {{ old('producto_id', $producto?->id) ? 'true' : 'false' }},
        productoId: '{{ old('producto_id', $producto?->id ?? '') }}',
        presentacionId: '{{ old('presentacion_id', '') }}',
        loteUnidades: {{ old('lote_unidades', 1) ?: 1 }},
        ingredientes: [{ key: 0, ingrediente_id: '', cantidad: null }],
        presentacionesPorProducto: @json($presentacionesPorProducto),
        presentacionesFiltradas: [],
        costoLote: 0,
        costoUnidad: 0,
        costosDB: costosDB,

        init() {
            this.filtrarPresentaciones();
            this.calcularCostos();
            const input = document.getElementById('lote_unidades');
            if (input) {
                this.loteUnidades = parseInt(input.value) || 1;
                input.addEventListener('input', () => {
                    this.loteUnidades = parseInt(input.value) || 1;
                    this.calcularCostos();
                });
            }
        },

        get duplicadoDetectado() {
            const ids = this.ingredientes.map(i => i.ingrediente_id).filter(Boolean);
            return ids.length !== new Set(ids).size;
        },

        esDuplicado(index) {
            const currentId = this.ingredientes[index]?.ingrediente_id;
            if (!currentId) return false;
            return this.ingredientes.some((ing, i) => i !== index && ing.ingrediente_id === currentId);
        },

        unidadIngrediente(id) {
            return id ? (ingredientesDB[id] || '') : '';
        },

        nombreIngrediente(id) {
            return id ? (nombresDB[id] || '') : '';
        },

        costoUnitarioTexto(id) {
            if (!id || !costosDB[id]) return '';
            return `${window.RochaFormat.unitCost(Number(costosDB[id]))} / ${this.unidadIngrediente(id)}`;
        },

        subtotalTexto(index) {
            const ing = this.ingredientes[index];
            if (!ing || !ing.ingrediente_id || !ing.cantidad) return '';
            const costo = costosDB[ing.ingrediente_id] || 0;
            const subtotal = costo * ing.cantidad;
            return window.RochaFormat.money(subtotal);
        },

        formatMoney(value) {
            return window.RochaFormat.money(Number(value || 0));
        },

        calcularCostos() {
            let total = 0;
            this.ingredientes.forEach(ing => {
                if (ing.ingrediente_id && ing.cantidad) {
                    const costo = costosDB[ing.ingrediente_id] || 0;
                    total += costo * ing.cantidad;
                }
            });
            this.costoLote = total;
            this.costoUnidad = this.loteUnidades > 0 ? total / this.loteUnidades : 0;
        },

        filtrarPresentaciones() {
            if (!this.productoId) {
                this.presentacionesFiltradas = [];
                this.presentacionId = '';
                return;
            }
            this.presentacionesFiltradas = this.presentacionesPorProducto[this.productoId] || [];
            if (!this.presentacionesFiltradas.find(p => p.id == this.presentacionId)) {
                this.presentacionId = '';
            }
        },

        agregarIngrediente() {
            this.ingredientes.push({ key: key++, ingrediente_id: '', cantidad: null });
        },

        eliminarIngrediente(index) {
            if (this.ingredientes.length > 1) {
                this.ingredientes.splice(index, 1);
                this.calcularCostos();
            }
        },
    }
}
</script>
</x-app-layout>
