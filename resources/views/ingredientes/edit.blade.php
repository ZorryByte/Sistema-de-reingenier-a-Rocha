<x-app-layout>
<div class="py-8 px-6 max-w-4xl mx-auto" x-data="ingredienteForm()">

    <div x-show="submitting" x-transition.delay.200ms class="fixed inset-0 bg-bakery-dark/30 backdrop-blur-sm z-50 flex items-center justify-center">
        <div class="bg-white rounded-2xl p-6 shadow-2xl flex items-center gap-4">
            <svg class="animate-spin w-8 h-8 text-bakery-gold" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
            <span class="text-bakery-dark font-semibold">Guardando ingrediente...</span>
        </div>
    </div>

    {{-- Header --}}
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('ingredientes.index') }}"
           class="text-bakery-muted/50 hover:text-bakery-muted transition p-1 rounded-lg hover:bg-bakery-warm"
           aria-label="Volver a ingredientes">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
            </svg>
        </a>
        <div class="flex-1">
            <h2 class="text-2xl font-display font-bold text-bakery-dark">Editar ingrediente</h2>
            <p class="text-sm text-bakery-muted mt-0.5">{{ $ingrediente->nombre }}</p>
        </div>
        @if($ingrediente->stockBajo())
            <span class="badge-danger">Stock bajo</span>
        @endif
    </div>

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

    @if(session('success'))
        <div class="mb-4 flash-success rounded-lg px-4 py-3 text-sm" role="status">{{ session('success') }}</div>
    @endif

    <form action="{{ route('ingredientes.update', $ingrediente) }}" method="POST" class="space-y-6" @submit="submitting = true">
        @csrf @method('PUT')

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            {{-- Columna principal --}}
            <div class="lg:col-span-2 space-y-6">

                {{-- Datos del ingrediente --}}
                <div class="form-card">
                    <h3 class="section-title">Datos del ingrediente</h3>

                    <div class="space-y-4">
                        <div>
                            <label class="form-label" for="nombre">Nombre</label>
                            <input type="text" id="nombre" name="nombre"
                                   value="{{ old('nombre', $ingrediente->nombre) }}"
                                   class="form-input" required>
                            @error('nombre') <p class="form-error">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="form-label form-label-required" for="categoria">Categoria</label>
                            <select id="categoria" name="categoria" class="form-select" required>
                                <option value="" disabled>Selecciona una categoria</option>
                                @php
                                    $cats = [
                                        'harinas' => 'Harinas',
                                        'lacteos' => 'Lácteos',
                                        'endulzantes' => 'Endulzantes',
                                        'leudantes' => 'Leudantes',
                                        'grasas' => 'Grasas',
                                        'saborizantes' => 'Saborizantes',
                                        'decoracion' => 'Decoración',
                                        'empaques' => 'Empaques',
                                        'refrigerados' => 'Refrigerados',
                                        'congelados' => 'Congelados',
                                        'otros' => 'Otros',
                                    ];
                                @endphp
                                @foreach($cats as $val => $label)
                                <option value="{{ $val }}" {{ old('categoria', $ingrediente->categoria) === $val ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('categoria') <p class="form-error">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="form-label">Unidad de medida</label>
                            <div class="grid grid-cols-3 sm:grid-cols-4 gap-2">
                                @php
                                    $unidades = [
                                        'g' => 'Gramos',
                                        'oz' => 'Onzas',
                                        'ml' => 'Mililitros',
                                        'unidad' => 'und.',
                                    ];
                                @endphp
                                @foreach($unidades as $valor => $etiqueta)
                                <label class="relative">
                                    <input type="radio" name="unidad_medida" value="{{ $valor }}"
                                           {{ old('unidad_medida', $ingrediente->unidad_medida) === $valor ? 'checked' : '' }}
                                           x-model="unidad"
                                           class="peer sr-only">
                                    <div class="text-center py-2 px-2 rounded-xl border border-bakery-amber/20
                                                peer-checked:border-bakery-gold peer-checked:bg-bakery-gold/10 peer-checked:text-bakery-brown
                                                text-xs font-medium text-bakery-muted
                                                hover:bg-bakery-warm cursor-pointer transition">
                                        {{ $etiqueta }}
                                    </div>
                                </label>
                                @endforeach
                            </div>
                            @error('unidad_medida') <p class="form-error">{{ $message }}</p> @enderror
                        </div>

                        <x-proveedor-combobox
                            :selected="$proveedorSeleccionado"
                            contexto="ingrediente_edit"
                            :ingrediente-id="$ingrediente->id"
                            draft-key="ingrediente-edit-draft-{{ $ingrediente->id }}"
                        />
                    </div>
                </div>

                {{-- Stock y costo --}}
                <div class="form-card">
                    <h3 class="section-title">Stock y costo</h3>
                    <p class="text-xs text-bakery-muted mb-4">El costo unitario se recalcula automaticamente al cambiar precio o stock.</p>

                    <div class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="form-label form-label-required" for="stock_actual">Stock actual</label>
                                <input type="number" id="stock_actual" name="stock_actual"
                                       x-model="stock_actual"
                                       value="{{ old('stock_actual', $ingrediente->stock_actual) }}"
                                       step="0.01" min="0"
                                       class="form-input">
                                @error('stock_actual') <p class="form-error">{{ $message }}</p> @enderror
                                <p class="text-xs text-bakery-muted/50 mt-1">Cambio genera ajuste.</p>
                            </div>
                            <div>
                                <label class="form-label form-label-required" for="stock_minimo">Stock minimo</label>
                                <input type="number" id="stock_minimo" name="stock_minimo"
                                       value="{{ old('stock_minimo', $ingrediente->stock_minimo) }}"
                                       step="0.01" min="0"
                                       class="form-input">
                                @error('stock_minimo') <p class="form-error">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="form-label" for="precio">Precio de compra ($)</label>
                                <input type="number" id="precio" name="precio"
                                       x-model="precio"
                                       value="{{ old('precio', $ingrediente->precio) }}"
                                       step="0.01" min="0"
                                       class="form-input">
                                <p class="text-xs text-bakery-muted/50 mt-1">Precio de referencia para futuras compras.</p>
                            </div>
                            <div>
                                <input type="hidden" name="costo_unitario" :value="parseFloat(costoUnitCalc) || 0">
                                <label class="form-label" for="costo_unitario">Costo unitario ($/<span x-text="unidad"></span>)</label>
                                <input type="text" id="costo_unitario"
                                       :value="costoUnitCalc ? parseFloat(costoUnitCalc).toFixed(4) : ''"
                                       readonly
                                       class="form-input bg-bakery-warm/50 cursor-not-allowed"
                                       placeholder="0.0000">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Columna lateral --}}
            <div class="space-y-6">

                {{-- Stock actual info --}}
                <div class="form-card-compact">
                    <h3 class="section-title mb-2">Stock actual</h3>
                    <p class="text-2xl font-bold text-bakery-dark">{{ $ingrediente->stock_actual }}</p>
                    <p class="text-xs text-bakery-muted/50 mt-1">Minimo: {{ $ingrediente->stock_minimo }}</p>
                    <p class="text-xs text-bakery-muted/50 mt-2">Proveedor: {{ $ingrediente->proveedor?->nombre ?? 'Sin asignar' }}</p>
                </div>

                {{-- Historial de Control de inventario --}}
                @if($movimientos->isNotEmpty())
                <div class="form-card-compact">
                    <h3 class="section-title mb-3">Ultimos registros de control</h3>
                    <div class="space-y-2">
                        @foreach($movimientos as $mov)
                        <div class="flex items-center justify-between text-xs border-b border-bakery-amber/5 pb-2 last:border-0">
                            <div>
                                <span class="font-medium text-bakery-dark capitalize">{{ $mov->tipo }}</span>
                                @if($mov->notas)
                                    <p class="text-[10px] text-bakery-muted/50">{{ $mov->notas }}</p>
                                @endif
                            </div>
                            <div class="text-right">
                                <p class="font-semibold {{ in_array($mov->tipo, ['salida','merma']) ? 'text-bakery-terracotta' : 'text-bakery-sage' }}">
                                    {{ in_array($mov->tipo, ['salida','merma']) ? '-' : '+' }}{{ \App\Support\DisplayNumber::quantity((float) $mov->cantidad) }}
                                </p>
                                <p class="text-[10px] text-bakery-muted/50">{{ $mov->fecha->diffForHumans() }}</p>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>
        </div>

        {{-- Footer sticky --}}
        <div class="sticky bottom-0 bg-bakery-cream/95 backdrop-blur-sm border-t border-bakery-amber/20 -mx-6 px-6 py-4 flex justify-end gap-3">
            <a href="{{ route('ingredientes.index') }}" class="btn-secondary">Cancelar</a>
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
function ingredienteForm() {
    return {
        submitting: false,
        unidad: '{{ old('unidad_medida', $ingrediente->unidad_medida) }}',
        precio: '{{ old('precio', $ingrediente->precio) }}',
        stock_actual: '{{ old('stock_actual', $ingrediente->stock_actual) }}',
        get costoUnitCalc() {
            const p = parseFloat(this.precio) || 0;
            const s = parseFloat(this.stock_actual) || 0;
            return s > 0 ? (p / s).toFixed(4) : '';
        },
        getUnidadLabel() {
            const labels = { g: 'Gramos', oz: 'Onzas', ml: 'Mililitros', unidad: 'und.' };
            return labels[this.unidad] || this.unidad;
        },
        getUnidadHint() {
            const hints = { g: 'Ideal para harinas, azucar, etc.', oz: 'Para mantequilla, chocolate', ml: 'Liquidos en poca cantidad', unidad: 'Huevos, piezas, etc.' };
            return hints[this.unidad] || '';
        },
    }
}
</script>
</x-app-layout>

