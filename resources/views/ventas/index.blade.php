<x-app-layout>
<div class="py-8 px-6">

    <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold text-bakery-dark">Ventas</h2>
        <a href="{{ route('ventas.create') }}"
        class="bg-bakery-gold hover:bg-bakery-brown text-white text-sm font-medium px-4 py-2 rounded-xl transition">
            + Nueva venta
        </a>
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

    {{-- Search and filter for ventas --}}
    <form method="GET" action="{{ route('ventas.index') }}" class="mb-6 bg-white rounded-2xl shadow-sm border border-bakery-amber/10 p-4">
        <div class="flex flex-wrap gap-3 items-end">
            <div class="flex-1 min-w-[200px]">
                <label for="ventas-buscar" class="text-xs font-medium text-bakery-muted mb-1 block">Buscar</label>
                <input type="text" id="ventas-buscar" name="search" value="{{ request('search') }}"
                       class="w-full form-input py-2 px-3 text-sm"
                       placeholder="ID, factura o cajero...">
            </div>
            <div>
                <label for="ventas-fecha" class="text-xs font-medium text-bakery-muted mb-1 block">Fecha</label>
                <input type="date" id="ventas-fecha" name="fecha" value="{{ request('fecha') }}"
                       class="w-full form-input py-2 px-3 text-sm">
            </div>
            <div>
                <label for="ventas-estado" class="text-xs font-medium text-bakery-muted mb-1 block">Estado</label>
                <select id="ventas-estado" name="estado" class="form-select py-2 px-3 text-sm">
                    <option value="">Todos</option>
                    <option value="pagada" {{ request('estado') === 'pagada' ? 'selected' : '' }}>Pagada</option>
                    <option value="cancelada" {{ request('estado') === 'cancelada' ? 'selected' : '' }}>Cancelada</option>
                </select>
            </div>
            <button type="submit" class="bg-bakery-gold hover:bg-bakery-brown text-white text-sm font-medium px-4 py-2 rounded-xl transition">
                Filtrar
            </button>
            @if(request('search') || request('fecha') || request('estado'))
                <a href="{{ route('ventas.index') }}" class="btn-ghost text-sm">Limpiar</a>
            @endif
        </div>
    </form>

    {{-- Mobile-friendly table wrapper --}}
    <div class="bg-white rounded-2xl shadow-sm border border-bakery-amber/10 overflow-hidden">
        <div class="overflow-x-auto">
<table class="w-full text-sm">
            <thead class="bg-bakery-warm border-b border-bakery-amber/10">
                <tr class="text-left text-bakery-muted">
                    <th class="px-4 py-3">#</th>
                    <th class="px-4 py-3">Fecha</th>
                    <th class="px-4 py-3">Cajero</th>
                    <th class="px-4 py-3">Items</th>
                    <th class="px-4 py-3">Total</th>
                    <th class="px-4 py-3">Estado</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody>
@forelse($ventas as $venta)
                @php
                    $colores = [
                        'pagada'    => 'badge-success',
                        'cancelada' => 'badge-danger',
                    ];
                    $estadoValue = $venta->estado instanceof \App\Enums\VentaEstado ? $venta->estado->value : $venta->estado;
                @endphp
                <tr class="border-b border-bakery-amber/5 hover:bg-bakery-warm transition">
                    <td class="px-4 py-3 font-mono text-bakery-muted">#{{ $venta->id }}</td>
                    <td class="px-4 py-3 text-bakery-dark">
                        {{ $venta->created_at->format('d/m/Y H:i') }}
                    </td>
                    <td class="px-4 py-3 text-bakery-muted">{{ $venta->usuario->name }}</td>
                    <td class="px-4 py-3 text-bakery-muted">{{ $venta->detalles->count() }}</td>
                    <td class="px-4 py-3 font-semibold text-bakery-dark">
                        {{ \App\Support\DisplayNumber::money($venta->total) }}
                    </td>
                    <td class="px-4 py-3">
                        <span class="text-xs font-medium px-2.5 py-1 rounded-full {{ $colores[$estadoValue] ?? '' }}">
                            {{ $venta->estado->label() }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-right space-x-2">
                        <a href="{{ route('ventas.show', $venta) }}"
                        class="text-bakery-brown hover:text-bakery-gold text-xs">Ver</a>
                        <a href="{{ route('ventas.pdf', $venta) }}"
                        class="text-bakery-muted hover:text-bakery-brown text-xs">PDF</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-4 py-8 text-center text-bakery-muted/50">
                        {{ request()->filled('search') || request()->filled('fecha') || request()->filled('estado') ? 'No hay ventas que coincidan con los filtros.' : 'No hay ventas registradas.' }}
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        </div>
        <div class="px-4 py-3 border-t border-bakery-amber/10">
            {{ $ventas->links('vendor.pagination.bakery') }}
        </div>
    </div>
</div>
</x-app-layout>