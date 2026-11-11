<x-app-layout>
@php
    $hasFilters = request()->filled('buscar')
        || request()->filled('desde')
        || request()->filled('hasta')
        || request()->filled('fecha')
        || request()->filled('producto_id')
        || request()->filled('presentacion_id')
        || request()->filled('user_id');
    $selectedProducto = $productos->firstWhere('id', (int) request('producto_id'));
    $selectedPresentacion = $presentaciones->firstWhere('id', (int) request('presentacion_id'));
    $selectedUsuario = $usuarios->firstWhere('id', (int) request('user_id'));
@endphp
<div class="py-8 px-6" x-data="produccionIndex()" x-cloak>

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h2 class="text-2xl font-display font-bold text-bakery-dark">Producci&oacute;n</h2>
            <p class="text-sm text-bakery-muted mt-0.5">Historial de lotes producidos</p>
        </div>
        <a href="{{ route('produccion.create') }}"
           class="btn-primary self-start sm:self-auto">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
            </svg>
            Registrar producci&oacute;n
        </a>
    </div>

    {{-- Flash messages --}}
    @if(session('success'))
        <div class="mb-4 flash-success text-sm rounded-xl px-4 py-3 flex items-start gap-2" role="alert">
            <svg class="w-4 h-4 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd"/>
            </svg>
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="mb-4 bg-bakery-terracotta/10 border border-bakery-terracotta/20 text-bakery-terracotta text-sm rounded-xl px-4 py-3 flex items-start gap-2" role="alert">
            <svg class="w-4 h-4 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
            </svg>
            {{ session('error') }}
        </div>
    @endif

    {{-- Filters --}}
    <div class="bg-white rounded-2xl shadow-sm border border-bakery-amber/20 mb-5"
         x-data="{
            search: @js(request('buscar', '')),
            showFilters: window.innerWidth >= 768,
            submitSearch() {
                const params = new URLSearchParams(window.location.search);
                if (this.search.trim()) {
                    params.set('buscar', this.search.trim());
                } else {
                    params.delete('buscar');
                }
                params.delete('page');
                window.location.href = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
            }
         }"
         @resize.window="showFilters = window.innerWidth >= 768">
        <div class="p-4 border-b border-bakery-amber/10">
            <div class="flex flex-col lg:flex-row gap-3">
                <div class="relative flex-1">
                    <label for="buscar-produccion" class="sr-only">Buscar producci&oacute;n</label>
                    <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-bakery-muted/50 pointer-events-none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input id="buscar-produccion"
                           type="text"
                           x-model="search"
                           @keydown.enter.prevent="submitSearch()"
                           placeholder="Buscar lote, producto, presentaci&oacute;n, notas o usuario..."
                           autocomplete="off"
                           class="w-full pl-12 pr-10 h-11 text-sm border border-bakery-amber/30 rounded-xl bg-bakery-warm/50 text-bakery-dark placeholder-bakery-muted/60 focus:outline-none focus:ring-2 focus:ring-bakery-gold/40 focus:border-bakery-gold transition">
                    <button x-show="search.trim()"
                            @click="search = ''; submitSearch()"
                            type="button"
                            class="absolute right-3 top-1/2 -translate-y-1/2 p-1.5 rounded-lg text-bakery-muted/50 hover:text-bakery-terracotta hover:bg-bakery-terracotta/10 transition"
                            aria-label="Limpiar b&uacute;squeda">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <div class="flex gap-2">
                    <button type="button"
                            @click="showFilters = !showFilters"
                            class="btn-secondary text-sm md:hidden"
                            :aria-expanded="showFilters.toString()">
                        <svg class="w-4 h-4 transition-transform" :class="showFilters ? 'rotate-180' : ''" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                        </svg>
                        Filtros
                    </button>
                    <button type="button" @click="submitSearch()" class="btn-primary text-sm">
                        Buscar
                    </button>
                </div>
            </div>
        </div>

        <div x-show="showFilters"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 -translate-y-2"
             x-transition:enter-end="opacity-100 translate-y-0"
             class="px-4 py-4">
            <form method="GET" action="{{ route('produccion.index') }}" class="space-y-3">
                <input type="hidden" name="buscar" value="{{ request('buscar') }}">

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
                    <div>
                        <label for="producto_id" class="text-xs font-medium text-bakery-muted mb-1 block">Producto</label>
                        <select id="producto_id" name="producto_id" class="form-select py-2 text-sm" aria-label="Filtrar por producto">
                            <option value="">Todos</option>
                            @foreach($productos as $producto)
                                <option value="{{ $producto->id }}" {{ request('producto_id') == $producto->id ? 'selected' : '' }}>
                                    {{ $producto->nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="presentacion_id" class="text-xs font-medium text-bakery-muted mb-1 block">Presentaci&oacute;n</label>
                        <select id="presentacion_id" name="presentacion_id" class="form-select py-2 text-sm" aria-label="Filtrar por presentaci&oacute;n">
                            <option value="">Todas</option>
                            @foreach($presentaciones as $presentacion)
                                <option value="{{ $presentacion->id }}" {{ request('presentacion_id') == $presentacion->id ? 'selected' : '' }}>
                                    {{ $presentacion->producto?->nombre }} - {{ $presentacion->nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="user_id" class="text-xs font-medium text-bakery-muted mb-1 block">Responsable</label>
                        <select id="user_id" name="user_id" class="form-select py-2 text-sm" aria-label="Filtrar por responsable">
                            <option value="">Todos</option>
                            @foreach($usuarios as $usuario)
                                <option value="{{ $usuario->id }}" {{ request('user_id') == $usuario->id ? 'selected' : '' }}>
                                    {{ $usuario->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="desde" class="text-xs font-medium text-bakery-muted mb-1 block">Desde</label>
                        <input id="desde" type="date" name="desde" value="{{ $desde }}" class="form-input py-2 text-sm" aria-label="Fecha desde">
                    </div>

                    <div>
                        <label for="hasta" class="text-xs font-medium text-bakery-muted mb-1 block">Hasta</label>
                        <input id="hasta" type="date" name="hasta" value="{{ $hasta }}" class="form-input py-2 text-sm" aria-label="Fecha hasta">
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row sm:items-center gap-2 pt-2">
                    <button type="submit" class="btn-primary text-sm w-full sm:w-auto">Aplicar filtros</button>
                    <a href="{{ route('produccion.index') }}" class="btn-ghost text-sm w-full sm:w-auto justify-center">Limpiar</a>
                    @if($hasFilters)
                        <span class="text-xs text-bakery-muted sm:ml-auto">{{ $producciones->total() }} resultado(s)</span>
                    @endif
                </div>
            </form>
        </div>
    </div>

    {{-- Active filters indicator --}}
    @if($hasFilters)
        <div class="mb-3 flex flex-wrap items-center gap-2 text-xs text-bakery-muted">
            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
            </svg>
            <span>Filtros activos:</span>
            @if(request('buscar'))
                <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-bakery-warm text-bakery-dark">"{{ request('buscar') }}"</span>
            @endif
            @if($selectedProducto)
                <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-bakery-gold/15 text-bakery-brown">{{ $selectedProducto->nombre }}</span>
            @endif
            @if($selectedPresentacion)
                <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-bakery-amber/15 text-bakery-brown">{{ $selectedPresentacion->nombre }}</span>
            @endif
            @if($selectedUsuario)
                <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-bakery-sage/10 text-bakery-sage">{{ $selectedUsuario->name }}</span>
            @endif
            @if($desde || $hasta)
                <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-white border border-bakery-amber/20 text-bakery-muted">
                    {{ $desde ?: 'Inicio' }} &rarr; {{ $hasta ?: 'Hoy' }}
                </span>
            @endif
        </div>
    @endif

    {{-- Table --}}
    <div class="bg-white rounded-2xl shadow-sm border border-bakery-amber/10 overflow-hidden">
        <div class="overflow-x-auto">
        <table class="w-full text-sm min-w-[820px]" role="table" aria-label="Historial de producci&oacute;n">
            <thead class="bg-bakery-warm border-b border-bakery-amber/10">
                <tr class="text-left text-bakery-muted">
                    <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide">Lote</th>
                    <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide">Producto</th>
                    <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-center">Lotes</th>
                    <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-center">Unds.</th>
                    <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide">Fecha</th>
                    <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide">Usuario</th>
                    <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-right">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($producciones as $prod)
                @php
                    $receta = $prod->presentacion->recetaActiva ?? $prod->presentacion->producto->recetaDefault;
                    $loteUnidades = $receta?->lote_unidades ?? $prod->presentacion->cantidad_unidades ?? 0;
                    $unidadesTotales = $prod->cantidad * $loteUnidades;
                @endphp
                <tr class="border-b border-bakery-amber/5 hover:bg-bakery-warm/50 transition-colors">
                    <td class="px-4 py-3">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-bakery-amber/10 border border-bakery-amber/15 text-xs font-mono font-semibold text-bakery-dark">
                            <span class="w-1.5 h-1.5 rounded-full bg-bakery-gold"></span>
                            {{ $prod->numero_lote }}
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        <div>
                            <p class="font-medium text-bakery-dark">{{ $prod->presentacion->producto->nombre ?? '-' }}</p>
                            <p class="text-xs text-bakery-muted">{{ $prod->presentacion->nombre ?? '-' }}</p>
                            @if($prod->notas)
                                <p class="text-[11px] text-bakery-muted/70 truncate max-w-[220px]">{{ $prod->notas }}</p>
                            @endif
                        </div>
                    </td>
                    <td class="px-4 py-3 text-center">
                        <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-bakery-warm border border-bakery-amber/10 text-sm font-bold text-bakery-gold tabular-nums">
                            {{ $prod->cantidad }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-center">
                        @if($loteUnidades > 0)
                            <span class="text-sm font-semibold text-bakery-dark tabular-nums">
                                {{ $unidadesTotales }}
                            </span>
                            <span class="text-xs text-bakery-muted ml-1">uds</span>
                        @else
                            <span class="text-xs text-bakery-muted/50">&mdash;</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        <div>
                            <p class="text-bakery-dark text-xs font-medium">{{ $prod->created_at->format('d/m/Y') }}</p>
                            <p class="text-bakery-muted text-[11px]">{{ $prod->created_at->format('H:i') }}</p>
                        </div>
                    </td>
                    <td class="px-4 py-3 text-bakery-muted text-xs">{{ $prod->user->name ?? '-' }}</td>
                    <td class="px-4 py-3 text-right">
                        <button type="button"
                                @click="reproducirLote('{{ $prod->presentacion_id }}')"
                                class="btn-ghost text-xs px-2.5 py-1.5"
                                :title="'Reproducir: {{ addslashes($prod->presentacion->producto->nombre ?? '') }} - {{ addslashes($prod->presentacion->nombre ?? '') }}'"
                                aria-label="Reproducir mismo lote">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182"/>
                            </svg>
                            Reproducir
                        </button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7">
                        <div class="text-center py-12 px-4">
                            <svg class="mx-auto w-12 h-12 text-bakery-amber/30 mb-3" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"/>
                            </svg>
                            @if($hasFilters)
                                <p class="text-sm text-bakery-dark font-medium">No hay lotes para estos filtros</p>
                                <p class="text-xs text-bakery-muted/60 mt-1">Prueba con otro rango, producto o responsable.</p>
                                <a href="{{ route('produccion.index') }}" class="inline-flex items-center gap-1.5 mt-4 text-sm text-bakery-gold hover:text-bakery-brown font-medium transition-colors">
                                    Limpiar filtros
                                </a>
                            @else
                                <p class="text-sm text-bakery-muted/60 font-medium">No hay producciones registradas</p>
                                <p class="text-xs text-bakery-muted/40 mt-1">Registra tu primer lote de producci&oacute;n para comenzar</p>
                                <a href="{{ route('produccion.create') }}"
                                   class="inline-flex items-center gap-1.5 mt-4 text-sm text-bakery-gold hover:text-bakery-brown font-medium transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                                    </svg>
                                    Registrar producci&oacute;n
                                </a>
                            @endif
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        </div>
        @if($producciones->hasPages())
        <div class="px-4 py-3 border-t border-bakery-amber/10">
            {{ $producciones->links('vendor.pagination.bakery') }}
        </div>
        @endif
    </div>
</div>

<script>
function produccionIndex() {
    return {
        reproducirLote(presentacionId) {
            window.location.href = '{{ route('produccion.create') }}?presentacion_id=' + presentacionId;
        },
    }
}
</script>
<style>
[x-cloak] { display: none !important; }
</style>
</x-app-layout>
