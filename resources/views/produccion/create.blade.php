@php
    $catalogoProduccion = $presentaciones->map(function ($p) {
        $receta = $p->recetaActiva ?? $p->producto?->recetaDefault;

        return [
            'id' => $p->id,
            'producto' => $p->producto?->nombre ?? 'Sin producto',
            'presentacion' => $p->nombre,
            'stock' => (float) $p->stock_actual,
            'stock_minimo' => (float) ($p->stock_minimo ?? 0),
            'stock_bajo' => (float) $p->stock_minimo > 0 && (float) $p->stock_actual <= (float) $p->stock_minimo,
            'tiene_receta' => (bool) $receta,
            'receta_nombre' => $receta?->nombre,
            'lote_unidades' => (float) ($receta?->lote_unidades ?? $p->cantidad_unidades ?? 0),
        ];
    })->values();
@endphp
<x-app-layout>
<div class="py-8 px-6 max-w-5xl mx-auto" x-data="produccionForm()" x-cloak>

    <div class="mb-6">
        <a href="{{ route('produccion.index') }}"
           class="inline-flex items-center gap-1.5 text-sm text-bakery-muted hover:text-bakery-dark transition"
           aria-label="Volver a producci&oacute;n">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
            </svg>
            Volver a producci&oacute;n
        </a>
    </div>

    <div class="mb-6">
        <h2 class="text-2xl font-display font-bold text-bakery-dark">Registrar producci&oacute;n</h2>
        <p class="text-sm text-bakery-muted mt-0.5">Encuentra una presentaci&oacute;n, revisa su receta y genera el lote.</p>
    </div>

    @if(session('error'))
        <div class="mb-5 bg-bakery-terracotta/10 border border-bakery-terracotta/20 text-bakery-terracotta text-sm rounded-xl px-4 py-3 flex items-start gap-2" role="alert">
            <svg class="w-4 h-4 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
            </svg>
            {{ session('error') }}
        </div>
    @endif

    @if($errors->any())
        <div class="mb-5 bg-bakery-terracotta/10 border border-bakery-terracotta/20 text-bakery-terracotta text-sm rounded-xl px-4 py-3" role="alert">
            <p class="font-semibold mb-1">Errores de validaci&oacute;n:</p>
            <ul class="list-disc list-inside space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('produccion.store') }}" id="produccion-form" class="grid grid-cols-1 lg:grid-cols-5 gap-6" @submit="isSubmitting = true">
        @csrf
        <input type="hidden" name="presentacion_id" id="presentacion_id" x-model="selectedPresentacionId">

        {{-- Catalog --}}
        <div class="lg:col-span-3 form-card">
            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3 mb-4">
                <div>
                    <h3 class="section-title mb-1">Cat&aacute;logo de producci&oacute;n</h3>
                    <p class="section-hint">Busca por producto o presentaci&oacute;n antes de preparar el lote.</p>
                </div>
                <span class="inline-flex items-center self-start px-2.5 py-1 rounded-lg bg-bakery-warm border border-bakery-amber/10 text-xs text-bakery-muted">
                    <span x-text="filteredCatalog.length"></span>&nbsp;opciones
                </span>
            </div>

            <div class="relative mb-3">
                <label for="buscar-presentacion" class="sr-only">Buscar producto o presentaci&oacute;n</label>
                <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-5 h-5 text-bakery-muted/50 pointer-events-none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input id="buscar-presentacion"
                       type="text"
                       x-model="catalogSearch"
                       placeholder="Buscar producto o presentaci&oacute;n..."
                       autocomplete="off"
                       class="form-input pl-11 py-3 text-sm">
                <button type="button"
                        x-show="catalogSearch.trim()"
                        @click="catalogSearch = ''"
                        class="absolute right-3 top-1/2 -translate-y-1/2 p-1.5 rounded-lg text-bakery-muted/50 hover:text-bakery-terracotta hover:bg-bakery-terracotta/10 transition"
                        aria-label="Limpiar b&uacute;squeda">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <div class="flex flex-wrap gap-2 mb-4">
                <template x-for="filter in quickFilters" :key="filter.key">
                    <button type="button"
                            @click="activeCatalogFilter = filter.key"
                            :class="activeCatalogFilter === filter.key
                                ? 'bg-bakery-gold/15 border-bakery-gold/40 text-bakery-brown shadow-sm'
                                : 'bg-white border-bakery-amber/20 text-bakery-muted hover:bg-bakery-warm hover:text-bakery-dark'"
                            class="inline-flex items-center h-9 px-3 text-xs font-semibold rounded-lg border transition">
                        <span x-text="filter.label"></span>
                    </button>
                </template>
            </div>

            @error('presentacion_id')
                <p class="mb-3 text-xs text-bakery-terracotta">{{ $message }}</p>
            @enderror

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 max-h-[560px] overflow-y-auto pr-1">
                <template x-for="item in filteredCatalog" :key="item.id">
                    <button type="button"
                            @click="selectPresentation(item)"
                            :aria-pressed="String(selectedPresentacionId == item.id)"
                            :class="selectedPresentacionId == item.id
                                ? 'border-bakery-gold bg-bakery-gold/10 shadow-sm ring-2 ring-bakery-gold/20'
                                : 'border-bakery-amber/15 bg-white hover:bg-bakery-warm/60 hover:border-bakery-amber/40'"
                            class="text-left rounded-xl border p-4 transition min-h-[150px] flex flex-col justify-between">
                        <div>
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-bakery-dark truncate" x-text="item.producto"></p>
                                    <p class="text-xs text-bakery-muted truncate" x-text="item.presentacion"></p>
                                </div>
                                <span class="inline-flex items-center justify-center w-7 h-7 rounded-lg bg-bakery-warm text-bakery-gold flex-shrink-0">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                    </svg>
                                </span>
                            </div>

                            <div class="mt-3 flex flex-wrap gap-1.5">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium"
                                      :class="item.tiene_receta ? 'bg-bakery-sage/10 text-bakery-sage' : 'bg-bakery-gold/10 text-bakery-brown'">
                                    <span x-text="item.tiene_receta ? 'Con receta' : 'Sin receta'"></span>
                                </span>
                                <span x-show="item.stock_bajo" class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium bg-bakery-terracotta/10 text-bakery-terracotta">
                                    Stock bajo
                                </span>
                            </div>
                        </div>

                        <div class="mt-4 grid grid-cols-2 gap-2 text-xs">
                            <div class="rounded-lg bg-bakery-warm/60 px-2 py-2">
                                <p class="text-bakery-muted">Stock</p>
                                <p class="font-bold tabular-nums" :class="item.stock_bajo ? 'text-bakery-terracotta' : 'text-bakery-dark'" x-text="formatNumber(item.stock)"></p>
                            </div>
                            <div class="rounded-lg bg-bakery-warm/60 px-2 py-2">
                                <p class="text-bakery-muted">Unid/lote</p>
                                <p class="font-bold text-bakery-gold tabular-nums" x-text="formatNumber(item.lote_unidades)"></p>
                            </div>
                        </div>
                    </button>
                </template>
            </div>

            <template x-if="filteredCatalog.length === 0">
                <div class="empty-state">
                    <svg class="empty-state-icon" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <p class="empty-state-text">No se encontraron presentaciones</p>
                </div>
            </template>
        </div>

        {{-- Production form --}}
        <div class="lg:col-span-2 space-y-5">
            <div class="form-card">
                <h3 class="section-title mb-1">Detalle del lote</h3>
                <p class="text-sm text-bakery-muted mb-5">Define cu&aacute;ntos lotes vas a producir.</p>

                <div x-show="!selectedPresentation" class="mb-5 rounded-xl border border-bakery-amber/20 bg-bakery-warm/60 px-4 py-3 text-sm text-bakery-muted">
                    Selecciona una presentaci&oacute;n del cat&aacute;logo para ver el consumo de ingredientes.
                </div>

                <div x-show="selectedPresentation" x-transition class="mb-5 rounded-xl border border-bakery-gold/20 bg-bakery-gold/5 px-4 py-3">
                    <p class="text-sm font-semibold text-bakery-dark" x-text="selectedPresentation ? selectedPresentation.producto + ' - ' + selectedPresentation.presentacion : ''"></p>
                    <p class="text-xs text-bakery-muted mt-1">
                        <span x-text="selectedPresentation && selectedPresentation.tiene_receta ? 'Receta: ' + (selectedPresentation.receta_nombre || 'Activa') : 'Sin receta asociada'"></span>
                    </p>
                </div>

                {{-- Lote info display --}}
                <div x-show="loteUnidades > 0" x-transition
                     class="mb-5 rounded-xl border border-bakery-sage/20 bg-bakery-sage/5 px-4 py-3">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-bakery-sage/15 flex items-center justify-center flex-shrink-0">
                            <svg class="w-4 h-4 text-bakery-sage" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-bakery-dark">
                                Cada lote produce <span x-text="formatNumber(loteUnidades)" class="text-bakery-sage"></span> unds.
                            </p>
                            <p class="text-xs text-bakery-muted mt-0.5" x-show="recetaNombre">
                                <span x-text="recetaNombre"></span>
                            </p>
                        </div>
                    </div>
                </div>

                {{-- Cantidad de lotes --}}
                <div class="mb-5">
                    <label class="form-label form-label-required" for="cantidad">Cantidad de lotes</label>
                    <input type="number" name="cantidad" id="cantidad" min="1"
                           x-model.number="cantidadLotes"
                           @input="debouncedLoadPreview()"
                           class="form-input @error('cantidad') border-bakery-terracotta @enderror"
                           aria-describedby="cantidad-help">
                    <p id="cantidad-help" class="text-xs text-bakery-muted mt-1.5">
                        Cada lote genera <span x-text="loteUnidades || 'X'"></span> unds.
                    </p>
                    @error('cantidad')
                        <p class="mt-1 text-xs text-bakery-terracotta">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Notas --}}
                <div>
                    <label class="form-label" for="notas">Notas <span class="font-normal text-bakery-muted/50 text-xs">(opcional)</span></label>
                    <textarea name="notas" id="notas" rows="2"
                              class="form-textarea"
                              placeholder="Observaciones sobre esta producci&oacute;n...">{{ old('notas') }}</textarea>
                </div>
            </div>

            {{-- Preview section --}}
            <div x-show="previewVisible" x-transition id="preview-section" class="form-card">

                {{-- No recipe warning --}}
                <div x-show="sinReceta" class="mb-4 bg-bakery-gold/10 border border-bakery-gold/20 text-bakery-brown text-sm rounded-xl px-4 py-3 flex items-start gap-2">
                    <svg class="w-4 h-4 mt-0.5 flex-shrink-0 text-bakery-gold" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                    </svg>
                    <div>
                        <p class="font-semibold">Stock comprado</p>
                        <p class="text-xs mt-0.5">Este producto no tiene receta. Solo se sumar&aacute; stock de presentaci&oacute;n sin consumir ingredientes.</p>
                    </div>
                </div>

                {{-- Loading state --}}
                <div x-show="loadingPreview" class="mb-4 rounded-xl border border-bakery-amber/15 bg-bakery-warm/50 px-4 py-3 flex items-center gap-3">
                    <div class="relative w-4 h-4">
                        <div class="absolute inset-0 rounded-full border-2 border-bakery-amber/20"></div>
                        <div class="absolute inset-0 rounded-full border-2 border-transparent border-t-bakery-gold animate-spin"></div>
                    </div>
                    <span class="text-sm text-bakery-muted">Verificando stock disponible...</span>
                </div>

                {{-- Error state with retry --}}
                <div x-show="previewError" class="mb-4 bg-bakery-terracotta/10 border border-bakery-terracotta/20 text-bakery-terracotta text-sm rounded-xl px-4 py-3">
                    <div class="flex items-start justify-between gap-2">
                        <div class="flex items-start gap-2">
                            <svg class="w-4 h-4 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                            </svg>
                            <span x-text="previewErrorMessage"></span>
                        </div>
                        <button type="button" @click="loadPreview()"
                                class="text-xs font-semibold text-bakery-terracotta hover:text-bakery-dark underline underline-offset-2 flex-shrink-0">
                            Reintentar
                        </button>
                    </div>
                </div>

                {{-- Production summary card --}}
                <div x-show="previewData && !sinReceta" x-transition class="mb-4 rounded-xl border border-bakery-sage/20 bg-bakery-sage/5 px-4 py-4">
                    <div class="flex items-center gap-2 mb-3">
                        <svg class="w-4 h-4 text-bakery-sage" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <h4 class="text-sm font-semibold text-bakery-dark">Resumen de producci&oacute;n</h4>
                    </div>
                    <div class="grid grid-cols-3 gap-3">
                        <div class="rounded-lg bg-white border border-bakery-sage/10 px-3 py-2 text-center">
                            <p class="text-[11px] text-bakery-muted uppercase tracking-wide">Lotes</p>
                            <p class="text-lg font-bold text-bakery-dark tabular-nums" x-text="cantidadLotes"></p>
                        </div>
                        <div class="rounded-lg bg-white border border-bakery-sage/10 px-3 py-2 text-center">
                            <p class="text-[11px] text-bakery-muted uppercase tracking-wide">Unds.</p>
                            <p class="text-lg font-bold text-bakery-sage tabular-nums" x-text="unidadesResultantes"></p>
                        </div>
                        <div class="rounded-lg bg-white border border-bakery-sage/10 px-3 py-2 text-center">
                            <p class="text-[11px] text-bakery-muted uppercase tracking-wide">Ingredientes</p>
                            <p class="text-lg font-bold text-bakery-dark tabular-nums" x-text="previewData?.consumos?.length ?? 0"></p>
                        </div>
                    </div>
                </div>

                {{-- Insufficient stock alert --}}
                <div x-show="hasInsufficientStock" x-transition
                     class="mb-4 rounded-xl border border-bakery-terracotta/20 bg-bakery-terracotta/5 px-4 py-3"
                     role="alert">
                    <div class="flex items-start gap-2">
                        <svg class="w-4 h-4 mt-0.5 flex-shrink-0 text-bakery-terracotta" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                        </svg>
                        <div>
                            <p class="text-sm font-semibold text-bakery-terracotta">Stock insuficiente</p>
                            <p class="text-xs text-bakery-muted mt-0.5">Los siguientes ingredientes no tienen stock suficiente:</p>
                            <ul class="mt-1.5 space-y-0.5">
                                <template x-for="item in insufficientItems" :key="'insuff-' + item.nombre">
                                    <li class="text-xs text-bakery-terracotta">
                                        <strong x-text="item.nombre"></strong>:
                                        necesita <span x-text="window.RochaFormat.quantity(item.necesario)"></span>
                                        <span x-text="item.unidad"></span>,
                                        disponible <span x-text="formatNumber(item.disponible)"></span>
                                        <span x-text="item.unidad"></span>
                                    </li>
                                </template>
                            </ul>
                        </div>
                    </div>
                </div>

                {{-- Consumption table --}}
                <div x-show="previewData && previewData.consumos && previewData.consumos.length > 0 && !sinReceta" class="mb-4">
                    <h4 class="text-sm font-medium text-bakery-dark mb-2">Ingredientes a consumir:</h4>
                    <div class="overflow-x-auto rounded-xl border border-bakery-amber/10">
                        <table class="w-full text-sm" role="table" aria-label="Consumo de ingredientes">
                            <thead>
                                <tr class="text-left text-bakery-muted bg-bakery-warm/50">
                                    <th class="px-3 py-2.5 text-xs font-semibold uppercase tracking-wide">Ingrediente</th>
                                    <th class="px-3 py-2.5 text-right text-xs font-semibold uppercase tracking-wide">Necesario</th>
                                    <th class="px-3 py-2.5 text-right text-xs font-semibold uppercase tracking-wide">Stock actual</th>
                                    <th class="px-3 py-2.5 text-right text-xs font-semibold uppercase tracking-wide">Despu&eacute;s</th>
                                    <th class="px-3 py-2.5 text-right text-xs font-semibold uppercase tracking-wide">Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="item in (previewData?.consumos ?? [])" :key="'cons-' + item.nombre">
                                    <tr class="border-t border-bakery-amber/10"
                                        :class="{'bg-bakery-terracotta/5': !item.suficiente}">
                                        <td class="px-3 py-2.5 font-medium text-bakery-dark" x-text="item.nombre"></td>
                                        <td class="px-3 py-2.5 text-right tabular-nums"
                                            :class="item.suficiente ? 'text-bakery-dark' : 'text-bakery-terracotta font-semibold'"
                                            x-text="window.RochaFormat.quantity(item.necesario) + ' ' + item.unidad"></td>
                                        <td class="px-3 py-2.5 text-right tabular-nums text-bakery-muted" x-text="formatNumber(item.disponible) + ' ' + item.unidad"></td>
                                        <td class="px-3 py-2.5 text-right tabular-nums font-medium"
                                            :class="(item.disponible - item.necesario) < 0 ? 'text-bakery-terracotta' : 'text-bakery-sage'"
                                            x-text="formatNumber(item.disponible - item.necesario) + ' ' + item.unidad"></td>
                                        <td class="px-3 py-2.5 text-right">
                                            <span class="inline-flex items-center gap-1 text-xs font-medium px-2 py-0.5 rounded-full"
                                                  :class="item.suficiente ? 'bg-bakery-sage/10 text-bakery-sage' : 'bg-bakery-terracotta/10 text-bakery-terracotta'">
                                                <svg x-show="item.suficiente" class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                                </svg>
                                                <svg x-show="!item.suficiente" class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                                </svg>
                                                <span x-text="item.suficiente ? 'Suficiente' : 'Insuficiente'"></span>
                                            </span>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Stock presentacion --}}
                <div x-show="previewData" class="text-sm text-bakery-muted mb-2 rounded-lg bg-bakery-warm/50 border border-bakery-amber/10 px-3 py-2">
                    <span class="font-medium">Stock actual de presentaci&oacute;n:</span>
                    <span class="font-bold text-bakery-dark tabular-nums" x-text="formatNumber(previewData?.stock_presentacion)"></span>
                    unds.
                </div>
            </div>

            {{-- Submit button --}}
            <button type="submit" id="submit-btn"
                    :disabled="isSubmitting || hasInsufficientStock || !selectedPresentacionId"
                    class="btn-primary w-full justify-center disabled:opacity-50 disabled:cursor-not-allowed"
                    :title="hasInsufficientStock ? 'Stock insuficiente para algunos ingredientes' : ''">
                <svg x-show="isSubmitting" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                <span x-text="submitButtonText"></span>
            </button>
        </div>
    </form>
