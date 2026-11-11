<x-app-layout>
<div class="finance-page">
    <div class="finance-stack">
        <header class="finance-header">
            <div>
                <p class="finance-kicker">Inventario valorizado</p>
                <h2 class="finance-title">Valor de inventario</h2>
                <p class="finance-description">
                    Lectura financiera del stock disponible. Ayuda a entender cuanto valor esta retenido en ingredientes y productos.
                </p>
            </div>
            <div class="finance-actions">
                <a href="{{ route('control-inventario.index') }}" class="btn-secondary">Ver detalle</a>
                <a href="{{ route('finanzas.index') }}" class="btn-ghost">Finanzas</a>
            </div>
        </header>

        @php
            $total = max(1, (float) $resumen['total']);
            $porcentajeIngredientes = ($resumen['ingredientes'] / $total) * 100;
            $porcentajeProductos = ($resumen['productos'] / $total) * 100;
        @endphp

        <section class="grid grid-cols-1 gap-5 lg:grid-cols-[20rem_minmax(0,1fr)]">
            <div class="finance-panel finance-panel-pad">
                <p class="finance-section-note">Valor total</p>
                <p class="mt-2 text-4xl font-bold tabular-nums text-bakery-dark">{{ \App\Support\DisplayNumber::money($resumen['total']) }}</p>
                <div class="mt-6 aspect-square rounded-full"
                     style="background: conic-gradient(#8f5b2d 0 {{ $porcentajeIngredientes }}%, #4f7a5a {{ $porcentajeIngredientes }}% {{ $porcentajeIngredientes + $porcentajeProductos }}%, #eadfca 0);">
                    <div class="flex h-full items-center justify-center p-8">
                        <div class="flex h-full w-full flex-col items-center justify-center rounded-full bg-white text-center">
                            <span class="text-xs font-semibold text-bakery-muted">Registros mes</span>
                            <span class="mt-1 text-2xl font-bold text-bakery-dark">{{ $resumen['movimientos_mes'] }}</span>
                        </div>
                    </div>
                </div>
                <div class="mt-5 space-y-2 text-sm">
                    <div class="flex items-center justify-between gap-3">
                        <span class="inline-flex items-center gap-2 text-bakery-muted"><span class="h-2.5 w-2.5 rounded-sm bg-bakery-brown"></span>Ingredientes</span>
                        <span class="font-semibold tabular-nums text-bakery-dark">{{ \App\Support\DisplayNumber::money($resumen['ingredientes']) }}</span>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <span class="inline-flex items-center gap-2 text-bakery-muted"><span class="h-2.5 w-2.5 rounded-sm bg-bakery-sage"></span>Productos</span>
                        <span class="font-semibold tabular-nums text-bakery-dark">{{ \App\Support\DisplayNumber::money($resumen['productos']) }}</span>
                    </div>
                </div>
            </div>

            <section class="finance-table-wrap">
                <div class="border-b border-bakery-amber/10 px-4 py-4">
                    <h3 class="finance-section-title">Mayor valor retenido</h3>
                    <p class="finance-section-note">Ordenado por stock multiplicado por costo disponible.</p>
                </div>
                <div class="divide-y divide-bakery-amber/10">
                    @forelse($resumen['items']->take(12) as $item)
                        @php($percent = $resumen['total'] > 0 ? ($item['valor'] / $resumen['total']) * 100 : 0)
                        <div class="grid gap-3 px-4 py-3 md:grid-cols-[1fr_10rem] md:items-center">
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="font-semibold text-bakery-dark">{{ $item['nombre'] }}</p>
                                    <span class="rounded-full bg-bakery-warm px-2 py-0.5 text-xs font-semibold text-bakery-muted">{{ $item['tipo'] }}</span>
                                </div>
                                <div class="mt-2 h-2 overflow-hidden rounded-full bg-bakery-amber/15">
                                    <span class="block h-full rounded-full bg-bakery-brown" style="width: {{ min(100, $percent) }}%"></span>
                                </div>
                                <p class="mt-1 text-xs text-bakery-muted">{{ \App\Support\DisplayNumber::quantity($item['stock']) }} {{ $item['unidad'] }} a {{ \App\Support\DisplayNumber::unitCost($item['costo']) }}</p>
                            </div>
                            <p class="text-right text-lg font-bold tabular-nums text-bakery-dark">{{ \App\Support\DisplayNumber::money($item['valor']) }}</p>
                        </div>
                    @empty
                        <div class="px-4 py-12 text-center">
                            <p class="font-semibold text-bakery-dark">No hay inventario valorizado</p>
                            <p class="mt-1 text-sm text-bakery-muted">Cuando existan ingredientes o productos con stock, apareceran aqui.</p>
                        </div>
                    @endforelse
                </div>
            </section>
        </section>
    </div>
</div>
</x-app-layout>
