@props(['cliente' => null, 'form' => null, 'requerido' => 'false', 'permiteQuitar' => true])

@php
    $id = filter_var(old('cliente_id', $cliente?->id), FILTER_VALIDATE_INT);
    $seleccionado = $id ? ($id === $cliente?->id ? $cliente : \App\Models\Cliente::activos()->find($id)) : null;
    $opcion = $seleccionado ? [
        'id' => $seleccionado->id,
        'nombre' => $seleccionado->nombre,
        'telefono' => $seleccionado->telefono_visible,
        'archivado' => $seleccionado->archivado_at !== null,
    ] : null;
@endphp

<section class="crm-customer-selector w-full min-w-0 max-w-xl" aria-labelledby="selector-cliente-titulo"
         x-data="selectorCliente(@js($opcion), @js(route('clientes.buscar')), @js(route('clientes.alta-rapida')), @js(csrf_token()))">
    <div class="flex flex-wrap items-center justify-between gap-2">
        <h3 id="selector-cliente-titulo" class="text-base font-semibold text-bakery-dark">Cliente</h3>
        <span class="text-xs text-bakery-muted" x-text="({{ $requerido }}) ? 'Obligatorio' : 'Opcional'"></span>
    </div>

    <input type="hidden" name="cliente_id" value="{{ $opcion['id'] ?? '' }}" :value="seleccion?.id ?? ''" @if($form) form="{{ $form }}" @endif>
    @error('cliente_id') <p class="form-error mt-2" role="alert">{{ $message }}</p> @enderror

    <div x-cloak x-show="seleccion" class="mt-2 flex flex-wrap items-center justify-between gap-2">
        <div class="min-w-0">
            <p class="break-words font-semibold text-bakery-dark" x-text="seleccion?.nombre"></p>
            <p class="text-sm text-bakery-muted" x-text="seleccion?.telefono"></p>
            <p class="text-sm text-bakery-brown" x-show="seleccion?.archivado">Cliente archivado</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <button type="button" class="btn-ghost" data-foco-cliente="cambiar" @click="abrirBusqueda">Cambiar</button>
            @if($permiteQuitar)
                <button type="button" class="btn-ghost" x-show="!({{ $requerido }})" @click="quitar">Quitar</button>
            @endif
        </div>
    </div>

    <div x-cloak x-show="!seleccion || buscando" class="mt-4">
        <label for="buscar-cliente" class="form-label mb-2">Buscar por nombre o teléfono</label>
        <div class="flex flex-wrap items-center gap-2">
            <input id="buscar-cliente" type="search" class="form-input min-h-11 min-w-0 flex-[1_1_11rem] rounded-lg py-2" maxlength="120" autocomplete="off" :disabled="guardando"
                   data-foco-cliente="busqueda" x-model="texto" @input="programarBusqueda" @keydown.enter.prevent="buscar"
                   @keydown.escape.stop="cerrarBusqueda" aria-controls="resultados-clientes">
            <button type="button" class="btn-secondary min-h-11 shrink-0 rounded-lg px-3 py-0 text-sm"
                    aria-label="Registrar cliente" aria-controls="alta-cliente"
                    x-show="!alta" @click="alta = true; enfocar('nombre')">Registrar cliente</button>
        </div>
        <p class="text-sm text-bakery-muted [&:not(:empty)]:mt-2" role="status" x-text="estado"></p>
        <ul id="resultados-clientes" x-show="resultados.length" class="mt-2 max-h-56 divide-y divide-bakery-amber/15 overflow-auto">
            <template x-for="opcion in resultados" :key="opcion.id">
                <li>
                    <button type="button" class="w-full rounded-lg px-2 py-2 text-left hover:bg-bakery-warm focus-visible:outline focus-visible:outline-2"
                            :disabled="guardando" @click="elegir(opcion)">
                        <span class="block break-words font-medium text-bakery-dark" x-text="opcion.nombre"></span>
                        <span class="text-sm text-bakery-muted" x-text="opcion.telefono"></span>
                    </button>
                </li>
            </template>
        </ul>
        <button type="button" class="btn-ghost mt-2" x-show="seleccion" :disabled="guardando" @click="cerrarBusqueda">Cancelar cambio</button>

        <div id="alta-cliente" x-show="alta" class="mt-4 space-y-4 border-t border-bakery-amber/20 pt-4">
            <label class="block">
                <span class="form-label mb-2">Nombre</span>
                <input type="text" data-foco-cliente="nombre" x-model="nombre" maxlength="120" class="form-input" autocomplete="off" :disabled="guardando" @keydown.enter.prevent="registrar">
            </label>
            <label class="block">
                <span class="form-label mb-2">Teléfono</span>
                <input type="tel" x-model="telefono" maxlength="40" class="form-input" placeholder="2222-0000" autocomplete="off" :disabled="guardando" @keydown.enter.prevent="registrar">
            </label>
            <p class="form-error" role="alert" x-text="errorAlta" x-show="errorAlta"></p>
            <div class="flex flex-wrap gap-2">
                <button type="button" class="btn-primary" @click="registrar" :disabled="guardando" x-text="guardando ? 'Guardando…' : 'Guardar y seleccionar'"></button>
                <button type="button" class="btn-ghost" :disabled="guardando" @click="alta = false; enfocar('busqueda')">Cancelar alta</button>
            </div>
        </div>
    </div>
</section>
