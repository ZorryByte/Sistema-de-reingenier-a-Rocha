<x-app-layout>
@php
    $comparables = $productos->whereNotNull('porcentaje');
    $minPorcentaje = min(0, (float) $comparables->min('porcentaje'));
    $maxPorcentaje = max(1, (float) $comparables->max('porcentaje'));
    $escala = $maxPorcentaje - $minPorcentaje;
    $cero = -$minPorcentaje / $escala * 100;
    $productosBajoMargen = $comparables->filter(fn ($p) => $p['porcentaje'] < 30)->count();
    $productosBuenMargen = $comparables->filter(fn ($p) => $p['porcentaje'] >= 50)->count();
@endphp

<div class="finance-page">
    <div class="finance-stack">
        <header class="finance-header">
            <div>
                <p class="finance-kicker">Margen estimado</p>
                <h2 class="finance-title">Rentabilidad por producto</h2>
                <p class="finance-description">Margen calculado con receta activa y precio vigente. Sirve para detectar productos que necesitan revision de costo o precio.</p>
            </div>
        </header>

        <section class="finance-hero-panel">
            <div class="grid grid-cols-1 lg:grid-cols-[minmax(0,1fr)_18rem]">
                <div class="p-5 sm:p-6">
                    <h3 class="finance-section-title">Mapa de margen (%)</h3>
                    <p class="mt-1 text-xs text-bakery-muted">Los productos con menor margen quedan visibles para revisar receta, costo o precio.</p>

                    <div class="mt-5 space-y-4">
                        @forelse($comparables->sortBy('porcentaje')->take(8) as $p)
                            @php
                                $width = abs($p['porcentaje']) / $escala * 100;
                                $left = $cero - ($p['porcentaje'] < 0 ? $width : 0);
                                $barClass = $p['porcentaje'] >= 50 ? 'bg-emerald-600' : ($p['porcentaje'] >= 30 ? 'bg-bakery-brown' : 'bg-bakery-terracotta');
                            @endphp
                            <div>
                                <div class="flex items-center justify-between gap-3 text-sm">
                                    <span class="min-w-0 break-words font-semibold text-bakery-dark">{{ $p['producto'] }} - {{ $p['presentacion'] }}</span>
                                    <span class="text-xs font-semibold tabular-nums {{ $p['porcentaje'] >= 30 ? 'text-emerald-700' : 'text-red-700' }}">{{ $p['porcentaje'] }}%</span>
                                </div>
                                <div class="relative mt-2 h-2 rounded-full bg-bakery-warm" aria-hidden="true">
                                    <span class="absolute h-full rounded-full {{ $barClass }}" style="left: {{ $left }}%; width: {{ $width }}%"></span>
                                    <span class="absolute inset-y-0 w-px bg-bakery-dark" style="left: {{ $cero }}%"></span>
                                </div>
                            </div>
                        @empty
                            <div class="finance-empty">
                                <p class="font-medium text-bakery-dark">No hay margen calculado</p>
                                <p class="mt-1 text-xs text-bakery-muted">Configura recetas activas y precios para ver rentabilidad.</p>
                            </div>
                        @endforelse
                    </div>
                </div>

                <aside class="finance-hero-dark p-5">
                    <p class="text-xs font-bold text-white/60">Lectura rapida</p>
                    <dl class="mt-4 space-y-4">
                        <div>
                            <dt class="text-xs text-white/55">Buen margen</dt>
                            <dd class="mt-1 text-2xl font-bold tabular-nums">{{ $productosBuenMargen }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-white/55">Revisar precio o receta</dt>
                            <dd class="mt-1 text-2xl font-bold tabular-nums">{{ $productosBajoMargen }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-white/55">Total analizado</dt>
                            <dd class="mt-1 text-sm font-semibold">{{ $comparables->count() }} presentaciones</dd>
                        </div>
                    </dl>
                </aside>
            </div>
        </section>

        <section class="finance-table-wrap">
            <div class="flex items-center justify-between border-b border-bakery-amber/10 px-4 py-3">
                <h3 class="finance-section-title">Detalle de rentabilidad</h3>
                <span class="text-xs font-semibold text-bakery-brown">{{ $productos->count() }} registros</span>
            </div>
            <div class="finance-table-scroll" tabindex="0" role="region" aria-label="Detalle de rentabilidad">
                <table class="finance-table min-w-[680px]">
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th>Presentacion</th>
                            <th>Costo</th>
                            <th>Precio</th>
                            <th>Margen</th>
                            <th>% Margen</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($productos as $p)
                            <tr class="finance-row">
                                <td class="font-medium text-bakery-dark">{{ $p['producto'] }}</td>
                                <td class="text-bakery-muted">{{ $p['presentacion'] }}</td>
                                <td class="text-bakery-muted tabular-nums">{{ $p['costo'] === null ? 'Sin costo calculado' : \App\Support\DisplayNumber::money($p['costo']) }}</td>
                                <td class="text-bakery-dark tabular-nums">{{ \App\Support\DisplayNumber::money($p['precio']) }}</td>
                                <td class="font-semibold tabular-nums {{ $p['margen'] === null ? 'text-bakery-muted' : ($p['margen'] >= 0 ? 'text-emerald-700' : 'text-red-700') }}">
                                    {{ $p['margen'] === null ? 'No calculable' : \App\Support\DisplayNumber::money($p['margen']) }}
                                </td>
                                <td>
                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium
                                        {{ $p['porcentaje'] === null ? 'bg-bakery-warm text-bakery-muted' : ($p['porcentaje'] >= 50 ? 'bg-emerald-50 text-emerald-700' : ($p['porcentaje'] >= 30 ? 'bg-amber-50 text-amber-700' : 'bg-red-50 text-red-700')) }}">
                                        {{ $p['porcentaje'] === null ? 'No calculable' : $p['porcentaje'].'%' }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6">
                                    <div class="finance-empty">
                                        <p class="font-medium text-bakery-dark">No hay productos con receta configurada</p>
                                        <p class="mt-1 text-xs text-bakery-muted">Asocia recetas activas para calcular margen por presentacion.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</div>
</x-app-layout>
