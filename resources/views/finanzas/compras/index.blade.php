<x-app-layout>
<div class="finance-page">
    <div class="finance-stack">
        <header class="finance-header">
            <div>
                <p class="finance-kicker">Compras en pausa</p>
                <h2 class="finance-title">Lectura de compras</h2>
                <p class="finance-description">Consulta historica de compras y soporte de proveedores. La creacion queda pausada hasta rediseñar el flujo completo.</p>
            </div>
        </header>

        @if(session('success'))
            <div class="flash-success rounded-xl px-4 py-3 text-sm" role="alert">{{ session('success') }}</div>
        @endif

        <section class="finance-data-strip bg-white px-4 sm:px-5">
            <div class="finance-data-cell">
                <p class="text-xs font-semibold text-bakery-muted">Recibido este mes</p>
                <p class="mt-1 text-2xl font-bold text-bakery-dark tabular-nums">{{ \App\Support\DisplayNumber::money($lectura['mes_total']) }}</p>
            </div>
            <div class="finance-data-cell">
                <p class="text-xs font-semibold text-bakery-muted">Registros del mes</p>
                <p class="mt-1 text-2xl font-bold text-bakery-dark tabular-nums">{{ $lectura['mes_registros'] }}</p>
            </div>
            <div class="finance-data-cell">
                <p class="text-xs font-semibold text-bakery-muted">Sin proveedor</p>
                <p class="mt-1 text-2xl font-bold {{ $lectura['sin_proveedor'] > 0 ? 'text-bakery-terracotta' : 'text-emerald-700' }} tabular-nums">{{ $lectura['sin_proveedor'] }}</p>
            </div>
        </section>

        <section class="finance-table-wrap">
            <div class="flex flex-col gap-1 border-b border-bakery-amber/10 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h3 class="finance-section-title">Historial de compras</h3>
                    <p class="finance-section-note">Ordenado por fecha mas reciente.</p>
                </div>
                <span class="text-xs font-semibold text-bakery-brown">{{ $compras->total() }} registros</span>
            </div>

            <div class="finance-mobile-list">
                @forelse($compras as $compra)
                    <a href="{{ route('finanzas.compras.show', $compra) }}" class="finance-mobile-item">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold text-bakery-dark">{{ $compra->proveedor?->nombre ?? 'Sin proveedor' }}</p>
                                <p class="mt-1 text-xs text-bakery-muted">{{ $compra->fecha->format('d/m/Y') }} - {{ $compra->user->name }}</p>
                            </div>
                            <p class="text-sm font-bold text-bakery-dark tabular-nums">{{ \App\Support\DisplayNumber::money($compra->total) }}</p>
                        </div>
                    </a>
                @empty
                    <div class="finance-empty">
                        <div class="finance-empty-icon">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l2.2 10.8a2 2 0 0 0 2 1.6h7.9a2 2 0 0 0 2-1.6L20.5 7H6.2"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10 20h.01M17 20h.01"/>
                            </svg>
                        </div>
                        <p class="font-medium text-bakery-dark">No hay compras registradas</p>
                        <p class="mt-1 text-xs text-bakery-muted">La creacion de compras queda pausada en esta fase.</p>
                    </div>
                @endforelse
            </div>

            <div class="finance-desktop-table">
                <div class="finance-table-scroll">
                    <table class="finance-table min-w-[620px]">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Proveedor</th>
                                <th>Total</th>
                                <th>Usuario</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($compras as $compra)
                                <tr class="finance-row">
                                    <td class="text-bakery-dark">{{ $compra->fecha->format('d/m/Y') }}</td>
                                    <td class="font-medium text-bakery-dark">{{ $compra->proveedor?->nombre ?? 'Sin proveedor' }}</td>
                                    <td class="font-semibold text-bakery-dark tabular-nums">{{ \App\Support\DisplayNumber::money($compra->total) }}</td>
                                    <td class="text-xs text-bakery-muted">{{ $compra->user->name }}</td>
                                    <td class="text-right">
                                        <a href="{{ route('finanzas.compras.show', $compra) }}" class="text-xs font-semibold text-bakery-brown hover:text-bakery-gold">Ver detalle</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5">
                                        <div class="finance-empty">
                                            <div class="finance-empty-icon">
                                                <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l2.2 10.8a2 2 0 0 0 2 1.6h7.9a2 2 0 0 0 2-1.6L20.5 7H6.2"/>
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 20h.01M17 20h.01"/>
                                                </svg>
                                            </div>
                                            <p class="font-medium text-bakery-dark">No hay compras registradas</p>
                                            <p class="mt-1 text-xs text-bakery-muted">La creacion de compras queda pausada en esta fase.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @if($compras->hasPages())
                <div class="border-t border-bakery-amber/10 px-4 py-3">
                    {{ $compras->links('vendor.pagination.bakery') }}
                </div>
            @endif
        </section>
    </div>
</div>
</x-app-layout>
