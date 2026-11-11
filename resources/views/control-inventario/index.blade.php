@php use Illuminate\Support\Facades\Route; @endphp
<x-app-layout>
<div class="inventory-page">
<div class="inventory-shell">
    @php
        $currentMovimientos = $movimientos->getCollection();
        $entradasPagina = $currentMovimientos->where('tipo', 'entrada')->count();
        $salidasPagina = $currentMovimientos->filter(fn ($mov) => in_array($mov->tipo, ['salida', 'merma', 'venta']))->count();
    @endphp

    <div class="inventory-header">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <p class="dashboard-kicker">Inventario</p>
            <h2 class="mt-2 text-2xl font-display font-bold tracking-tight text-bakery-dark sm:text-3xl">Control de inventario</h2>
            <p class="mt-1 max-w-2xl text-sm leading-relaxed text-bakery-muted">Auditoria completa de entradas, salidas, ventas, mermas, ajustes y creaciones. Registra entradas y mermas desde Ingredientes para mantener el flujo ordenado.</p>
        </div>
        </div>

        <div class="mt-5 grid grid-cols-1 gap-3 sm:grid-cols-3">
            <div class="inventory-stat">
                <p class="inventory-stat-label">Total filtrado</p>
                <p class="inventory-stat-value">{{ $movimientos->total() }}</p>
            </div>
            <div class="inventory-stat">
                <p class="inventory-stat-label">Entradas en pagina</p>
                <p class="inventory-stat-value text-bakery-sage">{{ $entradasPagina }}</p>
            </div>
            <div class="inventory-stat">
                <p class="inventory-stat-label">Salidas y mermas</p>
                <p class="inventory-stat-value text-bakery-terracotta">{{ $salidasPagina }}</p>
            </div>
        </div>
    </div>
        @if(session('success'))
        <div class="mb-4 flash-success text-sm rounded-xl px-4 py-3">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="mb-4 bg-bakery-terracotta/10 border border-bakery-terracotta/20 text-bakery-terracotta text-sm rounded-xl px-4 py-3">
            {{ session('error') }}
        </div>
    @endif

    {{-- Pestañas --}}
    <div class="inventory-card"
         x-data="{
             activeTab: '{{ request('tipo', '') }}',
             showFilters: window.innerWidth >= 768,
             tabs: [
                 { key: '', label: 'Todos', count: {{ $movimientos->total() }} },
                 @foreach(['entrada' => 'Entradas', 'salida' => 'Salidas', 'venta' => 'Ventas', 'produccion' => 'Producción', 'merma' => 'Mermas', 'ajuste' => 'Ajustes', 'reserva' => 'Reservas'] as $k => $l)
                 { key: '{{ $k }}', label: '{{ $l }}' },
                 @endforeach
             ]
         }"
         @resize.window="showFilters = window.innerWidth >= 768">

        {{-- Barra --}}
        <div class="border-b border-bakery-amber/10">
            <nav class="flex gap-1 overflow-x-auto px-3 pt-3 scrollbar-hide sm:px-4"
                 role="tablist"
                 aria-label="Tipos de movimiento">
                @php
                    $tabTypes = ['' => 'Todos', 'entrada' => 'Entradas', 'salida' => 'Salidas', 'venta' => 'Ventas', 'produccion' => 'Producción', 'merma' => 'Mermas', 'ajuste' => 'Ajustes', 'reserva' => 'Reservas'];
                @endphp
                @foreach($tabTypes as $key => $label)
                <a href="{{ route('control-inventario.index', array_merge(request()->except('tipo', 'page'), ['tipo' => $key])) }}"
                   role="tab"
                   aria-selected="{{ request('tipo', '') === $key ? 'true' : 'false' }}"
                   class="flex min-h-[44px] items-center gap-2 rounded-t-xl border-b-2 px-4 py-3 text-sm font-semibold transition whitespace-nowrap
                          {{ request('tipo', '') === $key
                              ? 'border-bakery-gold text-bakery-gold bg-bakery-gold/5'
                              : 'border-b-transparent text-bakery-muted hover:text-bakery-dark hover:bg-bakery-warm/70' }}">
                    {{ $label }}
                </a>
                @endforeach
            </nav>
        </div>

        {{-- Filtros movil --}}
        <div class="sm:hidden px-4 pt-3">
            <button @click="showFilters = !showFilters"
                    class="flex items-center gap-2 text-xs text-bakery-muted hover:text-bakery-dark transition w-full min-h-[44px]"
                    :aria-expanded="showFilters.toString()">
                <svg class="w-4 h-4 transition-transform duration-200"
                     :class="showFilters ? 'rotate-180' : ''"
                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
                Filtros avanzados
            </button>
        </div>

        {{-- Filtros --}}
        <div x-show="showFilters"
             x-cloak
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 -translate-y-2"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 -translate-y-2"
             class="px-4 py-4 border-t border-bakery-amber/10">
            <form method="GET" action="{{ route('control-inventario.index') }}" class="space-y-3">
                <input type="hidden" name="tipo" value="{{ request('tipo', '') }}">

                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-[minmax(220px,1.4fr)_repeat(3,minmax(150px,1fr))]">
                    <div>
                        <label for="control-inventario-ingrediente_id" class="text-xs font-medium text-bakery-muted mb-1 block">Ingrediente</label>
                        <select id="control-inventario-ingrediente_id" name="ingrediente_id" class="form-select py-2 text-sm" aria-label="Filtrar por ingrediente">
                            <option value="">Todos</option>
                            @foreach($ingredientes as $ing)
                            <option value="{{ $ing->id }}" {{ request('ingrediente_id') == $ing->id ? 'selected' : '' }}>
                                {{ $ing->nombre }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="control-inventario-desde" class="text-xs font-medium text-bakery-muted mb-1 block">Fecha desde</label>
                        <input type="date" id="control-inventario-desde" name="desde" value="{{ request('desde') }}" class="form-input py-2 text-sm" aria-label="Fecha desde">
                    </div>

                    <div>
                        <label for="control-inventario-hasta" class="text-xs font-medium text-bakery-muted mb-1 block">Fecha hasta</label>
                        <input type="date" id="control-inventario-hasta" name="hasta" value="{{ request('hasta') }}" class="form-input py-2 text-sm" aria-label="Fecha hasta">
                    </div>

                    <div class="flex items-end gap-3">
                        <label class="flex min-h-[42px] w-full items-center gap-2 rounded-xl border border-bakery-amber/20 bg-white px-3 text-sm text-bakery-muted cursor-pointer">
                            <input type="checkbox" name="incluir_revertidos" value="1" {{ request('incluir_revertidos') ? 'checked' : '' }} class="rounded border-bakery-amber/30">
                            Revertidos
                        </label>
                    </div>
                </div>

                <div class="flex flex-col gap-2 pt-2 sm:flex-row">
                    <button type="submit" class="btn-primary text-sm">Aplicar filtros</button>
                    <a href="{{ route('control-inventario.index') }}" class="btn-ghost text-sm">Limpiar</a>
                </div>
            </form>
        </div>
    </div>

    {{-- Tabla --}}
    <div class="inventory-card overflow-hidden">
        <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-bakery-warm/80 border-b border-bakery-amber/10">
                <tr class="text-left text-xs font-semibold uppercase tracking-[0.08em] text-bakery-muted">
                    <th class="px-4 py-3">Fecha</th>
                    <th class="px-4 py-3">Movimiento</th>
                    <th class="px-4 py-3">Entidad</th>
                    <th class="px-4 py-3 text-right">Cantidad</th>
                    <th class="px-4 py-3">Stock y costo</th>
                    <th class="px-4 py-3">Origen</th>
                    <th class="px-4 py-3">Usuario</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-bakery-amber/10">
                @forelse($movimientos as $mov)
                <tr class="hover:bg-bakery-warm/60 transition {{ $mov->reverted_at ? 'opacity-55' : '' }}">
                    <td class="px-4 py-4 text-bakery-muted text-xs tabular-nums">
                        <span class="block font-semibold text-bakery-dark">{{ $mov->fecha->format('d/m/Y') }}</span>
                        <span>{{ $mov->fecha->format('H:i') }}</span>
                    </td>
                    <td class="px-4 py-3">
                        <span class="text-xs font-medium px-2.5 py-1 rounded-full border {{ $mov->getTipoColor() }}">
                            {{ $mov->getTipoLabel() }}
                        </span>
                        @if($mov->categoria)
                            <span class="text-[10px] text-bakery-muted ml-1">{{ $mov->getCategoriaLabel() }}</span>
                        @endif
                        @if($mov->reverted_at)
                            <span class="text-[10px] text-bakery-terracotta ml-1">revertido</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 font-medium text-bakery-dark">
                        @if($mov->ingrediente_id)
                            {{ $mov->ingrediente->nombre ?? '-' }}
                        @elseif($mov->referencia_type && $mov->referencia_id)
                            @php
                                $refModel = $mov->referencia_type;
                                $refLabel = $mov->getReferenciaLabel();
                                $refRoute = null;
                                if ($refLabel && class_exists($refModel)) {
                                    $tableMap = [
                                        'App\\Models\\PresentacionProducto' => 'presentaciones',
                                        'App\\Models\\Producto' => 'productos',
                                        'App\\Models\\Receta' => 'recetas',
                                        'App\\Models\\Ingrediente' => 'ingredientes',
                                    ];
                                    $routeKey = $tableMap[$refModel] ?? null;
                                    if ($routeKey && Route::has($routeKey.'.show')) {
                                        $refRoute = route($routeKey.'.show', $mov->referencia_id);
                                    }
                                }
                            @endphp
                            @if($refRoute)
                                <a href="{{ $refRoute }}" class="text-bakery-gold hover:underline">{{ $refLabel }}</a>
                            @else
                                {{ $refLabel ?: class_basename($mov->referencia_type).' #'.$mov->referencia_id }}
                            @endif
                        @else
                            -
                        @endif
                    </td>
                    <td class="px-4 py-3 text-right font-semibold tabular-nums {{ in_array($mov->tipo, ['salida','merma','venta']) ? 'text-bakery-terracotta' : 'text-bakery-sage' }}">
                        @if($mov->cantidad)
                            {{ in_array($mov->tipo, ['salida','merma','venta']) ? '-' : '+' }}{{ \App\Support\DisplayNumber::quantity((float) $mov->cantidad) }}
                        @else
                            -
                        @endif
                    </td>
                    <td class="px-4 py-3 text-xs text-bakery-muted tabular-nums">
                        @if($mov->stock_anterior !== null)
                            <span class="font-medium text-bakery-dark">{{ \App\Support\DisplayNumber::quantity((float) $mov->stock_anterior) }}</span>
                            <span class="text-bakery-muted/60">a</span>
                            <span class="font-medium text-bakery-dark">{{ \App\Support\DisplayNumber::quantity((float) $mov->stock_resultante) }}</span>
                            @if($mov->costo_anterior !== null && $mov->costo_resultante !== null)
                                <span class="block mt-1 {{ (float) $mov->costo_anterior === (float) $mov->costo_resultante ? 'text-bakery-sage' : 'text-bakery-brown' }}">
                                    {{ \App\Support\DisplayNumber::unitCost((float) $mov->costo_anterior) }} &rarr; {{ \App\Support\DisplayNumber::unitCost((float) $mov->costo_resultante) }}
                                </span>
                            @endif
                        @else
                            -
                        @endif
                    </td>
                    <td class="px-4 py-3 text-xs text-bakery-muted">
                        {{ $mov->getOrigenLabel() }}
                        @if($mov->venta_id)
                            <a href="{{ route('ventas.show', $mov->venta_id) }}" class="text-bakery-gold hover:underline">#{{ $mov->venta_id }}</a>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-bakery-muted text-xs">{{ $mov->usuario->name ?? '-' }}</td>
                    <td class="px-4 py-3 text-right">
                        <a href="{{ route('control-inventario.show', $mov) }}" class="movement-row-link">Ver</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="px-4 py-12 text-center">
                        <p class="text-sm font-semibold text-bakery-dark">No hay registros de control de inventario.</p>
                        <p class="mt-1 text-sm text-bakery-muted">Prueba con otros filtros o registra entradas y mermas desde Ingredientes.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        </div>
        @if($movimientos->hasPages())
        <div class="px-4 py-3 border-t border-bakery-amber/10">
            {{ $movimientos->links('vendor.pagination.bakery') }}
        </div>
        @endif
    </div>
</div>
</div>
</x-app-layout>
