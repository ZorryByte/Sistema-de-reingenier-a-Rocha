<x-app-layout>
<div class="finance-page">
    <div class="finance-stack">
        <div class="mb-1">
            <a href="{{ route('finanzas.compras.index') }}" class="inline-flex items-center gap-1 text-sm text-bakery-muted transition hover:text-bakery-brown">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                </svg>
                Volver a compras
            </a>
        </div>

        <section class="finance-hero-panel">
            <div class="grid grid-cols-1 lg:grid-cols-[minmax(0,1fr)_18rem]">
                <div class="p-5 sm:p-6">
                    <p class="finance-kicker">Recepcion trazable</p>
                    <h2 class="finance-title">Recepcion #{{ $compra->id }}</h2>
                    <p class="finance-description">
                        Ingredientes recibidos, proveedor asociado y costo registrado para inventario.
                    </p>

                    <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div>
                            <p class="text-xs font-semibold text-bakery-muted">Proveedor</p>
                            <p class="mt-1 text-sm font-semibold text-bakery-dark">{{ $compra->proveedor?->nombre ?? 'Sin proveedor' }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold text-bakery-muted">Fecha</p>
                            <p class="mt-1 text-sm font-semibold text-bakery-dark">{{ $compra->fecha->format('d/m/Y') }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold text-bakery-muted">Usuario</p>
                            <p class="mt-1 text-sm font-semibold text-bakery-dark">{{ $compra->user->name }}</p>
                        </div>
                    </div>

                    @if($compra->notas)
                        <div class="mt-5 rounded-xl border border-bakery-amber/10 bg-bakery-warm/55 p-4">
                            <p class="text-xs font-semibold text-bakery-muted">Notas</p>
                            <p class="mt-1 text-sm leading-relaxed text-bakery-dark">{{ $compra->notas }}</p>
                        </div>
                    @endif
                </div>

                <aside class="finance-hero-dark p-5">
                    <p class="text-xs font-bold text-white/60">Total recibido</p>
                    <p class="mt-3 text-3xl font-bold tabular-nums">{{ \App\Support\DisplayNumber::money($compra->total) }}</p>
                    <p class="mt-5 border-t border-white/10 pt-4 text-xs leading-relaxed text-white/60">
                        Esta compra alimenta inventario y soporte de precios para futuras compras.
                    </p>
                </aside>
            </div>
        </section>

        <section class="finance-table-wrap">
            <div class="flex items-center justify-between border-b border-bakery-amber/10 px-4 py-3">
                <h3 class="finance-section-title">Ingredientes recibidos</h3>
                <span class="text-xs font-semibold text-bakery-brown">{{ $compra->detalles->count() }} lineas</span>
            </div>
            <div class="finance-table-scroll">
                <table class="finance-table min-w-[620px]">
                    <thead>
                        <tr>
                            <th>Ingrediente</th>
                            <th>Cantidad</th>
                            <th>Precio unit.</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($compra->detalles as $detalle)
                            <tr class="finance-row">
                                <td class="font-medium text-bakery-dark">{{ $detalle->ingrediente?->nombre ?? 'Eliminado' }}</td>
                                <td class="text-bakery-dark">{{ \App\Support\DisplayNumber::quantity($detalle->cantidad) }} {{ \App\Support\UnitFormatter::short($detalle->ingrediente?->unidad_medida) }}</td>
                                <td class="text-bakery-muted tabular-nums">{{ \App\Support\DisplayNumber::unitCost($detalle->precio_unitario) }}</td>
                                <td class="font-semibold text-bakery-dark tabular-nums">{{ \App\Support\DisplayNumber::money($detalle->subtotal) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</div>
</x-app-layout>
