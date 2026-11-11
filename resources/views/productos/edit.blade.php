<x-app-layout>
@php
    $presentacionesIniciales = old('presentaciones')
        ? collect(old('presentaciones'))->values()
        : $producto->presentaciones->map(fn ($p) => [
            'id' => $p->id,
            'nombre' => $p->nombre,
            'cantidad_unidades' => $p->cantidad_unidades,
            'precio' => $p->precio,
            'tipo_venta' => $p->tipo_venta,
            'activo' => $p->activo,
        ])->values();
@endphp

<div class="py-8 px-6 max-w-6xl mx-auto" x-data="productoEditForm()" x-cloak>
    <div x-show="submitting" x-transition.opacity class="fixed inset-0 bg-bakery-dark/30 backdrop-blur-sm z-50 flex items-center justify-center">
        <div class="bg-white rounded-2xl px-6 py-5 shadow-2xl flex items-center gap-4">
            <div class="relative h-5 w-5">
                <div class="absolute inset-0 rounded-full border-2 border-bakery-amber/20"></div>
                <div class="absolute inset-0 rounded-full border-2 border-transparent border-t-bakery-gold animate-spin"></div>
            </div>
            <span class="text-sm font-semibold text-bakery-dark">Guardando...</span>
        </div>
    </div>

    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between mb-6">
        <div class="flex items-start gap-3">
            <a href="{{ route('productos.index') }}"
               class="mt-0.5 text-bakery-muted/50 hover:text-bakery-muted transition p-1 rounded-lg hover:bg-bakery-warm"
               aria-label="Volver a productos">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <div>
                <h2 class="text-2xl font-display font-bold text-bakery-dark">Editar producto</h2>
                <p class="text-sm text-bakery-muted mt-0.5">{{ $producto->nombre }}</p>
            </div>
        </div>
        @if($producto->activo)
            <span class="badge-success">Activo</span>
        @else
            <span class="badge-neutral">Inactivo</span>
        @endif
    </div>

    @if($errors->any())
        <div class="mb-6 bg-bakery-terracotta/10 border border-bakery-terracotta/20 text-bakery-terracotta text-sm rounded-xl px-4 py-3" role="alert">
            <ul class="list-disc list-inside space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('productos.update', $producto) }}" method="POST" class="space-y-6" @submit="submitting = true">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-6">
                <div class="form-card">
                    <div class="mb-5">
                        <h3 class="section-title mb-1">Datos del producto</h3>
                        <p class="section-hint">Nombre comercial y unidad base.</p>
                    </div>

                    <div class="space-y-4">
                        <div>
                            <label class="form-label form-label-required" for="nombre">Nombre</label>
                            <input type="text" id="nombre" name="nombre"
                                   value="{{ old('nombre', $producto->nombre) }}"
                                   class="form-input" required>
                            @error('nombre') <p class="form-error">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="form-label">Unidad base</label>
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                                @php $unidades = ['unidad' => 'und.', 'g' => 'Gramos', 'oz' => 'Onzas', 'ml' => 'Mililitros']; @endphp
                                @foreach($unidades as $valor => $etiqueta)
                                    <label class="relative">
                                        <input type="radio" name="tipo_unidad" value="{{ $valor }}"
                                               {{ old('tipo_unidad', $producto->tipo_unidad) === $valor ? 'checked' : '' }}
                                               class="peer sr-only">
                                        <div class="text-center py-2 px-3 rounded-xl border border-bakery-amber/20 peer-checked:border-bakery-gold peer-checked:bg-bakery-gold/10 peer-checked:text-bakery-brown text-sm font-medium text-bakery-muted hover:bg-bakery-warm cursor-pointer transition">
                                            {{ $etiqueta }}
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                            @error('tipo_unidad') <p class="form-error">{{ $message }}</p> @enderror
                        </div>

                        <div class="flex items-center gap-2 pt-2">
                            <input type="hidden" name="activo" value="0">
                            <input type="checkbox" name="activo" id="activo" value="1"
                                   {{ old('activo', $producto->activo) ? 'checked' : '' }}
                                   class="rounded border-bakery-amber/30 text-bakery-gold focus:ring-bakery-gold">
                            <label for="activo" class="text-sm text-bakery-dark">Producto activo</label>
                        </div>
                    </div>
                </div>

                <div class="form-card">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between mb-4">
                        <div>
                            <h3 class="section-title mb-0">Presentaciones</h3>
                            <p class="section-hint">Cada fila es una forma de venta.</p>
                        </div>
                        <button type="button" @click="agregarPresentacion()"
                                class="btn-ghost text-bakery-gold hover:text-bakery-brown hover:bg-bakery-gold/10"
                                aria-label="Agregar presentaci&oacute;n">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                            </svg>
                            Agregar
                        </button>
                    </div>

                    @error('presentaciones') <p class="form-error mb-3">{{ $message }}</p> @enderror

                    <div class="space-y-3">
                        <template x-for="(pres, index) in presentaciones" :key="pres.key">
                            <div class="data-row">
                                <input type="hidden" :name="`presentaciones[${index}][id]`" :value="pres.id || ''">
                                <input type="hidden" :name="`presentaciones[${index}][activo]`" value="0">

                                <button type="button" @click="eliminarPresentacion(index)"
                                        x-show="presentaciones.length > 1"
                                        class="data-row-remove"
                                        aria-label="Eliminar presentaci&oacute;n">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>

                                <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                                    <div>
                                        <label class="text-xs font-medium text-bakery-muted mb-1 block">Nombre <span class="text-bakery-terracotta font-bold">*</span></label>
                                        <input type="text" :name="`presentaciones[${index}][nombre]`"
                                               x-model="pres.nombre"
                                               class="form-input py-2 px-3" required>
                                    </div>
                                    <div>
                                        <label class="text-xs font-medium text-bakery-muted mb-1 block">Cantidad</label>
                                        <input type="number" :name="`presentaciones[${index}][cantidad_unidades]`"
                                               x-model.number="pres.cantidad_unidades"
                                               :disabled="pres.tipo_venta === 'unidad'"
                                               step="0.0001" min="0.0001"
                                               class="form-input py-2 px-3"
                                               :class="pres.tipo_venta === 'unidad' ? 'bg-bakery-warm/60 cursor-not-allowed' : ''">
                                    </div>
                                    <div>
                                        <label class="text-xs font-medium text-bakery-muted mb-1 block">Precio ($)</label>
                                        <input type="number" :name="`presentaciones[${index}][precio]`"
                                               x-model.number="pres.precio"
                                               step="0.01" min="0"
                                               class="form-input py-2 px-3" required>
                                    </div>
                                    <div>
                                        <label class="text-xs font-medium text-bakery-muted mb-1 block">Tipo</label>
                                        <select :name="`presentaciones[${index}][tipo_venta]`"
                                                x-model="pres.tipo_venta"
                                                @change="normalizarCantidad(pres)"
                                                class="form-select py-2 px-3 text-xs">
                                            <option value="unidad">Por und.</option>
                                            <option value="peso">Por peso</option>
                                            <option value="porcion">Por porci&oacute;n</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="flex flex-wrap items-center gap-3">
                                    <label class="inline-flex items-center gap-2 text-xs text-bakery-muted">
                                        <input type="checkbox" :name="`presentaciones[${index}][activo]`"
                                               value="1" x-model="pres.activo"
                                               class="rounded border-bakery-amber/30 text-bakery-gold focus:ring-bakery-gold">
                                        Activa
                                    </label>
                                    <span class="text-[11px] text-bakery-muted/60" x-show="pres.tipo_venta === 'unidad'">Cantidad fija: 1 und.</span>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <div class="space-y-6">
                <div class="form-card">
                    <div class="flex items-start justify-between gap-3 mb-4">
                        <div>
                            <h3 class="section-title mb-0">Receta general</h3>
                            <p class="section-hint">Base de costo e inventario.</p>
                        </div>
                        @if($producto->recetaDefault)
                            <a href="{{ route('recetas.edit', $producto->recetaDefault) }}" class="btn-ghost text-bakery-gold hover:text-bakery-brown hover:bg-bakery-gold/10 text-xs">Editar</a>
                        @else
                            <a href="{{ route('recetas.create') }}?producto_id={{ $producto->id }}" class="btn-ghost text-bakery-gold hover:text-bakery-brown hover:bg-bakery-gold/10 text-xs">Crear</a>
                        @endif
                    </div>

                    @if($producto->recetaDefault)
                        <div class="rounded-xl border border-bakery-amber/20 bg-bakery-warm px-4 py-3">
                            <div class="flex items-center justify-between gap-3">
                                <p class="text-sm font-semibold text-bakery-dark">{{ $producto->recetaDefault->nombre }}</p>
                                @if($producto->recetaDefault->activo)
                                    <span class="badge-success">Activa</span>
                                @else
                                    <span class="badge-neutral">Inactiva</span>
                                @endif
                            </div>
                            <p class="mt-2 text-xs text-bakery-muted">{{ $producto->recetaDefault->detallesConIngredientes->count() }} ingredientes registrados.</p>
                        </div>
                    @else
                        <div class="empty-state py-5">
                            <svg class="empty-state-icon" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25"/>
                            </svg>
                            <p class="empty-state-text">Este producto a&uacute;n no tiene receta.</p>
                        </div>
                        <div class="mt-4">
                            <label class="form-label" for="receta_id">Asociar receta</label>
                            <select id="receta_id" name="receta_id" class="form-select">
                                <option value="">Mantener sin receta</option>
                                @foreach($recetasIndependientes as $receta)
                                    <option value="{{ $receta->id }}" {{ old('receta_id') == $receta->id ? 'selected' : '' }}>
                                        {{ $receta->nombre }} ({{ $receta->lote_unidades }} unds.)
                                    </option>
                                @endforeach
                            </select>
                            @error('receta_id') <p class="form-error">{{ $message }}</p> @enderror
                            <p class="mt-2 text-xs leading-relaxed text-bakery-muted">Usa una receta independiente creada antes del producto.</p>
                        </div>
                    @endif
                </div>

                <div class="form-card bg-bakery-warm/70">
                    <h3 class="section-title">Control de flujo</h3>
                    <div class="space-y-3 text-sm leading-relaxed text-bakery-muted">
                        <p>La receta calcula consumo y costo.</p>
                        <p>El producto define c&oacute;mo se vende.</p>
                        <p>Producci&oacute;n agrega stock vendible.</p>
                    </div>
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
                <span x-text="submitting ? 'Guardando...' : 'Guardar cambios'"></span>
            </button>
        </div>
    </form>
