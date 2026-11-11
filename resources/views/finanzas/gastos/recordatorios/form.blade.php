@php
    $editing = $recordatorio?->exists === true;
    $action = $editing ? route('finanzas.gastos.recordatorios.update', $recordatorio) : route('finanzas.gastos.recordatorios.store');
@endphp
<x-app-layout>
<main class="finance-page gastos-page">
    <div class="gastos-form-shell-narrow">
        <a href="{{ route('finanzas.gastos.recordatorios.index') }}" class="finance-icon-label text-sm font-semibold text-bakery-muted transition hover:text-bakery-brown"><x-finanzas.icon name="arrow-left" />Volver a recordatorios</a>
        <header class="mt-5 border-b border-bakery-amber/15 pb-5">
            <h2 class="finance-title finance-icon-label"><x-finanzas.icon name="bell" />{{ $editing ? 'Editar recordatorio' : 'Crear recordatorio' }}</h2>
            <p class="finance-description">Aviso para preparar un gasto.</p>
        </header>
        @if($errors->any())<div class="mt-5 rounded-lg border border-bakery-terracotta/25 bg-bakery-terracotta/10 px-4 py-3 text-sm text-bakery-terracotta" role="alert">Revisa los campos indicados.</div>@endif

        <form method="POST" action="{{ $action }}" class="mt-7 space-y-6">
            @csrf @if($editing) @method('PUT') @endif
            <section class="gastos-section" aria-labelledby="recordatorio-datos">
                <h3 id="recordatorio-datos" class="finance-section-title finance-icon-label"><x-finanzas.icon name="receipt" />Datos</h3>
                <div class="mt-4 space-y-5">
                    <div>
                        <label for="concepto" class="form-label form-label-required">Concepto</label>
                        <input id="concepto" name="concepto" type="text" maxlength="160" class="form-input" value="{{ old('concepto', $recordatorio?->concepto) }}" placeholder="Ej. Electricidad del local" required @error('concepto') aria-invalid="true" aria-describedby="concepto-error" @enderror>
                        @error('concepto')<p id="concepto-error" class="form-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <div>
                            <label for="categoria_id" class="form-label form-label-required">Categoría</label>
                            <select id="categoria_id" name="categoria_id" class="form-select" required @error('categoria_id') aria-invalid="true" aria-describedby="categoria_id-error" @enderror>
                                <option value="">Seleccionar</option>
                                @foreach($categorias as $categoria)
                                    <option value="{{ $categoria->id }}" @selected(old('categoria_id', $recordatorio?->categoria_id) == $categoria->id)>{{ $categoria->nombre }}</option>
                                @endforeach
                            </select>
                            @error('categoria_id')<p id="categoria_id-error" class="form-error">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="proveedor_id" class="form-label">Proveedor</label>
                            <select id="proveedor_id" name="proveedor_id" class="form-select" @error('proveedor_id') aria-invalid="true" aria-describedby="proveedor_id-error" @enderror>
                                <option value="">Sin proveedor</option>
                                @foreach($proveedores as $proveedor)
                                    <option value="{{ $proveedor->id }}" @selected(old('proveedor_id', $recordatorio?->proveedor_id) == $proveedor->id)>{{ $proveedor->nombre }}</option>
                                @endforeach
                            </select>
                            @error('proveedor_id')<p id="proveedor_id-error" class="form-error">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="monto_estimado" class="form-label">Monto estimado</label>
                            <input id="monto_estimado" name="monto_estimado" type="number" step="0.01" min="0.01" max="999999.99" class="form-input" value="{{ old('monto_estimado', $recordatorio?->monto_estimado) }}" placeholder="Opcional" @error('monto_estimado') aria-invalid="true" aria-describedby="monto_estimado-error" @enderror>
                            @error('monto_estimado')<p id="monto_estimado-error" class="form-error">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="metodo_pago" class="form-label">Método sugerido</label>
                            <select id="metodo_pago" name="metodo_pago" class="form-select" @error('metodo_pago') aria-invalid="true" aria-describedby="metodo_pago-error" @enderror>
                                <option value="">Sin preferencia</option>
                                @foreach($metodosPago as $metodo)
                                    <option value="{{ $metodo->value }}" @selected(old('metodo_pago', $recordatorio?->metodo_pago?->value) === $metodo->value)>{{ $metodo->label() }}</option>
                                @endforeach
                            </select>
                            @error('metodo_pago')<p id="metodo_pago-error" class="form-error">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </div>
            </section>

            <section class="gastos-section" aria-labelledby="recordatorio-frecuencia">
                <h3 id="recordatorio-frecuencia" class="finance-section-title finance-icon-label"><x-finanzas.icon name="calendar" />Frecuencia</h3>
                <div class="mt-4 grid grid-cols-1 gap-5 sm:grid-cols-3">
                    <div>
                        <label for="intervalo" class="form-label form-label-required">Cada</label>
                        <input id="intervalo" name="intervalo" type="number" min="1" max="365" class="form-input" value="{{ old('intervalo', $recordatorio?->intervalo ?? 1) }}" required @error('intervalo') aria-invalid="true" aria-describedby="intervalo-error" @enderror>
                        @error('intervalo')<p id="intervalo-error" class="form-error">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="unidad_frecuencia" class="form-label form-label-required">Unidad</label>
                        <select id="unidad_frecuencia" name="unidad_frecuencia" class="form-select" required @error('unidad_frecuencia') aria-invalid="true" aria-describedby="unidad_frecuencia-error" @enderror>
                            @foreach($unidades as $unidad)
                                <option value="{{ $unidad->value }}" @selected(old('unidad_frecuencia', $recordatorio?->unidad_frecuencia?->value ?? 'meses') === $unidad->value)>{{ $unidad->label() }}</option>
                            @endforeach
                        </select>
                        @error('unidad_frecuencia')<p id="unidad_frecuencia-error" class="form-error">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="proxima_fecha" class="form-label form-label-required">Próxima</label>
                        <input id="proxima_fecha" name="proxima_fecha" type="date" min="{{ now()->format('Y-m-d') }}" value="{{ old('proxima_fecha', $recordatorio?->proxima_fecha?->format('Y-m-d') ?? now()->format('Y-m-d')) }}" class="form-input" required @error('proxima_fecha') aria-invalid="true" aria-describedby="proxima_fecha-error" @enderror>
                        @error('proxima_fecha')<p id="proxima_fecha-error" class="form-error">{{ $message }}</p>@enderror
                    </div>
                </div>
            </section>

            <div class="flex flex-col-reverse gap-3 border-t border-bakery-amber/15 pt-5 sm:flex-row sm:justify-end">
                <a href="{{ route('finanzas.gastos.recordatorios.index') }}" class="btn-ghost text-center">Cancelar</a>
                <button type="submit" class="btn-primary"><x-finanzas.icon name="check" />{{ $editing ? 'Guardar cambios' : 'Crear recordatorio' }}</button>
            </div>
        </form>
    </div>
</main>
</x-app-layout>
