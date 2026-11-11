<x-app-layout>
    <div class="crm-workspace">
        <header class="crm-heading">
            <h1>Clientes</h1>
            <a class="btn-primary" href="{{ route('clientes.create') }}">Nuevo cliente</a>
        </header>

        <div class="crm-surface">
            <form action="{{ route('clientes.index') }}" method="GET" class="crm-filters" role="search" aria-label="Filtrar clientes">
                <label>
                    <span class="form-label">Buscar cliente</span>
                    <input name="q" class="form-input" type="search" maxlength="120" placeholder="Nombre o teléfono" value="{{ $texto }}">
                </label>
                <label>
                    <span class="form-label">Estado</span>
                    <select name="estado" class="form-select">
                        @foreach(['activos' => 'Activos', 'archivados' => 'Archivados', 'todos' => 'Todos'] as $valor => $etiqueta)
                            <option value="{{ $valor }}" @selected($estado === $valor)>{{ $etiqueta }}</option>
                        @endforeach
                    </select>
                </label>
                <div class="crm-actions">
                    <button class="btn-secondary" type="submit">Buscar</button>
                    @if(filled($texto) || $estado !== 'activos')
                        <a class="btn-ghost" href="{{ route('clientes.index') }}">Limpiar</a>
                    @endif
                </div>
            </form>

            @if($clientes->isNotEmpty())
                <div class="crm-directory-row crm-column-labels" aria-hidden="true">
                    <span>Nombre</span><span>Teléfono</span><span>Estado</span><span></span>
                </div>
            @endif
            <ul class="divide-y divide-bakery-amber/20">
                @forelse($clientes as $cliente)
                    <li>
                        <a href="{{ route('clientes.show', $cliente) }}" class="crm-directory-row crm-row-link">
                            <span class="break-words font-semibold text-bakery-dark">{{ $cliente->nombre }}</span>
                            <span class="text-sm tabular-nums text-bakery-muted">{{ $cliente->telefono_visible }}</span>
                            <span class="text-sm {{ $cliente->archivado_at ? 'text-bakery-muted' : 'text-bakery-brown' }}">{{ $cliente->archivado_at ? 'Archivado' : 'Activo' }}</span>
                            <span class="crm-row-arrow" aria-hidden="true">›</span>
                        </a>
                    </li>
                @empty
                    <li class="crm-empty">
                        @if(filled($texto) || $estado !== 'activos')
                            <p>No hay clientes que coincidan con estos filtros.</p>
                        @else
                            <p>No hay clientes activos.</p>
                        @endif
                    </li>
                @endforelse
            </ul>
            @if($clientes->hasPages())
                <div class="crm-pagination">{{ $clientes->links() }}</div>
            @endif
        </div>
    </div>
</x-app-layout>
