<x-app-layout>
<div class="py-6 px-4 sm:px-6">
    {{-- Header --}}
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-2xl font-display font-bold text-bakery-dark">Ingredientes</h2>
            <p class="text-sm text-bakery-muted mt-0.5">Materias primas y control de inventario</p>
        </div>
        <div class="flex flex-wrap gap-2 sm:justify-end">
            <a href="{{ route('ingredientes.control-inventario.create') }}" class="btn-secondary">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2m6-2a10 10 0 11-20 0 10 10 0 0120 0z"/>
                </svg>
                Registrar entrada/merma
            </a>
            <a href="{{ route('ingredientes.create') }}" class="btn-primary">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                </svg>
                Nuevo ingrediente
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-4 flash-success text-sm rounded-xl px-4 py-3" role="alert" aria-live="assertive">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="mb-4 bg-bakery-terracotta/10 border border-bakery-terracotta/20 text-bakery-terracotta text-sm rounded-xl px-4 py-3" role="alert" aria-live="assertive">
            {{ session('error') }}
        </div>
    @endif

    {{-- ================================================
        SEARCH + FILTERS SECTION
        ================================================ --}}
    <div x-data="{
        search: @js(request('buscar', '')),
        submitSearch() {
            const params = new URLSearchParams(window.location.search);
            if (this.search.trim()) {
                params.set('buscar', this.search.trim());
            } else {
                params.delete('buscar');
            }
            params.delete('page');
            window.location.href = window.location.pathname + '?' + params.toString();
        }
    }" class="bg-white rounded-2xl shadow-sm border border-bakery-amber/20 p-4 mb-6">

        {{-- Search + Category --}}
        <div class="flex flex-col sm:flex-row gap-3 mb-4">
            {{-- Search Input --}}
            <div class="relative flex-1">
                <label for="buscar-ingrediente" class="sr-only">Buscar ingrediente por nombre</label>
                <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-bakery-muted/50 pointer-events-none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input
                    id="buscar-ingrediente"
                    type="text"
                    x-model="search"
                    @keydown.enter.prevent="submitSearch()"
                    aria-label="Buscar ingrediente por nombre"
                    placeholder="Buscar ingrediente..."
                    autocomplete="off"
                    class="w-full pl-12 pr-10 h-11 text-sm border border-bakery-amber/30 rounded-xl bg-bakery-warm/50
                           text-bakery-dark placeholder-bakery-muted/60
                           focus:outline-none focus:ring-2 focus:ring-bakery-gold/40 focus:border-bakery-gold
                           transition duration-150"
                />
                <button
                    x-show="search.trim()"
                    @click="search = ''; submitSearch()"
                    type="button"
                    class="absolute right-3 top-1/2 -translate-y-1/2 p-1.5 rounded-lg text-bakery-muted/50 hover:text-bakery-terracotta hover:bg-bakery-terracotta/10 transition"
                    aria-label="Limpiar búsqueda">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- Category Select --}}
            <div class="relative sm:w-52">
                <label for="filtro-categoria" class="sr-only">Filtrar por categoría</label>
                <select
                    id="filtro-categoria"
                    class="w-full h-11 pl-4 pr-10 text-sm border border-bakery-amber/30 rounded-xl bg-bakery-warm/50
                           text-bakery-dark appearance-none cursor-pointer truncate
                           focus:outline-none focus:ring-2 focus:ring-bakery-gold/40 focus:border-bakery-gold
                           transition duration-150"
                    onchange="window.location.href = this.options[this.selectedIndex].value"
                    aria-label="Filtrar por categoría">
                    <option value="{{ route('ingredientes.index', array_filter(['buscar' => request('buscar'), 'filtro' => request('filtro')])) }}"
                        {{ !request('categoria') ? 'selected' : '' }}>
                        Todas las categorías
                    </option>
                    @foreach($categorias as $cat)
                    <option value="{{ route('ingredientes.index', array_filter(['categoria' => $cat['value'], 'buscar' => request('buscar'), 'filtro' => request('filtro')])) }}"
                        {{ request('categoria') === $cat['value'] ? 'selected' : '' }}>
                        {{ $cat['label'] }}
                    </option>
                    @endforeach
                </select>
                <svg class="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-bakery-muted/50 pointer-events-none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                </svg>
            </div>
        </div>

        {{-- Quick Filters + Clear --}}
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('ingredientes.index', array_filter([
                'buscar' => request('buscar'),
                'categoria' => request('categoria'),
            ])) }}"
            class="inline-flex items-center h-9 px-4 text-xs font-semibold rounded-lg border transition
                    {{ !request('filtro') ? 'bg-bakery-gold/15 border-bakery-gold/40 text-bakery-brown shadow-sm' : 'bg-white border-bakery-amber/20 text-bakery-muted hover:bg-bakery-warm hover:border-bakery-amber/40' }}">
                Todos
            </a>
            <a href="{{ route('ingredientes.index', array_filter([
                'filtro' => 'bajo',
                'buscar' => request('buscar'),
                'categoria' => request('categoria'),
            ])) }}"
            class="inline-flex items-center h-9 px-4 text-xs font-semibold rounded-lg border transition gap-1.5
                    {{ request('filtro') === 'bajo' ? 'bg-bakery-terracotta/15 border-bakery-terracotta/40 text-bakery-terracotta shadow-sm' : 'bg-white border-bakery-amber/20 text-bakery-muted hover:bg-bakery-warm hover:border-bakery-amber/40' }}">
                Stock bajo
                @if($bajosStockCount > 0)
                    <span class="inline-flex items-center justify-center min-w-[18px] h-[18px] px-1.5 text-[10px] font-bold text-white bg-bakery-terracotta rounded-full">{{ $bajosStockCount }}</span>
                @endif
            </a>

            @if(request('filtro') || request('categoria'))
                <a href="{{ route('ingredientes.index') }}"
                   class="ml-auto inline-flex items-center h-9 px-3 text-xs font-medium text-bakery-terracotta/80 hover:text-bakery-terracotta rounded-lg hover:bg-bakery-terracotta/5 transition gap-1">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                    Limpiar
                </a>
            @endif
        </div>
    </div>

    {{-- Active filters indicator --}}
    @if(request('buscar') || request('categoria') || request('filtro'))
        <div class="mb-3 flex flex-wrap items-center gap-2 text-xs text-bakery-muted">
            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
            </svg>
            <span>
                Filtros activos:
                @if(request('buscar'))
                    "<strong class="text-bakery-dark">{{ request('buscar') }}</strong>"
                @endif
                @if(request('categoria'))
                    en <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-bakery-gold/15 text-bakery-brown text-[10px] font-medium">{{ $categorias->firstWhere('value', request('categoria'))['label'] ?? request('categoria') }}</span>
                @endif
                @if(request('filtro') === 'bajo')
                    con stock bajo
                @endif
            </span>
        </div>
    @endif

    {{-- Tabla --}}
    <div x-data class="bg-white rounded-2xl shadow-sm border border-bakery-amber/10 overflow-hidden">
        <div class="overflow-x-auto" tabindex="0" role="region" aria-label="Tabla de ingredientes">
        <table class="w-full text-sm min-w-[600px]">
            <thead class="bg-bakery-warm border-b border-bakery-amber/10">
                <tr class="text-left text-bakery-muted text-xs uppercase tracking-wider">
                    <th class="px-4 py-3 font-semibold">Nombre</th>
                    <th class="px-4 py-3 font-semibold">Unidad</th>
                    <th class="px-4 py-3 font-semibold">Stock actual</th>
                    <th class="px-4 py-3 font-semibold hidden sm:table-cell">Stock min</th>
                    <th class="px-4 py-3 font-semibold hidden md:table-cell">Proveedor</th>
                    <th class="px-4 py-3 font-semibold hidden md:table-cell">Costo unit</th>
                    <th class="px-4 py-3 font-semibold"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-bakery-amber/5">
                @forelse($ingredientes as $ing)
                <tr class="border-b border-bakery-amber/5 hover:bg-bakery-warm transition
                    {{ $ing->stockBajo() ? 'bg-bakery-terracotta/10 hover:bg-bakery-terracotta/20' : '' }}">
                    <td class="px-4 py-3 font-medium text-bakery-dark">
                        <div class="flex items-center gap-2">
                            <span>{{ $ing->nombre }}</span>
                            @if($ing->stockBajo())
                                <span class="badge-danger text-[10px]">Bajo</span>
                            @endif
                        </div>
                        <span class="text-[10px] text-bakery-muted">{{ $ing->categoriaLabel() }}</span>
                    </td>
                    <td class="px-4 py-3 text-bakery-muted text-xs">{{ \App\Support\UnitFormatter::short($ing->unidad_medida) }}</td>
                    <td class="px-4 py-3 {{ $ing->stockBajo() ? 'text-bakery-terracotta font-semibold' : 'text-bakery-dark' }}">
                        {{ \App\Support\DisplayNumber::quantity($ing->stock_actual) }}
                    </td>
                    <td class="px-4 py-3 text-bakery-muted text-xs">{{ \App\Support\DisplayNumber::quantity($ing->stock_minimo) }}</td>
                    <td class="px-4 py-3 text-bakery-muted text-xs hidden md:table-cell">{{ $ing->proveedor?->nombre ?? 'Sin asignar' }}</td>
                    <td class="px-4 py-3 text-bakery-muted text-xs">{{ \App\Support\DisplayNumber::unitCost($ing->costo_unitario) }}/{{ \App\Support\UnitFormatter::short($ing->unidad_medida) }}</td>
                    <td class="px-4 py-3 text-right">
                        <div class="flex items-center justify-end gap-3">
                            <a href="{{ route('ingredientes.control-inventario.create', ['ingrediente_id' => $ing->id]) }}"
                            class="text-bakery-sage hover:text-bakery-brown text-xs flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2m6-2a10 10 0 11-20 0 10 10 0 0120 0z"/>
                                </svg>
                                Movimiento
                            </a>
                            <a href="{{ route('ingredientes.edit', $ing) }}"
                            class="text-bakery-brown hover:text-bakery-gold text-xs flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                                Editar
                            </a>
                            <button type="button"
                                @click="$dispatch('open-confirm-modal', {
                                    action: '{{ route('ingredientes.destroy', $ing) }}',
                                    method: 'DELETE',
                                    confirmText: 'Archivar ingrediente',
                                    consequences: 'El ingrediente se marcará como eliminado. Los registros de control de inventario seguiran disponibles.',
                                    warning: '{{ $ing->recetas_activas_count > 0 ? $ing->recetas_activas_count . ' receta(s) se desactivarán automáticamente porque usan este ingrediente.' : '' }}'
                                })"
                                aria-label="Archivar ingrediente {{ $ing->nombre }}"
                                class="text-bakery-terracotta hover:text-red-600 text-xs flex items-center gap-1 transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                                Archivar
                            </button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-4 py-12">
                        <div class="flex flex-col items-center text-center">
                            <div class="w-16 h-16 mb-4 rounded-full bg-bakery-warm flex items-center justify-center">
                                <svg class="w-8 h-8 text-bakery-amber/50" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                </svg>
                            </div>
                            @if(request('buscar') || request('categoria') || request('filtro'))
                                <p class="text-bakery-dark font-medium mb-1">No hay ingredientes para tu búsqueda</p>
                                <p class="text-bakery-muted text-xs mb-4">Intenta con otros términos o limpia los filtros</p>
                                <a href="{{ route('ingredientes.index') }}" class="btn-secondary text-xs">
                                    Limpiar filtros
                                </a>
                            @else
                                <p class="text-bakery-dark font-medium mb-1">No hay ingredientes registrados</p>
                                <p class="text-bakery-muted text-xs mb-4">Comienza agregando tu primer ingrediente</p>
                                <a href="{{ route('ingredientes.create') }}" class="btn-primary text-xs">
                                    Crear ingrediente
                                </a>
                            @endif
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        </div>
        @if($ingredientes->hasPages())
        <div class="px-4 py-3 border-t border-bakery-amber/10">
            {{ $ingredientes->links('vendor.pagination.bakery') }}
        </div>
        @endif
    </div>
</x-app-layout>

