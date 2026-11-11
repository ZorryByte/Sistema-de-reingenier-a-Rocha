<x-app-layout>
<div class="py-8 px-6">

    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('ingredientes.index') }}"
           class="text-bakery-muted/50 hover:text-bakery-muted transition p-1 rounded-lg hover:bg-bakery-warm">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
            </svg>
        </a>
        <div>
            <h2 class="text-2xl font-display font-bold text-bakery-dark">{{ $ingrediente->nombre }}</h2>
            <p class="text-sm text-bakery-muted mt-0.5">
                Stock actual: <span class="font-semibold {{ $ingrediente->stockBajo() ? 'text-bakery-terracotta' : 'text-bakery-dark' }}">{{ $ingrediente->stock_actual }}</span>
                &middot; M&iacute;nimo: {{ $ingrediente->stock_minimo }}
            </p>
        </div>
        <a href="{{ route('ingredientes.control-inventario.create', ['ingrediente_id' => $ingrediente->id]) }}" class="btn-primary ml-auto">
            Registrar entrada/merma
        </a>
    </div>

    {{-- Filtros --}}
    <div class="bg-white rounded-2xl shadow-sm border border-bakery-amber/20 p-4 mb-6">
        <form method="GET" action="{{ route('ingredientes.historial', $ingrediente) }}" class="flex flex-wrap items-center gap-3">
            @php
                $tipos = ['' => 'Todos', 'entrada' => 'Entradas', 'salida' => 'Salidas', 'venta' => 'Ventas', 'ajuste' => 'Ajustes', 'merma' => 'Mermas'];
            @endphp
            @foreach($tipos as $key => $label)
            <a href="{{ route('ingredientes.historial', array_merge([$ingrediente], request()->except('tipo', 'page') + ['tipo' => $key])) }}"
               class="px-3 py-1.5 text-xs font-medium rounded-full border transition
                      {{ request('tipo', '') === $key ? 'bg-bakery-gold/15 border-bakery-gold/40 text-bakery-brown' : 'bg-white border-bakery-amber/20 text-bakery-muted hover:bg-bakery-warm' }}">
                {{ $label }}
            </a>
            @endforeach

            <div class="ml-auto flex items-center gap-2">
                <input type="date" name="desde" value="{{ request('desde') }}" class="form-input py-1.5 text-xs w-auto">
                <input type="date" name="hasta" value="{{ request('hasta') }}" class="form-input py-1.5 text-xs w-auto">

                <label class="flex items-center gap-1.5 text-xs text-bakery-muted cursor-pointer">
                    <input type="checkbox" name="incluir_revertidos" value="1" {{ request('incluir_revertidos') ? 'checked' : '' }} class="rounded border-bakery-amber/30">
                    Revertidos
                </label>

                <button type="submit" class="btn-secondary py-1.5 px-3 text-xs">Filtrar</button>
                <a href="{{ route('ingredientes.historial', $ingrediente) }}" class="text-xs text-bakery-muted hover:underline">Limpiar</a>
            </div>
        </form>
    </div>

    {{-- Tabla --}}
    <div class="bg-white rounded-2xl shadow-sm border border-bakery-amber/10 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-bakery-warm border-b border-bakery-amber/10">
                <tr class="text-left text-bakery-muted">
                    <th class="px-4 py-3">Fecha</th>
                    <th class="px-4 py-3">Tipo</th>
                    <th class="px-4 py-3">Cantidad</th>
                    <th class="px-4 py-3">Stock</th>
                    <th class="px-4 py-3">Origen</th>
                    <th class="px-4 py-3">Usuario</th>
                    <th class="px-4 py-3">Notas</th>
                </tr>
            </thead>
            <tbody>
                @forelse($movimientos as $mov)
                <tr class="border-b border-bakery-amber/5 hover:bg-bakery-warm transition {{ $mov->reverted_at ? 'opacity-50' : '' }}">
                    <td class="px-4 py-3 text-bakery-muted text-xs">{{ $mov->fecha->format('d/m/Y H:i') }}</td>
                    <td class="px-4 py-3">
                        <span class="text-xs font-medium px-2.5 py-1 rounded-full border {{ $mov->getTipoColor() }}">
                            {{ $mov->getTipoLabel() }}
                        </span>
                        @if($mov->reverted_at)
                            <span class="text-[10px] text-bakery-terracotta ml-1">revertido</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 font-semibold {{ in_array($mov->tipo, ['salida','merma','venta']) ? 'text-bakery-terracotta' : 'text-bakery-sage' }}">
                        {{ in_array($mov->tipo, ['salida','merma','venta']) ? '-' : '+' }}{{ \App\Support\DisplayNumber::quantity((float) $mov->cantidad) }}
                    </td>
                    <td class="px-4 py-3 text-xs text-bakery-muted">
                        {{ \App\Support\DisplayNumber::quantity((float) $mov->stock_anterior) }} &rarr; {{ \App\Support\DisplayNumber::quantity((float) $mov->stock_resultante) }}
                        @if($mov->costo_anterior !== null && $mov->costo_resultante !== null)
                            <span class="block mt-1 {{ (float) $mov->costo_anterior === (float) $mov->costo_resultante ? 'text-bakery-sage' : 'text-bakery-brown' }}">
                                {{ \App\Support\DisplayNumber::unitCost((float) $mov->costo_anterior) }} &rarr; {{ \App\Support\DisplayNumber::unitCost((float) $mov->costo_resultante) }}
                            </span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-xs text-bakery-muted">
                        {{ $mov->getOrigenLabel() }}
                        @if($mov->venta_id)
                            <a href="{{ route('ventas.show', $mov->venta_id) }}" class="text-bakery-gold hover:underline">#{{ $mov->venta_id }}</a>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-bakery-muted text-xs">{{ $mov->usuario->name ?? '-' }}</td>
                    <td class="px-4 py-3 text-bakery-muted text-xs max-w-xs truncate">{{ $mov->notas ?? '-' }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-4 py-8 text-center text-bakery-muted/50">
                        No hay registros de control de inventario para este ingrediente.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        @if($movimientos->hasPages())
        <div class="px-4 py-3 border-t border-bakery-amber/10">
            {{ $movimientos->links('vendor.pagination.bakery') }}
        </div>
        @endif
    </div>
</div>
</x-app-layout>

