<x-app-layout>
<div class="py-10 px-6 max-w-4xl mx-auto" x-data="recetaShowSimulator()">

    {{-- Header --}}
    <div class="flex items-center gap-3 mb-10">
        <a href="{{ route('recetas.index') }}"
           class="w-9 h-9 rounded-lg flex items-center justify-center text-bakery-muted hover:text-bakery-dark hover:bg-bakery-warm transition-colors"
           aria-label="Volver">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
            </svg>
        </a>
        <div class="flex-1">
            <h1 class="text-xl font-display font-bold text-bakery-dark tracking-tight leading-tight">{{ $receta->nombre }}</h1>
            <p class="text-[13px] text-bakery-muted mt-0.5 leading-relaxed">
                {{ $receta->producto?->nombre ?? 'Independiente' }}
                @if($receta->producto)
                    @if($receta->presentacion)
                        &mdash; {{ $receta->presentacion->nombre }}
                    @else
                        &mdash; Receta default
                    @endif
                @endif
            </p>
        </div>
        @if($receta->activo)
            <span class="badge-success">Activa</span>
        @else
            <span class="badge-neutral">Inactiva</span>
        @endif
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

        {{-- Main --}}
        <div class="lg:col-span-2 space-y-8">

            {{-- Batch info card --}}
            <div class="rounded-xl border border-bakery-gold/20 bg-gradient-to-r from-bakery-gold/5 to-bakery-amber/10 px-5 py-4">
                <div class="flex items-center gap-4">
                    <div class="w-10 h-10 rounded-lg bg-bakery-gold/20 flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-bakery-gold" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-semibold text-bakery-dark">
                            1 lote = <span class="text-bakery-gold text-base">{{ $receta->lote_unidades }}</span> unds.
                        </p>
                        <p class="text-xs text-bakery-muted mt-0.5">
                            Cada vez que produzcas 1 lote, se consumen los ingredientes listados abajo y obtienes {{ $receta->lote_unidades }} unds. terminadas.
                        </p>
                    </div>
                </div>
            </div>

            {{-- Ingredientes --}}
            <div class="form-card">
                <div class="flex items-start justify-between mb-6">
                    <div>
                        <h2 class="section-title">Ingredientes</h2>
                        <p class="section-subtitle">Ingredientes del lote ({{ $receta->lote_unidades ?? '?' }} unds.)</p>
                    </div>
                    <span class="text-xs font-semibold text-bakery-muted/60 bg-bakery-warm px-2 py-0.5 rounded-md">
                        {{ $receta->detallesConIngredientes->count() }}
                    </span>
                </div>

                @if($receta->detallesConIngredientes->isEmpty())
                    <div class="empty-state">
                        <svg class="empty-state-icon" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25"/>
                        </svg>
                        <p class="empty-state-text">Sin ingredientes registrados</p>
                    </div>
                @else
                    <div class="space-y-2">
                        @foreach($receta->detallesConIngredientes as $det)
                        <div class="flex items-center justify-between py-3 px-4 rounded-xl bg-bakery-warm border border-bakery-amber/20">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-bakery-gold/15 flex items-center justify-center">
                                    <svg class="w-4 h-4 text-bakery-gold" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-bakery-dark">{{ $det->ingrediente->nombre }}</p>
                                    <p class="text-xs text-bakery-muted">{{ \App\Support\DisplayNumber::quantity($det->cantidad) }} {{ \App\Support\UnitFormatter::short($det->ingrediente->unidad_medida) }} por lote</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-semibold text-bakery-dark tabular-nums">
                                    {{ \App\Support\DisplayNumber::money($det->cantidad * ($det->ingrediente->costo_unitario ?? 0)) }}
                                </p>
                                <p class="text-[11px] text-bakery-muted">
                                    {{ \App\Support\DisplayNumber::unitCost($det->ingrediente->costo_unitario ?? 0) }}/{{ \App\Support\UnitFormatter::short($det->ingrediente->unidad_medida) }}
                                </p>
                            </div>
                        </div>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Batch Simulator --}}
            <div class="form-card">
                <div class="mb-5">
                    <h2 class="section-title">Simulador de producci&oacute;n</h2>
                    <p class="section-subtitle">Calcula consumo e ingredientes para N lotes</p>
                </div>

                <div class="flex items-center gap-3 mb-5">
                    <label for="sim-lotes" class="text-sm font-medium text-bakery-dark">Si produces</label>
                    <div class="relative">
                        <input type="number" id="sim-lotes" x-model.number="simLotes" min="1" max="100"
                               class="form-input w-20 text-center pr-8 font-semibold"
                               aria-label="Cantidad de lotes a simular">
                        <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-bakery-muted/60 pointer-events-none">lotes</span>
                    </div>
                    <span class="text-sm text-bakery-muted">
                        &rarr; produce <strong class="text-bakery-dark" x-text="simLotes * {{ $receta->lote_unidades }}"></strong> unds.
                    </span>
                </div>

                @if($receta->detallesConIngredientes->isNotEmpty())
                    <div class="space-y-2">
                        <p class="text-xs font-semibold text-bakery-muted uppercase tracking-wide mb-2">Ingredientes a consumir:</p>
                        @foreach($receta->detallesConIngredientes as $det)
                        <div class="flex items-center justify-between py-2.5 px-3 rounded-lg border border-bakery-amber/10 bg-bakery-warm/50">
                            <div class="flex items-center gap-2">
                                <div class="w-2 h-2 rounded-full bg-bakery-gold/60"></div>
                                <span class="text-sm text-bakery-dark">{{ $det->ingrediente->nombre }}</span>
                            </div>
                            <div class="flex items-center gap-4">
                                <span class="text-sm tabular-nums text-bakery-muted">
                                    <span x-text="window.RochaFormat.quantity(simLotes * {{ $det->cantidad }})"></span> {{ \App\Support\UnitFormatter::short($det->ingrediente->unidad_medida) }}
                                </span>
                                <span class="text-sm font-semibold tabular-nums text-bakery-dark">
                                    <span x-text="window.RochaFormat.money(simLotes * {{ $det->cantidad }} * {{ $det->ingrediente->costo_unitario ?? 0 }})"></span>
                                </span>
                            </div>
                        </div>
                        @endforeach
                        <div class="pt-3 border-t border-bakery-amber/20 flex items-center justify-between">
                            <span class="text-sm font-semibold text-bakery-dark">Costo total estimado</span>
                            <span class="text-lg font-bold text-bakery-gold tabular-nums">
                                <span x-text="window.RochaFormat.money(simLotes * {{ $receta->costoPorLote() }})"></span>
                            </span>
                        </div>
                    </div>
                @else
                    <p class="text-sm text-bakery-muted/60 text-center py-4">Sin ingredientes configurados para simular.</p>
                @endif
            </div>

            {{-- Tiempo --}}
            <div class="form-card">
                <h2 class="section-title mb-3">Tiempo de preparaci&oacute;n</h2>
                <p class="text-lg font-display font-bold text-bakery-gold leading-tight">
                    {{ $receta->tiempo_preparacion }} <span class="text-sm font-normal text-bakery-muted/60">minutos por lote</span>
                </p>
            </div>

            {{-- Descripci&oacute;n --}}
            @if($receta->descripcion)
                <div class="form-card">
                    <h2 class="section-title mb-4">Descripci&oacute;n</h2>
                    <p class="text-[13px] text-bakery-muted whitespace-pre-line leading-relaxed">{{ $receta->descripcion }}</p>
                </div>
            @endif
        </div>

        {{-- Sidebar --}}
        <div class="space-y-8">

            {{-- Detalles --}}
            <div class="form-card">
                <h2 class="section-title mb-4">Detalles</h2>

                <div class="space-y-0">
                    <div class="flex items-center justify-between py-3 border-b border-bakery-amber/10">
                        <span class="text-xs text-bakery-muted">Producto</span>
                        @if($receta->producto)
                            <a href="{{ route('productos.show', $receta->producto) }}"
                               class="text-[13px] font-semibold text-bakery-gold hover:text-bakery-brown transition-colors">
                                {{ $receta->producto->nombre }}
                            </a>
                        @else
                            <span class="text-[13px] font-semibold text-bakery-dark">Independiente</span>
                        @endif
                    </div>
                    <div class="flex items-center justify-between py-3 border-b border-bakery-amber/10">
                        <span class="text-xs text-bakery-muted">Categoria</span>
                        <span class="text-[13px] font-semibold text-bakery-dark">{{ $receta->categoriaReceta?->nombre ?? 'Sin categoria' }}</span>
                    </div>
                    <div class="flex items-center justify-between py-3 border-b border-bakery-amber/10">
                        <span class="text-xs text-bakery-muted">Tipo</span>
                        <span class="text-[13px] font-semibold text-bakery-dark">{{ $receta->tipoLabel() }}</span>
                    </div>
                    <div class="flex items-center justify-between py-3 border-b border-bakery-amber/10">
                        <span class="text-xs text-bakery-muted">Ingredientes</span>
                        <span class="text-[13px] font-semibold text-bakery-dark">{{ $receta->detalles->count() }}</span>
                    </div>
                    <div class="flex items-center justify-between py-3 border-b border-bakery-amber/10">
                        <span class="text-xs text-bakery-muted">Unds. por lote</span>
                        <span class="text-[13px] font-semibold text-bakery-dark">{{ $receta->lote_unidades }}</span>
                    </div>
                    <div class="flex items-center justify-between py-3">
                        <span class="text-xs text-bakery-muted">Creada</span>
                        <span class="text-[13px] font-semibold text-bakery-dark">{{ $receta->created_at->format('d/m/Y') }}</span>
                    </div>
                </div>
            </div>

            {{-- Costos --}}
            <div class="form-card bg-bakery-warm border-bakery-amber/30">
                <h2 class="section-title mb-4">Costos</h2>

                <div class="space-y-4">
                    {{-- Costo por lote - prominent --}}
                    <div class="rounded-xl bg-white border border-bakery-amber/15 px-4 py-3">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-xs font-semibold text-bakery-muted uppercase">Costo por lote</span>
                            <span class="text-lg font-bold text-bakery-gold tabular-nums">{{ \App\Support\DisplayNumber::money($receta->costoPorLote()) }}</span>
                        </div>
                        <p class="text-[11px] text-bakery-muted/60">
                            {{ $receta->lote_unidades }} unds. &middot; {{ $receta->detalles->count() }} ingredientes
                        </p>
                    </div>

                    {{-- Costo por und. --}}
                    <div class="rounded-xl bg-white border border-bakery-amber/15 px-4 py-3">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold text-bakery-muted uppercase">Costo por und.</span>
                            <span class="text-sm font-semibold text-bakery-dark tabular-nums">{{ \App\Support\DisplayNumber::unitCost($receta->costoPorUnidad()) }}</span>
                        </div>
                    </div>

                    {{-- Cost breakdown with progress bars --}}
                    @if($receta->detallesConIngredientes->isNotEmpty())
                        <div class="pt-3 border-t border-bakery-amber/20">
                            <p class="text-[11px] font-semibold text-bakery-muted uppercase tracking-wide mb-2">Desglose por ingrediente</p>
                            <div class="space-y-2">
                                @php
                                    $costoLote = $receta->costoPorLote();
                                @endphp
                                @foreach($receta->detallesConIngredientes as $det)
                                    @php
                                        $ingCosto = $det->cantidad * ($det->ingrediente->costo_unitario ?? 0);
                                        $pct = $costoLote > 0 ? ($ingCosto / $costoLote * 100) : 0;
                                    @endphp
                                    <div class="space-y-0.5">
                                        <div class="flex items-center justify-between text-[11px]">
                                            <span class="text-bakery-muted truncate">{{ $det->ingrediente->nombre }}</span>
                                            <span class="tabular-nums text-bakery-dark ml-2">{{ \App\Support\DisplayNumber::money($ingCosto) }} <span class="text-bakery-muted/50">({{ number_format($pct, 0) }}%)</span></span>
                                        </div>
                                        <div class="h-1.5 rounded-full bg-bakery-amber/10 overflow-hidden">
                                            <div class="h-full rounded-full bg-bakery-gold/50 transition-all" style="width: {{ $pct }}%"></div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Acciones --}}
            <div class="space-y-2" x-data>
                <a href="{{ route('recetas.edit', $receta) }}" class="btn-primary w-full justify-center">
                    Editar receta
                </a>
                @if($receta->producto)
                    <a href="{{ route('productos.edit', $receta->producto) }}" class="btn-secondary w-full justify-center">
                        Ir al producto
                    </a>
                @endif
                @if($receta->activo)
                    <button type="button"
                            @click="$dispatch('open-confirm-modal', {action: '{{ route('recetas.destroy', $receta) }}', method: 'DELETE', confirmText: 'Desactivar receta', consequences: 'La receta dejará de estar disponibles para nuevas producciones.'})"
                            class="btn-danger w-full justify-center mt-2">
                        Desactivar receta
                    </button>
                @else
                    <button type="button"
                            @click="$dispatch('open-confirm-modal', {action: '{{ route('recetas.activar', $receta) }}', method: 'PATCH', confirmText: 'Activar receta', consequences: 'La receta volverá a estar disponible para nuevas producciones.'})"
                            class="btn-primary w-full justify-center mt-2">
                        Activar receta
                    </button>
                @endif
            </div>
        </div>
    </div>
</div>

<script>
function recetaShowSimulator() {
    return {
        simLotes: 1,
    }
}
</script>
</x-app-layout>
