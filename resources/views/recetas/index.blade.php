<x-app-layout>
<div class="py-10 px-6 max-w-6xl mx-auto">

    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between mb-8">
        <div>
            <h1 class="text-xl font-display font-bold text-bakery-dark tracking-tight leading-tight">Recetas</h1>
            <p class="text-[13px] text-bakery-muted mt-1 leading-relaxed">Primero crea la receta. Luego la conectas a un producto y sus presentaciones.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('recetas.categorias.index') }}" class="btn-secondary">Categorias</a>
            <a href="{{ route('productos.index') }}" class="btn-secondary">Ver productos</a>
            <a href="{{ route('recetas.create') }}" class="btn-primary">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                </svg>
                Nueva receta
            </a>
        </div>
    </div>

    <form method="GET" action="{{ route('recetas.index') }}" class="form-card-compact mb-6">
        <div class="flex flex-wrap gap-3 items-end">
            <div class="flex-1 min-w-[200px]">
                <label for="recetas-q" class="text-[11px] font-semibold text-bakery-muted uppercase tracking-wide mb-1.5 block">Buscar</label>
                <input type="text" id="recetas-q" name="q" value="{{ request('q') }}"
                       class="form-input py-2.5 text-[13px]" placeholder="Nombre, producto o presentaci&oacute;n...">
            </div>
            <div>
                <label for="recetas-categoria_receta_id" class="text-[11px] font-semibold text-bakery-muted uppercase tracking-wide mb-1.5 block">Categoria</label>
                <select id="recetas-categoria_receta_id" name="categoria_receta_id" class="form-select py-2.5 text-[13px]">
                    <option value="">Todas</option>
                    <option value="sin_categoria" {{ request('categoria_receta_id') === 'sin_categoria' ? 'selected' : '' }}>Sin categoria</option>
                    @foreach($categorias as $categoria)
                        <option value="{{ $categoria->id }}" {{ (string) request('categoria_receta_id') === (string) $categoria->id ? 'selected' : '' }}>
                            {{ $categoria->nombre }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="recetas-tipo" class="text-[11px] font-semibold text-bakery-muted uppercase tracking-wide mb-1.5 block">Tipo</label>
                <select id="recetas-tipo" name="tipo" class="form-select py-2.5 text-[13px]">
                    <option value="">Todos</option>
                    <option value="default" {{ request('tipo') === 'default' ? 'selected' : '' }}>General</option>
                    <option value="presentacion" {{ request('tipo') === 'presentacion' ? 'selected' : '' }}>Por presentaci&oacute;n</option>
                    <option value="independiente" {{ request('tipo') === 'independiente' ? 'selected' : '' }}>Independiente</option>
                </select>
            </div>
            <div>
                <label for="recetas-estado" class="text-[11px] font-semibold text-bakery-muted uppercase tracking-wide mb-1.5 block">Estado</label>
                <select id="recetas-estado" name="estado" class="form-select py-2.5 text-[13px]">
                    <option value="">Todos</option>
                    <option value="activa" {{ request('estado') === 'activa' ? 'selected' : '' }}>Activas</option>
                    <option value="inactiva" {{ request('estado') === 'inactiva' ? 'selected' : '' }}>Inactivas</option>
                </select>
            </div>
            <button type="submit" class="btn-secondary py-2.5 px-4 text-[13px]">Filtrar</button>
            @if(request('q') || request('categoria_receta_id') || request('tipo') || request('estado'))
                <a href="{{ route('recetas.index') }}" class="btn-ghost text-[13px]">Limpiar</a>
            @endif
        </div>
    </form>

    <div x-data class="form-card overflow-hidden p-0">
        @if($recetas->isEmpty())
            <div class="empty-state">
                <svg class="empty-state-icon" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25"/>
                </svg>
                <p class="empty-state-text">No hay recetas registradas</p>
                <a href="{{ route('recetas.create') }}" class="text-[13px] text-bakery-gold hover:text-bakery-brown mt-2 inline-block underline underline-offset-2 decoration-bakery-gold/40">
                    Crear primera receta
                </a>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-bakery-amber/10">
                            <th class="text-left py-3.5 px-5 text-[11px] font-semibold text-bakery-muted uppercase tracking-wide">Receta</th>
                            <th class="text-left py-3.5 px-5 text-[11px] font-semibold text-bakery-muted uppercase tracking-wide">Categoria</th>
                            <th class="text-left py-3.5 px-5 text-[11px] font-semibold text-bakery-muted uppercase tracking-wide">Costo/Lote</th>
                            <th class="text-left py-3.5 px-5 text-[11px] font-semibold text-bakery-muted uppercase tracking-wide">Producto</th>
                            <th class="text-left py-3.5 px-5 text-[11px] font-semibold text-bakery-muted uppercase tracking-wide">Tipo</th>
                            <th class="text-left py-3.5 px-5 text-[11px] font-semibold text-bakery-muted uppercase tracking-wide">Ingredientes</th>
                            <th class="text-left py-3.5 px-5 text-[11px] font-semibold text-bakery-muted uppercase tracking-wide">Estado</th>
                            <th class="text-right py-3.5 px-5 text-[11px] font-semibold text-bakery-muted uppercase tracking-wide">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recetas as $receta)
                        <tr class="border-b border-bakery-amber/8 hover:bg-bakery-warm/40 transition-colors">
                            <td class="py-3.5 px-5">
                                <a href="{{ route('recetas.show', $receta) }}" class="font-medium text-bakery-dark hover:text-bakery-brown leading-snug">
                                    {{ $receta->nombre }}
                                </a>
                                @if($receta->descripcion)
                                    <p class="text-xs text-bakery-muted/50 truncate max-w-[200px] mt-0.5 leading-relaxed">{{ $receta->descripcion }}</p>
                                @endif
                            </td>
                            <td class="py-3.5 px-5">
                                @if($receta->categoriaReceta)
                                    <span class="badge-neutral">{{ $receta->categoriaReceta->nombre }}</span>
                                @else
                                    <span class="badge-warning">Sin categoria</span>
                                @endif
                            </td>
                            <td class="py-3.5 px-5">
                                @if($receta->detalles->isNotEmpty())
                                    <span class="text-sm font-semibold text-bakery-gold tabular-nums">
                                        {{ \App\Support\DisplayNumber::money($receta->costoPorLote()) }}
                                    </span>
                                @else
                                    <span class="text-xs text-bakery-muted/40">Sin costo</span>
                                @endif
                            </td>
                            <td class="py-3.5 px-5 text-[13px] text-bakery-dark">{{ $receta->producto?->nombre ?? 'Sin producto' }}</td>
                            <td class="py-3.5 px-5">
                                @if($receta->esIndependiente())
                                    <span class="badge-neutral">Independiente</span>
                                @elseif($receta->esDefault())
                                    <span class="badge-neutral">General</span>
                                @else
                                    <span class="badge-warning">{{ $receta->presentacion?->nombre ?? 'Presentaci&oacute;n' }}</span>
                                @endif
                            </td>
                            <td class="py-3.5 px-5 text-[13px] text-bakery-muted tabular-nums">{{ $receta->detalles->count() }}</td>
                            <td class="py-3.5 px-5">
                                @if($receta->activo)
                                    <span class="badge-success">Activa</span>
                                @else
                                    <span class="badge-neutral">Inactiva</span>
                                @endif
                            </td>
                            <td class="py-3.5 px-5 text-right">
                                <div class="flex items-center justify-end gap-0.5">
                                    <a href="{{ route('recetas.show', $receta) }}"
                                       class="btn-ghost px-2.5 py-1 text-xs" title="Ver">
                                        Ver
                                    </a>
                                    <a href="{{ route('recetas.edit', $receta) }}"
                                       class="btn-ghost px-2.5 py-1 text-xs text-bakery-gold hover:text-bakery-brown" title="Editar">
                                        Editar
                                    </a>
                                    @if($receta->activo)
                                        <button type="button" @click="$dispatch('open-confirm-modal', {action: '{{ route('recetas.destroy', $receta) }}', method: 'DELETE', confirmText: 'Desactivar receta', consequences: 'La receta dejará de estar disponible para nuevas ventas.'})"
                                                aria-label="Desactivar receta {{ $receta->nombre }}"
                                                class="btn-ghost px-2 py-1 text-xs text-bakery-terracotta hover:text-bakery-terracotta hover:bg-bakery-terracotta/10" title="Desactivar">
                                            Desactivar
                                        </button>
                                    @else
                                        <button type="button" @click="$dispatch('open-confirm-modal', {action: '{{ route('recetas.activar', $receta) }}', method: 'PATCH', confirmText: 'Activar receta', consequences: 'La receta volverá a estar disponible para nuevas ventas.'})"
                                                aria-label="Activar receta {{ $receta->nombre }}"
                                                class="btn-ghost px-2 py-1 text-xs text-bakery-gold hover:text-bakery-brown hover:bg-bakery-gold/10" title="Activar">
                                            Activar
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="px-5 py-3.5">
                {{ $recetas->links('vendor.pagination.bakery') }}
            </div>
        @endif
    </div>
</div>
</x-app-layout>