</div>

<script>
function productoEditForm() {
    let nextKey = {{ $presentacionesIniciales->count() }};
    const iniciales = @json($presentacionesIniciales);

    return {
        submitting: false,
        presentaciones: iniciales.map((p, index) => ({
            key: index,
            id: p.id || '',
            nombre: p.nombre || '',
            cantidad_unidades: p.cantidad_unidades || 1,
            precio: p.precio || null,
            tipo_venta: p.tipo_venta || 'unidad',
            activo: p.activo === undefined ? true : Boolean(Number(p.activo)),
        })),

        init() {
            if (this.presentaciones.length === 0) {
                this.agregarPresentacion();
            }
            this.presentaciones.forEach((pres) => this.normalizarCantidad(pres));
        },

        agregarPresentacion() {
            this.presentaciones.push({
                key: nextKey++,
                id: '',
                nombre: '',
                cantidad_unidades: 1,
                precio: null,
                tipo_venta: 'unidad',
                activo: true,
            });
        },

        eliminarPresentacion(index) {
            if (this.presentaciones.length > 1) {
                this.presentaciones.splice(index, 1);
            }
        },

        normalizarCantidad(pres) {
            if (pres.tipo_venta === 'unidad') {
                pres.cantidad_unidades = 1;
            }
        },
    }
}
</script>
</x-app-layout>
