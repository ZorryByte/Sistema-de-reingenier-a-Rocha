<x-app-layout>
<div class="py-8 px-6 max-w-6xl mx-auto" x-data="productoForm()" x-cloak>

    {{-- Cargando --}}
    <div x-show="submitting" x-transition.delay.200ms class="fixed inset-0 bg-bakery-dark/30 backdrop-blur-sm z-50 flex items-center justify-center">
        <div class="bg-white rounded-2xl p-6 shadow-2xl flex items-center gap-4">
            <svg class="animate-spin w-8 h-8 text-bakery-gold" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
            <span class="text-bakery-dark font-semibold">Guardando producto...</span>
        </div>
    </div>

    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('productos.index') }}"
           class="text-bakery-muted/50 hover:text-bakery-muted transition p-1 rounded-lg hover:bg-bakery-warm"
           aria-label="Volver a productos">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
            </svg>
        </a>
        <div>
            <h2 class="text-2xl font-display font-bold text-bakery-dark">Nuevo producto</h2>
            <p class="text-sm text-bakery-muted mt-0.5">Configura el precio de venta y las presentaciones del producto.</p>
        </div>
    </div>

    @if($errors->any())
        <div class="mb-4 bg-bakery-terracotta/10 border border-bakery-terracotta/20 text-bakery-terracotta text-sm rounded-xl px-4 py-3">
            <ul class="list-disc list-inside space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Campos requeridos --}}
    <p class="text-xs text-bakery-muted mb-4 italic">Los campos marcados con * son obligatorios</p>

    <form action="{{ route('productos.store') }}" method="POST" class="space-y-6" @submit="submitting = true">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <div class="lg:col-span-2 space-y-6">

                <div class="form-card">
                    <h3 class="section-title">Datos del producto</h3>

                    <div class="space-y-4">
                        <div>
                            <label class="form-label form-label-required" for="nombre">Nombre</label>
                            <input type="text" id="nombre" name="nombre" value="{{ old('nombre') }}"
                                   class="form-input" placeholder="Ej: Croissant de mantequilla" required>
                            @error('nombre') <p class="form-error">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="form-label">Unidad base</label>
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                                @php $unidades = ['unidad' => 'und.', 'g' => 'Gramos', 'oz' => 'Onzas', 'ml' => 'Mililitros']; @endphp
                                @foreach($unidades as $valor => $etiqueta)
                                <label class="relative">
                                    <input type="radio" name="tipo_unidad" value="{{ $valor }}"
                                           {{ old('tipo_unidad') === $valor ? 'checked' : ($loop->first ? 'checked' : '') }}
                                           class="peer sr-only">
                                    <div class="text-center py-2 px-3 rounded-xl border border-bakery-amber/20
                                                peer-checked:border-amber-400 peer-checked:bg-bakery-gold/10 peer-checked:text-bakery-brown
                                                text-sm font-medium text-bakery-muted
                                                hover:bg-bakery-warm cursor-pointer transition">
                                        {{ $etiqueta }}
                                    </div>
                                </label>
                                @endforeach
                            </div>
                            @error('tipo_unidad') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                <div class="form-card">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="section-title mb-0">Presentaciones</h3>
                            <p class="section-hint">Nombre, precio y tipo de presentaci&oacute;n. Stock se agrega desde Producci&oacute;n.</p>
                        </div>
                        <button type="button" @click="agregarPresentacion"
                                class="btn-ghost text-bakery-gold hover:text-bakery-brown hover:bg-bakery-gold/10"
                                aria-label="Agregar presentación">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                            </svg>
                            Agregar
                        </button>
                    </div>

                    @error('presentaciones') <p class="form-error mb-3">{{ $message }}</p> @enderror

                    <div class="space-y-3">
                        <template x-for="(pres, index) in presentaciones" :key="pres.key">
                            <div class="data-row"
                                 x-transition:enter="fade-enter"
                                 x-transition:enter-start="opacity-0 scale-95"
                                 x-transition:enter-end="opacity-100 scale-100"
                                 x-transition:leave="fade-leave"
                                 x-transition:leave-start="opacity-100 scale-100"
                                 x-transition:leave-end="opacity-0 scale-95">

                                <button type="button" @click="eliminarPresentacion(index)"
                                        x-show="presentaciones.length > 1"
                                        class="data-row-remove"
                                        aria-label="Eliminar presentación">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>

                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                    <div>
                                        <label class="text-xs font-medium text-bakery-muted mb-1 block">
                                            Nombre <span class="text-bakery-terracotta font-bold">*</span>
                                        </label>
                                        <input type="text" :name="`presentaciones[${index}][nombre]`"
                                               x-model="pres.nombre"
                                               class="form-input py-2 px-3"
                                               placeholder="Ej: und.">
                                    </div>
                                    <div>
                                        <label class="text-xs font-medium text-bakery-muted mb-1 block">Precio ($) <span class="text-bakery-terracotta font-bold">*</span></label>
                                        <input type="number" :name="`presentaciones[${index}][precio]`"
                                               x-model.number="pres.precio"
                                               step="0.01" min="0"
                                               class="form-input py-2 px-3"
                                               placeholder="0.00">
                                    </div>
                                    <div>
                                        <label class="text-xs font-medium text-bakery-muted mb-1 block">
                                            Unid. por present.
                                            <span class="text-bakery-amber/60 font-normal">(para receta)</span>
                                        </label>
                                        <input type="number"
                                               :name="`presentaciones[${index}][cantidad_unidades]`"
                                               x-model.number="pres.cantidad"
                                               :disabled="pres.tipo_venta === 'unidad'"
                                               step="1" min="1"
                                               class="form-input py-2 px-3 text-xs"
                                               :class="pres.tipo_venta === 'unidad' ? 'bg-bakery-warm/50 cursor-not-allowed' : ''"
                                               placeholder="12">
                                        <p class="text-[10px] text-bakery-muted/60 mt-1">
                                            <template x-if="pres.tipo_venta === 'unidad'">
                                                <span>Por und. = 1</span>
                                            </template>
                                        </p>
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 items-end">
                                    <div class="sm:col-span-2">
                                        <label class="text-xs font-medium text-bakery-muted mb-1 block">Tipo de venta</label>
                                        <select :name="`presentaciones[${index}][tipo_venta]`"
                                                x-model="pres.tipo_venta"
                                                class="form-select py-2 px-3 text-xs"
                                                @change="pres.tipo_venta === 'unidad' ? pres.cantidad = 1 : pres.cantidad = pres.cantidad || null">
                                            <option value="unidad">Por und.</option>
                                            <option value="peso">Por peso</option>
                                            <option value="porcion">Por porción</option>
                                        </select>
                                    </div>

                                    <div>
                                        <div class="text-xs font-medium text-bakery-muted mb-1">Stock</div>
                                        <div class="flex items-center gap-2 px-3 py-2 bg-bakery-warm rounded-xl border border-bakery-amber/10">
                                            <span class="w-2 h-2 rounded-full bg-bakery-terracotta/40"></span>
                                            <span class="text-xs text-bakery-muted">0</span>
                                            <span class="text-[10px] text-bakery-muted/60 ml-auto">Producci&oacute;n</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <div class="space-y-6">
                <div class="form-card bg-bakery-warm/70">
                    <h3 class="section-title">Receta general</h3>
                    <label class="form-label" for="receta_id">Receta creada</label>
                    <select id="receta_id" name="receta_id" class="form-select">
                        <option value="">Sin asociar por ahora</option>
                        @foreach($recetasIndependientes as $receta)
                            <option value="{{ $receta->id }}" {{ old('receta_id') == $receta->id ? 'selected' : '' }}>
                                {{ $receta->nombre }} ({{ $receta->lote_unidades }} unds.)
                            </option>
                        @endforeach
                    </select>
                    @error('receta_id') <p class="form-error">{{ $message }}</p> @enderror
                    @if($recetasIndependientes->isEmpty())
                        <p class="mt-2 text-xs leading-relaxed text-bakery-muted">No hay recetas independientes activas para asociar.</p>
                    @else
                        <p class="mt-2 text-xs leading-relaxed text-bakery-muted">Selecciona la receta base del producto.</p>
                    @endif

                    <div class="my-5 border-t border-bakery-amber/20"></div>

                    <h3 class="section-title">Antes de guardar</h3>
                    <div class="space-y-3 text-sm leading-relaxed text-bakery-muted">
                        <p><span class="font-semibold text-bakery-dark">Receta:</span> debe existir primero para calcular costos y descontar ingredientes.</p>
                        <p><span class="font-semibold text-bakery-dark">Presentaciones:</span> definen c&oacute;mo se vende el producto.</p>
                        <p><span class="font-semibold text-bakery-dark">Stock:</span> se carga desde producci&oacute;n, no desde este formulario.</p>
                    </div>
                    <a href="{{ route('recetas.create') }}" class="btn-secondary mt-5 w-full">Crear receta primero</a>
                </div>
            </div>
        </div>

        <div class="sticky bottom-0 bg-bakery-cream/95 backdrop-blur-sm border-t border-bakery-amber/20 -mx-6 px-6 py-4 flex justify-end gap-3">
            <a href="{{ route('productos.index') }}" class="btn-secondary">Cancelar</a>
            <button type="submit" :disabled="submitting" class="btn-primary disabled:opacity-60 disabled:cursor-not-allowed">
                <svg x-show="!submitting" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                </svg>
                <svg x-show="submitting" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                <span x-text="submitting ? 'Guardando...' : 'Guardar producto'"></span>
            </button>
        </div>
    </form>
</div>

<script>
function productoForm() {
    let presKey = {{ old('presentaciones') ? count(old('presentaciones')) : 1 }};

    return {
        submitting: false,
        presentaciones: {!! json_encode(old('presentaciones') ?? [['nombre' => '', 'cantidad_unidades' => 1, 'precio' => null, 'tipo_venta' => 'unidad']]) !!}.map((p, i) => ({ key: i, nombre: p.nombre || '', cantidad: p.cantidad_unidades || 1, precio: p.precio || null, tipo_venta: p.tipo_venta || 'unidad' })),

        agregarPresentacion() {
            this.presentaciones.push({ key: presKey++, nombre: '', cantidad: 1, precio: null, tipo_venta: 'unidad' });
        },

        eliminarPresentacion(index) {
            if (this.presentaciones.length > 1) {
                this.presentaciones.splice(index, 1);
            }
        },
    }
}
</script>
</x-app-layout>
