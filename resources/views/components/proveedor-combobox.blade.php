@props([
    'selected' => null,
    'contexto' => 'ingrediente_create',
    'ingredienteId' => null,
    'draftKey' => null,
    'label' => 'Proveedor habitual',
    'hint' => 'Opcional. Facilita el análisis y la trazabilidad.',
])

@php
    $createUrl = route('finanzas.proveedores.create', array_filter([
        'contexto' => $contexto,
        'ingrediente_id' => $ingredienteId,
    ]));
@endphp

<div
    x-data="proveedorCombobox(@js([
        'searchUrl' => route('finanzas.proveedores.search'),
        'selectedId' => old('proveedor_id', $selected?->id),
        'selectedName' => $selected?->nombre,
        'draftKey' => $draftKey,
        'restoreDraft' => request()->boolean('restaurar'),
    ]))"
    x-id="['proveedor-listbox']"
    class="relative"
    @click.outside="open = false"
>
    <label class="form-label" :for="$id('proveedor-listbox')">{{ $label }}</label>
    <input type="hidden" name="proveedor_id" :value="selectedId">

    <div class="relative">
        <input
            type="search"
            x-ref="searchInput"
            x-model="query"
            :id="$id('proveedor-listbox')"
            class="form-input pr-24"
            placeholder="Sin proveedor - busca para asignar"
            autocomplete="off"
            role="combobox"
            aria-autocomplete="list"
            :aria-expanded="open.toString()"
            :aria-controls="$id('proveedor-listbox') + '-options'"
            :aria-activedescendant="activeOptionId($id('proveedor-listbox'))"
            :aria-invalid="invalidSelection.toString()"
            @focus="focus()"
            @input="input()"
            @keydown.arrow-down.prevent="move(1)"
            @keydown.arrow-up.prevent="move(-1)"
            @keydown.enter.prevent="chooseActive()"
            @keydown.escape="open = false"
        >
        <button
            type="button"
            x-show="selectedId || query"
            class="absolute inset-y-0 right-0 px-3 text-xs font-semibold text-bakery-muted hover:text-bakery-dark"
            @click="clear()"
        >
            Limpiar
        </button>
    </div>

    <div
        x-show="open"
        x-cloak
        class="absolute z-30 mt-2 max-h-72 w-full overflow-y-auto rounded-lg border border-bakery-amber/20 bg-white shadow-lg"
    >
        <div :id="$id('proveedor-listbox') + '-options'" role="listbox">
        <button
            type="button"
            :id="$id('proveedor-listbox') + '-option-0'"
            class="flex min-h-11 w-full items-center justify-between border-b border-bakery-amber/10 px-3 py-2 text-left text-sm hover:bg-bakery-warm"
            :class="activeIndex === 0 ? 'bg-bakery-warm' : ''"
            :aria-selected="(selectedId === '').toString()"
            role="option"
            @click="clear()"
        >
            <span class="font-medium text-bakery-dark">Sin proveedor</span>
            <span class="text-xs text-bakery-muted">Predeterminado</span>
        </button>

        <template x-for="(provider, index) in results" :key="provider.id">
            <button
                type="button"
                :id="$id('proveedor-listbox') + '-option-' + (index + 1)"
                class="block min-h-11 w-full px-3 py-2 text-left text-sm text-bakery-dark hover:bg-bakery-warm"
                :class="activeIndex === index + 1 ? 'bg-bakery-warm' : ''"
                :aria-selected="(selectedId === String(provider.id)).toString()"
                role="option"
                @click="select(provider)"
                x-text="provider.nombre"
            ></button>
        </template>

        <p x-show="loading" class="px-3 py-3 text-sm text-bakery-muted" aria-live="polite">Buscando proveedores...</p>
        <p x-show="!loading && searchError" class="px-3 py-3 text-sm text-bakery-terracotta" role="alert">No se pudo buscar. Intenta de nuevo.</p>
        <p x-show="!loading && !searchError && results.length === 0" class="px-3 py-3 text-sm text-bakery-muted">No hay coincidencias.</p>
        </div>

        <a
            href="{{ $createUrl }}"
            class="flex min-h-11 items-center border-t border-bakery-amber/15 px-3 py-2 text-sm font-semibold text-bakery-brown hover:bg-bakery-gold/10"
            @click="saveDraft($event)"
        >
            Agregar proveedor
        </a>
    </div>

    @error('proveedor_id') <p class="form-error">{{ $message }}</p> @enderror
    <p x-show="invalidSelection" x-cloak class="form-error" role="alert">Selecciona un proveedor de la lista o usa Sin proveedor.</p>
    <p class="mt-1 text-xs text-bakery-muted">{{ $hint }}</p>
</div>
