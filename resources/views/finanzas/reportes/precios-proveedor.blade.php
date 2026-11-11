<x-app-layout>
@php
    $maxPrecio = max(0.0001, (float) $historial->max('precio_unitario'));
    $minPrecio = (float) ($historial->min('precio_unitario') ?? 0);
    $ultimoPrecio = $historial->first();
@endphp

<div class="finance-page">
    <div class="finance-stack">
        <header class="finance-header">
            <div>
                <p class="finance-kicker">Historial de costos</p>
                <h2 class="finance-title">Precios proveedor</h2>
                <p class="finance-description">Comparacion de precios unitarios registrados por ingrediente y proveedor para decidir compras futuras.</p>
            </div>

            <form method="GET" action="{{ route('finanzas.reportes.precios-proveedor') }}" class="finance-filter w-full lg:w-auto">
                <label for="ingrediente_id" class="form-label">Ingrediente</label>
                <select id="ingrediente_id" name="ingrediente_id" class="form-select sm:w-72" onchange="this.form.submit()">
                    <option value="">Seleccionar ingrediente</option>
                    @foreach($ingredientes as $ing)
                        <option value="{{ $ing->id }}" {{ $ingredienteId == $ing->id ? 'selected' : '' }}>{{ $ing->nombre }}</option>
                    @endforeach
                </select>
            </form>
        </header>

        @if($historial->isNotEmpty())
            <section class="finance-hero-panel">
                <div class="grid grid-cols-1 lg:grid-cols-[minmax(0,1fr)_18rem]">
                    <div class="p-5 sm:p-6">
                        <h3 class="finance-section-title">Variacion de precio</h3>
                        <p class="mt-1 text-xs text-bakery-muted">Compara los precios unitarios registrados para decidir futuras compras.</p>

                        <div class="mt-5 space-y-4">
                            @foreach($historial->take(8) as $h)
                                @php($width = ((float) $h->precio_unitario / $maxPrecio) * 100)
                                <div>
                                    <div class="flex items-center justify-between gap-3 text-sm">
                                        <span class="min-w-0 truncate font-semibold text-bakery-dark">{{ $h->compra->proveedor?->nombre ?? 'Sin proveedor' }}</span>
                                        <span class="text-xs font-semibold text-bakery-dark tabular-nums">{{ \App\Support\DisplayNumber::unitCost($h->precio_unitario) }}</span>
                                    </div>
                                    <div class="mt-2 h-2 overflow-hidden rounded-full bg-bakery-warm">
                                        <span class="block h-full rounded-full bg-bakery-brown" style="width: {{ $width }}%"></span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <aside class="finance-hero-dark p-5">
                        <p class="text-xs font-bold text-white/60">Referencia actual</p>
                        <p class="mt-3 text-3xl font-bold tabular-nums">{{ \App\Support\DisplayNumber::unitCost((float) $ultimoPrecio->precio_unitario) }}</p>
                        <div class="mt-5 border-t border-white/10 pt-4 text-xs text-white/60">
                            <p>Precio menor: {{ \App\Support\DisplayNumber::unitCost($minPrecio) }}</p>
                            <p class="mt-2">Registros visibles: {{ $historial->count() }}</p>
                        </div>
                    </aside>
                </div>
            </section>

            <section class="finance-table-wrap">
                <div class="flex items-center justify-between border-b border-bakery-amber/10 px-4 py-3">
                    <h3 class="finance-section-title">Detalle de compras</h3>
                    <span class="text-xs font-semibold text-bakery-brown">{{ $historial->count() }} registros</span>
                </div>
                <div class="finance-table-scroll">
                    <table class="finance-table min-w-[560px]">
                        <thead>
                            <tr>
                                <th>Proveedor</th>
                                <th>Precio unitario</th>
                                <th>Cantidad</th>
                                <th>Fecha</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($historial as $h)
                                <tr class="finance-row">
                                    <td class="font-medium text-bakery-dark">{{ $h->compra->proveedor?->nombre ?? 'Sin proveedor' }}</td>
                                    <td class="font-semibold text-bakery-dark tabular-nums">{{ \App\Support\DisplayNumber::unitCost($h->precio_unitario) }}</td>
                                    <td class="text-bakery-muted tabular-nums">{{ \App\Support\DisplayNumber::quantity($h->cantidad) }}</td>
                                    <td class="text-bakery-muted">{{ $h->compra->fecha->format('d/m/Y') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @elseif($ingredienteId)
            <section class="finance-panel">
                <div class="finance-empty">
                    <p class="font-medium text-bakery-dark">No hay compras registradas para este ingrediente</p>
                    <p class="mt-1 text-xs text-bakery-muted">Cuando registres compras con este ingrediente, aparecera aqui el historial de precios.</p>
                </div>
            </section>
        @else
            <section class="finance-panel">
                <div class="finance-empty">
                    <p class="font-medium text-bakery-dark">Selecciona un ingrediente</p>
                    <p class="mt-1 text-xs text-bakery-muted">El historial se carga solo para el ingrediente elegido.</p>
                </div>
            </section>
        @endif
    </div>
</div>
</x-app-layout>
