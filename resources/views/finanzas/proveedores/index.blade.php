<x-app-layout>
<div class="finance-page">
    <div class="finance-stack">
        <header class="finance-header">
            <div>
                <p class="finance-kicker">Catalogo financiero</p>
                <h2 class="finance-title">Proveedores</h2>
                <p class="finance-description">Lectura de proveedores para sostener historial de precios, compras y trazabilidad contable futura.</p>
            </div>
            <div class="finance-actions">
                <a href="{{ route('finanzas.proveedores.create') }}" class="btn-primary">Agregar proveedor</a>
            </div>
        </header>

        @if(session('success'))
            <div class="flash-success rounded-xl px-4 py-3 text-sm" role="alert">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="rounded-xl border border-bakery-terracotta/20 bg-bakery-terracotta/10 px-4 py-3 text-sm text-bakery-terracotta" role="alert">{{ session('error') }}</div>
        @endif

        <section class="finance-data-strip bg-white px-4 sm:grid-cols-2 sm:px-5 lg:grid-cols-4">
            <div class="finance-data-cell">
                <p class="text-xs font-semibold text-bakery-muted">Proveedores activos</p>
                <p class="mt-1 text-2xl font-bold text-bakery-dark tabular-nums">{{ $lectura['total'] }}</p>
            </div>
            <div class="finance-data-cell">
                <p class="text-xs font-semibold text-bakery-muted">Con compras</p>
                <p class="mt-1 text-2xl font-bold text-bakery-dark tabular-nums">{{ $lectura['con_compras'] }}</p>
            </div>
            <div class="finance-data-cell">
                <p class="text-xs font-semibold text-bakery-muted">Sin contacto</p>
                <p class="mt-1 text-2xl font-bold {{ $lectura['sin_contacto'] > 0 ? 'text-bakery-terracotta' : 'text-emerald-700' }} tabular-nums">{{ $lectura['sin_contacto'] }}</p>
            </div>
            <div class="finance-data-cell">
                <p class="text-xs font-semibold text-bakery-muted">Cobertura ingredientes</p>
                <p class="mt-1 text-2xl font-bold text-bakery-dark tabular-nums">{{ number_format($lectura['cobertura'], 1) }}%</p>
                <p class="mt-1 text-xs text-bakery-muted">{{ $lectura['sin_proveedor'] }} sin proveedor</p>
            </div>
        </section>

        <section class="finance-filter">
            <form method="GET" action="{{ route('finanzas.proveedores.index') }}" class="flex flex-col gap-3 sm:flex-row">
                <div class="flex-1">
                    <label for="buscar" class="form-label">Buscar proveedor</label>
                    <input id="buscar" type="text" name="buscar" value="{{ request('buscar') }}" placeholder="Nombre, telefono o email" class="form-input">
                </div>
                <div class="flex gap-2 sm:items-end">
                    <button type="submit" class="btn-secondary flex-1 sm:flex-none">Buscar</button>
                    @if(request('buscar'))
                        <a href="{{ route('finanzas.proveedores.index') }}" class="btn-ghost flex-1 sm:flex-none">Limpiar</a>
                    @endif
                </div>
            </form>
        </section>

        <section class="finance-table-wrap">
            <div class="flex flex-col gap-1 border-b border-bakery-amber/10 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h3 class="finance-section-title">Directorio de proveedores</h3>
                    <p class="finance-section-note">Contacto, compras y cobertura de ingredientes.</p>
                </div>
                <span class="text-xs font-semibold text-bakery-brown">{{ $proveedores->total() }} registros</span>
            </div>

            <div class="finance-mobile-list">
                @forelse($proveedores as $p)
                    <div class="px-4 py-3">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold text-bakery-dark">{{ $p->nombre }}</p>
                                <p class="mt-1 text-xs text-bakery-muted">{{ $p->ingredientes_count }} ingredientes - {{ $p->compras_count }} compras</p>
                            </div>
                            <div class="flex shrink-0 gap-3 text-xs font-semibold">
                                <a href="{{ route('finanzas.proveedores.show', $p) }}" class="text-bakery-brown hover:text-bakery-gold">Ver</a>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="finance-empty">
                        <div class="finance-empty-icon">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 21a8 8 0 0 1 16 0"/>
                            </svg>
                        </div>
                        <p class="font-medium text-bakery-dark">No hay proveedores registrados</p>
                        <p class="mt-1 text-xs text-bakery-muted">Agrega el primer proveedor para asociarlo a ingredientes.</p>
                        <a href="{{ route('finanzas.proveedores.create') }}" class="btn-primary mt-4 text-xs">Agregar proveedor</a>
                    </div>
                @endforelse
            </div>

            <div class="finance-desktop-table">
                <div class="finance-table-scroll">
                    <table class="finance-table min-w-[720px]">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Telefono</th>
                                <th>Email</th>
                                <th>Ingredientes</th>
                                <th>Compras</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($proveedores as $p)
                                <tr class="finance-row">
                                    <td class="font-medium text-bakery-dark">{{ $p->nombre }}</td>
                                    <td class="text-bakery-muted">{{ $p->telefono ?? 'Sin telefono' }}</td>
                                    <td class="text-bakery-muted">{{ $p->email ?? 'Sin email' }}</td>
                                    <td class="text-bakery-dark tabular-nums">{{ $p->ingredientes_count }}</td>
                                    <td class="text-bakery-dark tabular-nums">{{ $p->compras_count }}</td>
                                    <td class="text-right">
                                        <div class="flex items-center justify-end gap-3">
                                            <a href="{{ route('finanzas.proveedores.show', $p) }}" class="text-xs font-semibold text-bakery-brown hover:text-bakery-gold">Ver</a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6">
                                        <div class="finance-empty">
                                            <div class="finance-empty-icon">
                                                <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z"/>
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 21a8 8 0 0 1 16 0"/>
                                                </svg>
                                            </div>
                                            <p class="font-medium text-bakery-dark">No hay proveedores registrados</p>
                                            <p class="mt-1 text-xs text-bakery-muted">Agrega el primer proveedor para asociarlo a ingredientes.</p>
                                            <a href="{{ route('finanzas.proveedores.create') }}" class="btn-primary mt-4 text-xs">Agregar proveedor</a>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @if($proveedores->hasPages())
                <div class="border-t border-bakery-amber/10 px-4 py-3">
                    {{ $proveedores->links('vendor.pagination.bakery') }}
                </div>
            @endif
        </section>
    </div>
</div>
</x-app-layout>
