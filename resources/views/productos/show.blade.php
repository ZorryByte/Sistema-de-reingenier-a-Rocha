<x-app-layout>
<div class="py-8 px-6 max-w-3xl mx-auto space-y-6">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            {{-- Accessible label for screen readers --}}
            <a href="{{ route('productos.index') }}" aria-label="Volver a productos" class="text-bakery-muted/50 hover:text-bakery-muted transition p-1 rounded-lg hover:bg-bakery-warm">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
<h2 class="text-2xl font-display font-bold text-bakery-dark">{{ $producto->nombre }}</h2>
            @if($producto->activo)
                <span class="badge-success">Activo</span>
            @else
                <span class="badge-neutral">Inactivo</span>
            @endif
        </div>
        <a href="{{ route('productos.edit', $producto) }}"
            class="bg-bakery-gold hover:bg-bakery-brown text-white text-sm font-medium px-4 py-2 rounded-xl transition">
            Editar
        </a>
    </div>

    {{-- Info base --}}
    <div class="bg-white rounded-2xl shadow-sm border border-bakery-amber/10 p-6">
        <h3 class="text-sm font-semibold text-bakery-muted uppercase tracking-wide mb-4">Datos del producto</h3>
        <div class="grid grid-cols-2 gap-4 text-sm">
            <div>
                <p class="text-bakery-muted/50">Nombre</p>
                <p class="font-medium text-bakery-dark mt-0.5">{{ $producto->nombre }}</p>
            </div>
            <div>
                <p class="text-bakery-muted/50">Unidad base</p>
                <p class="font-medium text-bakery-dark mt-0.5">{{ \App\Support\UnitFormatter::short($producto->tipo_unidad) }}</p>
            </div>
        </div>
    </div>

    {{-- Presentaciones --}}
    <div class="bg-white rounded-2xl shadow-sm border border-bakery-amber/10 p-6">
        <h3 class="text-sm font-semibold text-bakery-muted uppercase tracking-wide mb-4">
            Presentaciones ({{ $producto->presentaciones->count() }})
        </h3>

        @if($producto->presentaciones->isEmpty())
            <p class="text-sm text-bakery-muted/50">Este producto no tiene presentaciones registradas.</p>
        @else
            <table class="w-full text-sm">
                <thead class="border-b border-bakery-amber/10">
                    <tr class="text-left text-bakery-muted/50">
                        <th class="pb-2">Nombre</th>
                        <th class="pb-2">Cantidad</th>
                        <th class="pb-2">Tipo</th>
                        <th class="pb-2">Precio</th>
                        <th class="pb-2">Receta</th>
                        <th class="pb-2">Estado</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($producto->presentaciones as $p)
                    <tr class="border-b border-bakery-amber/5 last:border-0">
                        <td class="py-2 font-medium text-bakery-dark">{{ $p->nombre }}</td>
                        <td class="py-2 text-bakery-muted">{{ \App\Support\DisplayNumber::quantity($p->cantidad_unidades) }} {{ \App\Support\UnitFormatter::short($producto->tipo_unidad) }}</td>
                        <td class="py-2 text-bakery-muted">{{ $p->tipoVentaLabel() }}</td>
                        <td class="py-2 text-bakery-dark">{{ \App\Support\DisplayNumber::money($p->precio) }}</td>
                        <td class="py-2">
                            @if($p->recetaActiva)
                                <a href="{{ route('recetas.edit', $p->recetaActiva) }}"
                                    class="text-bakery-gold hover:text-bakery-brown text-xs font-medium">
                                    Editar receta
                                </a>
                            @else
                                <span class="text-bakery-muted/50 text-xs">Usa receta default</span>
                            @endif
                        </td>
                        <td class="py-2">
@if($p->activo)
                                <span class="badge-success">Activa</span>
                            @else
                                <span class="badge-neutral">Inactiva</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    {{-- Receta default --}}
    <div class="bg-white rounded-2xl shadow-sm border border-bakery-amber/10 p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-semibold text-bakery-muted uppercase tracking-wide">Receta default</h3>
            @if($producto->recetaDefault)
                <a href="{{ route('recetas.edit', $producto->recetaDefault) }}"
                    class="text-bakery-gold hover:text-bakery-brown text-xs font-medium">
                    Editar receta
                </a>
            @else
                <a href="{{ route('recetas.create') }}?producto_id={{ $producto->id }}"
                    class="text-bakery-gold hover:text-bakery-brown text-xs font-medium">
                    + Crear receta
                </a>
            @endif
        </div>

        @if(!$producto->recetaDefault)
            <p class="text-sm text-bakery-muted/50">Este producto no tiene receta default registrada.</p>
        @else
            <p class="text-sm font-medium text-bakery-dark mb-3">{{ $producto->recetaDefault->nombre }}</p>

            @if($producto->recetaDefault->descripcion)
                <p class="text-sm text-bakery-muted/50 italic mb-3">{{ $producto->recetaDefault->descripcion }}</p>
            @endif

            @if($producto->recetaDefault->detallesConIngredientes->isEmpty())
                <p class="text-sm text-bakery-muted/50">La receta no tiene ingredientes cargados.</p>
            @else
                <div class="space-y-2">
                    @foreach($producto->recetaDefault->detallesConIngredientes as $det)
                    <div class="flex items-center justify-between py-2 px-3 rounded-xl bg-bakery-warm border border-bakery-amber/10">
                        <div class="flex items-center gap-2">
                            <div class="w-2 h-2 rounded-full bg-amber-400"></div>
                            <span class="text-sm font-medium text-bakery-dark">{{ $det->ingrediente?->nombre ?? 'Ingrediente eliminado' }}</span>
                        </div>
                        <div class="flex items-center gap-1">
                            <span class="text-sm font-semibold text-bakery-dark">{{ \App\Support\DisplayNumber::quantity($det->cantidad) }}</span>
                        </div>
                    </div>
                    @endforeach
                </div>
            @endif
        @endif
    </div>

</div>
</x-app-layout>
