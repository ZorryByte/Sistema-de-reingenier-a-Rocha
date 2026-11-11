@php
    $editing = $proveedor->exists;
    $nombreBloqueado = $editing && $proveedor->compras()->exists();
    $action = $editing
        ? route('finanzas.proveedores.update', $proveedor)
        : route('finanzas.proveedores.store');
    $back = $contexto === 'ingrediente_create'
        ? route('ingredientes.create', ['restaurar' => 1])
        : ($contexto === 'ingrediente_edit' && $ingredienteId
            ? route('ingredientes.edit', ['ingrediente' => $ingredienteId, 'restaurar' => 1])
            : ($contexto === 'gasto_create'
                ? route('finanzas.gastos.create', ['restaurar' => 1])
                : route('finanzas.proveedores.index')));
@endphp

<x-app-layout>
<main class="finance-page">
    <div class="mx-auto w-full max-w-3xl px-4 py-7 sm:px-6 lg:py-10">
        <a href="{{ $back }}" class="text-sm font-semibold text-bakery-muted transition hover:text-bakery-brown">
            Volver
        </a>

        <header class="mt-6 border-b border-bakery-amber/15 pb-6">
            <p class="finance-kicker">Directorio de compras</p>
            <h2 class="finance-title">{{ $editing ? 'Editar proveedor' : 'Agregar proveedor' }}</h2>
            <p class="finance-description">Nombre obligatorio. El contacto puede completarse cuando este disponible.</p>
        </header>

        @if($errors->any())
            <div class="mt-5 rounded-lg border border-bakery-terracotta/25 bg-bakery-terracotta/10 px-4 py-3 text-sm text-bakery-terracotta" role="alert">
                Revisa los campos indicados.
            </div>
        @endif

        <form method="POST" action="{{ $action }}" class="mt-7">
            @csrf
            @if($editing) @method('PATCH') @endif
            <input type="hidden" name="contexto" value="{{ $contexto }}">
            @if($ingredienteId)
                <input type="hidden" name="ingrediente_id" value="{{ $ingredienteId }}">
            @endif

            <div class="space-y-6">
                <div>
                    <label for="nombre" class="form-label form-label-required">Nombre</label>
                    <input
                        id="nombre"
                        name="nombre"
                        type="text"
                        value="{{ old('nombre', $proveedor->nombre) }}"
                        class="form-input"
                        maxlength="100"
                        required
                        @readonly($nombreBloqueado)
                    >
                    @error('nombre') <p class="form-error">{{ $message }}</p> @enderror
                    @if($nombreBloqueado)
                        <p class="mt-1 text-xs text-bakery-muted">El nombre queda fijo porque ya tiene compras.</p>
                    @endif
                </div>

                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <div>
                        <label for="telefono" class="form-label">Telefono</label>
                        <input id="telefono" name="telefono" type="tel" value="{{ old('telefono', $proveedor->telefono) }}" class="form-input" maxlength="20" autocomplete="tel">
                        @error('telefono') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="email" class="form-label">Correo</label>
                        <input id="email" name="email" type="email" value="{{ old('email', $proveedor->email) }}" class="form-input" maxlength="100" autocomplete="email">
                        @error('email') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div>
                    <label for="notas" class="form-label">Notas</label>
                    <textarea id="notas" name="notas" rows="4" class="form-textarea" maxlength="500" placeholder="Horarios, condiciones o contacto alterno...">{{ old('notas', $proveedor->notas) }}</textarea>
                    @error('notas') <p class="form-error">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="mt-8 flex flex-col-reverse gap-3 border-t border-bakery-amber/15 pt-5 sm:flex-row sm:justify-end">
                <a href="{{ $back }}" class="btn-secondary">Cancelar</a>
                <button type="submit" class="btn-primary">{{ $editing ? 'Guardar cambios' : 'Agregar proveedor' }}</button>
            </div>
        </form>
    </div>
</main>
</x-app-layout>
