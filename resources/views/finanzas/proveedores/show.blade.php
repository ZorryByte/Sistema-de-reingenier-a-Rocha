<x-app-layout>
@php
    $totalCompras = (float) $proveedor->compras->sum('total');
    $ultimoMovimiento = $proveedor->compras->first();
@endphp

<div class="finance-page">
    <div class="finance-stack">
        <div class="mb-1">
            <div class="flex items-center justify-between gap-3">
                <a href="{{ route('finanzas.proveedores.index') }}" class="inline-flex items-center gap-1 text-sm text-bakery-muted transition hover:text-bakery-brown">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                    </svg>
                    Volver a proveedores
                </a>
                <a href="{{ route('finanzas.proveedores.edit', $proveedor) }}" class="btn-secondary text-sm">Editar proveedor</a>
            </div>
        </div>

        @if(session('success'))
            <div class="flash-success rounded-lg px-4 py-3 text-sm" role="status">{{ session('success') }}</div>
        @endif

        <section class="finance-hero-panel">
            <div class="grid grid-cols-1 lg:grid-cols-[minmax(0,1fr)_19rem]">
                <div class="p-5 sm:p-6">
                    <p class="finance-kicker">Proveedor</p>
                    <h2 class="finance-title">{{ $proveedor->nombre }}</h2>
                    <p class="finance-description">
                        Contacto, compras e historial de precios asociados a este proveedor.
                    </p>

                    <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <p class="text-xs font-semibold text-bakery-muted">Telefono</p>
                            <p class="mt-1 text-sm font-semibold text-bakery-dark">{{ $proveedor->telefono ?? 'Sin telefono' }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold text-bakery-muted">Email</p>
                            <p class="mt-1 text-sm font-semibold text-bakery-dark">{{ $proveedor->email ?? 'Sin email' }}</p>
                        </div>
                    </div>

                    @if($proveedor->notas)
                        <div class="mt-5 rounded-xl border border-bakery-amber/10 bg-bakery-warm/55 p-4">
                            <p class="text-xs font-semibold text-bakery-muted">Notas</p>
                            <p class="mt-1 text-sm leading-relaxed text-bakery-dark">{{ $proveedor->notas }}</p>
                        </div>
                    @endif
                </div>

                <aside class="finance-hero-dark p-5">
                    <p class="text-xs font-bold text-white/60">Resumen de proveedor</p>
                    <dl class="mt-4 space-y-4">
                        <div>
                            <dt class="text-xs text-white/55">Compras recientes</dt>
                            <dd class="mt-1 text-2xl font-bold tabular-nums">{{ $proveedor->compras->count() }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-white/55">Total visible</dt>
                            <dd class="mt-1 text-2xl font-bold tabular-nums">{{ \App\Support\DisplayNumber::money($totalCompras) }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-white/55">Ultima compra</dt>
                            <dd class="mt-1 text-sm font-semibold">{{ $ultimoMovimiento?->fecha?->format('d/m/Y') ?? 'Sin compras' }}</dd>
                        </div>
                    </dl>
                </aside>
            </div>
        </section>

        <section class="border-y border-bakery-amber/15 py-5">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h3 class="finance-section-title">Ingredientes habituales</h3>
                    <p class="finance-section-note">Asignados desde el modulo Ingredientes.</p>
                </div>
                <span class="text-xs font-semibold text-bakery-brown">{{ $proveedor->ingredientes->count() }} asignados</span>
            </div>

            @if($proveedor->ingredientes->isEmpty())
                <p class="mt-4 text-sm text-bakery-muted">Este proveedor aun no tiene ingredientes asociados.</p>
            @else
                <div class="mt-4 flex flex-wrap gap-2">
                    @foreach($proveedor->ingredientes as $ingrediente)
                        <a href="{{ route('ingredientes.edit', $ingrediente) }}" class="rounded-full border border-bakery-amber/20 px-3 py-1.5 text-xs font-semibold text-bakery-dark transition hover:border-bakery-gold hover:bg-bakery-gold/10">
                            {{ $ingrediente->nombre }}
                        </a>
                    @endforeach
                </div>
            @endif
        </section>

        @if($proveedor->compras->isNotEmpty())
            <section class="finance-table-wrap">
                <div class="flex items-center justify-between border-b border-bakery-amber/10 px-4 py-3">
                    <h3 class="finance-section-title">Compras recientes</h3>
                    <span class="text-xs font-semibold text-bakery-brown">{{ $proveedor->compras->count() }} registros</span>
                </div>
                <div class="finance-table-scroll">
                    <table class="finance-table min-w-[620px]">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Total</th>
                                <th>Ingredientes</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($proveedor->compras as $compra)
                                <tr class="finance-row">
                                    <td class="text-bakery-dark">{{ $compra->fecha->format('d/m/Y') }}</td>
                                    <td class="font-semibold text-bakery-dark tabular-nums">{{ \App\Support\DisplayNumber::money($compra->total) }}</td>
                                    <td class="text-xs text-bakery-muted">
                                        @foreach($compra->detalles->take(3) as $d)
                                            {{ $d->ingrediente?->nombre }} ({{ \App\Support\DisplayNumber::quantity($d->cantidad) }})@if(! $loop->last), @endif
                                        @endforeach
                                        @if($compra->detalles->count() > 3)
                                            +{{ $compra->detalles->count() - 3 }} mas
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @endif

        @if($historialPrecios->isNotEmpty())
            <section class="finance-table-wrap">
                <div class="flex items-center justify-between border-b border-bakery-amber/10 px-4 py-3">
                    <h3 class="finance-section-title">Historial de precios</h3>
                    <span class="text-xs font-semibold text-bakery-brown">{{ $historialPrecios->count() }} registros</span>
                </div>
                <div class="finance-table-scroll">
                    <table class="finance-table min-w-[620px]">
                        <thead>
                            <tr>
                                <th>Ingrediente</th>
                                <th>Precio unitario</th>
                                <th>Fecha</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($historialPrecios as $hp)
                                <tr class="finance-row">
                                    <td class="font-medium text-bakery-dark">{{ $hp->ingrediente?->nombre }}</td>
                                    <td class="font-semibold text-bakery-dark tabular-nums">{{ \App\Support\DisplayNumber::unitCost($hp->precio_unitario) }}</td>
                                    <td class="text-bakery-muted">{{ $hp->compra->fecha->format('d/m/Y') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @endif
    </div>
</div>
</x-app-layout>
