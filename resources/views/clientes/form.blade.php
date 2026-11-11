<x-app-layout>
    <div class="crm-workspace">
        <a class="crm-back" href="{{ route('clientes.index') }}"><span aria-hidden="true">←</span> Clientes</a>
        <header class="crm-heading"><h1>{{ $cliente->exists ? 'Editar cliente' : 'Nuevo cliente' }}</h1></header>
        <form class="crm-surface crm-contact-form" method="POST" action="{{ $cliente->exists ? route('clientes.update', $cliente) : route('clientes.store') }}">
            @csrf
            @if($cliente->exists) @method('PATCH') @endif
            <div>
                <label class="form-label" for="nombre-cliente">Nombre</label>
                <input class="form-input" id="nombre-cliente" name="nombre" autocomplete="name" maxlength="120" required
                       value="{{ old('nombre', $cliente->nombre) }}" @error('nombre') aria-invalid="true" aria-describedby="error-nombre" @enderror>
                @error('nombre') <p class="form-error" id="error-nombre">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="form-label" for="telefono-cliente">Teléfono</label>
                <input class="form-input" type="tel" id="telefono-cliente" name="telefono" autocomplete="tel" maxlength="40" required
                       value="{{ old('telefono', $cliente->exists ? $cliente->telefono_visible : '') }}"
                       aria-describedby="ayuda-telefono @error('telefono') error-telefono @enderror" @error('telefono') aria-invalid="true" @enderror>
                <p class="mt-1 text-sm text-bakery-muted" id="ayuda-telefono">Usa ocho dígitos en El Salvador. Para otros países, incluye el prefijo con +.</p>
                @error('telefono') <p class="form-error" id="error-telefono">{{ $message }}</p> @enderror
            </div>
            <div class="crm-actions">
                <button class="btn-primary" type="submit">{{ $cliente->exists ? 'Guardar cambios' : 'Registrar cliente' }}</button>
                <a class="btn-ghost" href="{{ $cliente->exists ? route('clientes.show', $cliente) : route('clientes.index') }}">Cancelar</a>
            </div>
        </form>
    </div>
</x-app-layout>