</div>

<script>
const catalogoProduccion = @json($catalogoProduccion);

function produccionForm() {
    let debounceTimer = null;

    return {
        catalog: catalogoProduccion,
        catalogSearch: '',
        activeCatalogFilter: '',
        selectedPresentacionId: @js((string) old('presentacion_id', request('presentacion_id', ''))),
        cantidadLotes: {{ old('cantidad', 1) }},
        loteUnidades: 0,
        recetaNombre: '',
        sinReceta: false,
        previewVisible: false,
        loadingPreview: false,
        previewError: false,
        previewErrorMessage: '',
        previewData: null,
        hasInsufficientStock: false,
        isSubmitting: false,
        quickFilters: [
            { key: '', label: 'Todos' },
            { key: 'con_receta', label: 'Con receta' },
            { key: 'sin_receta', label: 'Sin receta' },
            { key: 'stock_bajo', label: 'Stock bajo' },
        ],

        get selectedPresentation() {
            return this.catalog.find(item => String(item.id) === String(this.selectedPresentacionId)) || null;
        },

        get filteredCatalog() {
            const q = this.catalogSearch.toLowerCase().trim();
            return this.catalog.filter(item => {
                const matchesText = !q
                    || item.producto.toLowerCase().includes(q)
                    || item.presentacion.toLowerCase().includes(q)
                    || (item.receta_nombre || '').toLowerCase().includes(q);

                const matchesFilter = this.activeCatalogFilter === ''
                    || (this.activeCatalogFilter === 'con_receta' && item.tiene_receta)
                    || (this.activeCatalogFilter === 'sin_receta' && !item.tiene_receta)
                    || (this.activeCatalogFilter === 'stock_bajo' && item.stock_bajo);

                return matchesText && matchesFilter;
            });
        },

        get submitButtonText() {
            if (this.isSubmitting) return 'Registrando...';
            if (!this.selectedPresentacionId) return 'Selecciona una presentacion';
            if (this.sinReceta) return 'Agregar lote de stock';
            return 'Generar lote de produccion';
        },

        get insufficientItems() {
            if (!this.previewData || !this.previewData.consumos) return [];
            return this.previewData.consumos.filter(item => !item.suficiente);
        },

        get unidadesResultantes() {
            if (!this.previewData) return this.cantidadLotes * this.loteUnidades;
            return this.previewData.unidades_resultantes || (this.cantidadLotes * this.loteUnidades);
        },

        init() {
            if (this.selectedPresentation) {
                this.applySelectedPresentation();
                this.loadPreview();
            }
        },

        selectPresentation(item) {
            this.selectedPresentacionId = String(item.id);
            this.applySelectedPresentation();
            this.loadPreview();
        },

        applySelectedPresentation() {
            const item = this.selectedPresentation;
            if (!item) {
                this.loteUnidades = 0;
                this.recetaNombre = '';
                this.sinReceta = false;
                return;
            }

            this.loteUnidades = parseFloat(item.lote_unidades) || 0;
            this.recetaNombre = item.tiene_receta ? 'Receta: ' + (item.receta_nombre || 'Activa') : 'Sin receta';
            this.sinReceta = !item.tiene_receta;
        },

        debouncedLoadPreview() {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => this.loadPreview(), 400);
        },

        loadPreview() {
            this.previewRequest?.abort();
            this.previewRequest = null;
            const presentacionId = this.selectedPresentacionId;
            const cantidad = this.cantidadLotes;

            if (!presentacionId || !cantidad || cantidad < 1) {
                this.previewVisible = false;
                return;
            }

            this.previewVisible = true;
            this.loadingPreview = true;
            this.previewError = false;
            this.previewData = null;
            this.hasInsufficientStock = false;

            const url = '{{ route('produccion.preview') }}?presentacion_id=' + presentacionId + '&cantidad=' + cantidad;

            const request = new AbortController();
            this.previewRequest = request;
            fetch(url, { signal: request.signal })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Error al cargar la vista previa');
                    }
                    return response.json();
                })
                .then(data => {
                    if (request.signal.aborted || this.selectedPresentacionId !== presentacionId || this.cantidadLotes !== cantidad) return;
                    this.loadingPreview = false;

                    if (data.error) {
                        this.previewError = true;
                        this.previewErrorMessage = data.error;
                        return;
                    }

                    this.previewData = data;
                    this.sinReceta = data.sin_receta || false;
                    this.recetaNombre = data.receta_nombre ? 'Receta: ' + data.receta_nombre : this.recetaNombre;
                    this.hasInsufficientStock = !this.sinReceta && data.consumos && data.consumos.some(item => !item.suficiente);
                })
                .catch(error => {
                    if (request.signal.aborted) return;
                    this.loadingPreview = false;
                    this.previewError = true;
                    this.previewErrorMessage = 'No se pudo verificar el stock. Verifica tu conexion e intenta de nuevo.';
                    console.error('Error cargando preview:', error);
                });
        },

        formatNumber(value) {
            return window.RochaFormat.quantity(Number(value || 0));
        },
    }
}
</script>
<style>
[x-cloak] { display: none !important; }
</style>
</x-app-layout>
