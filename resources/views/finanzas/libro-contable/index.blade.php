<x-app-layout>
<div class="finance-page">
    <div class="finance-stack">
        <header class="finance-header">
            <div>
                <p class="finance-kicker">Contaduria automatica</p>
                <h2 class="finance-title">Libro contable</h2>
                <p class="finance-description">
                    Asientos generados desde ventas, cancelaciones y gastos. Cada registro conserva origen, usuario y detalle de cuentas.
                </p>
            </div>
            <div class="finance-actions">
                <a href="{{ route('finanzas.index') }}" class="btn-ghost">Finanzas</a>
                <a href="{{ route('finanzas.libro-mayor.index') }}" class="btn-secondary">Libro mayor</a>
                <a href="{{ route('finanzas.reportes.resultados') }}" class="btn-secondary">Resultados</a>
            </div>
        </header>

        <section class="grid grid-cols-1 gap-3 md:grid-cols-3">
            <div class="finance-panel finance-panel-pad">
                <p class="finance-section-note">Asientos</p>
                <p class="mt-1 text-3xl font-bold tabular-nums text-bakery-dark">{{ $lectura['asientos'] }}</p>
            </div>
            <div class="finance-panel finance-panel-pad">
                <p class="finance-section-note">Debe</p>
                <p class="mt-1 text-3xl font-bold tabular-nums text-emerald-700">{{ \App\Support\DisplayNumber::money($lectura['debe']) }}</p>
            </div>
            <div class="finance-panel finance-panel-pad">
                <p class="finance-section-note">Haber</p>
                <p class="mt-1 text-3xl font-bold tabular-nums text-bakery-brown">{{ \App\Support\DisplayNumber::money($lectura['haber']) }}</p>
            </div>
        </section>

        <section class="finance-table-wrap">
            <div class="flex flex-col gap-3 border-b border-bakery-amber/10 px-4 py-4 md:flex-row md:items-center md:justify-between">
                <div>
                    <h3 class="finance-section-title">Asientos confirmados</h3>
                    <p class="finance-section-note">Los asientos no se editan; cualquier correccion se registra con una reversión.</p>
                </div>
                <form method="GET" action="{{ route('finanzas.libro-contable.index') }}" class="flex gap-2">
                    <select name="origen" class="form-select py-2 text-sm">
                        <option value="">Todos los origenes</option>
                        @foreach(['venta' => 'Ventas', 'cancelacion_venta' => 'Cancelaciones', 'gasto' => 'Gastos'] as $value => $label)
                            <option value="{{ $value }}" @selected(request('origen') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <button class="btn-secondary text-sm" type="submit">Filtrar</button>
                </form>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-bakery-warm/70 text-left text-xs font-semibold uppercase tracking-[0.06em] text-bakery-muted">
                        <tr>
                            <th class="px-4 py-3">Fecha</th>
                            <th class="px-4 py-3">Origen</th>
                            <th class="px-4 py-3">Descripcion</th>
                            <th class="px-4 py-3 text-right">Debe</th>
                            <th class="px-4 py-3 text-right">Haber</th>
                            <th class="px-4 py-3">Usuario</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-bakery-amber/10">
                        @forelse($asientos as $asiento)
                            @php
                                $debe = (float) $asiento->detalles->sum('debe');
                                $haber = (float) $asiento->detalles->sum('haber');
                            @endphp
                            <tr class="transition hover:bg-bakery-warm/55">
                                <td class="px-4 py-3 text-xs text-bakery-muted tabular-nums">{{ $asiento->fecha_contable->format('d/m/Y H:i') }}</td>
                                <td class="px-4 py-3">
                                    <span class="rounded-full bg-bakery-gold/10 px-2.5 py-1 text-xs font-semibold text-bakery-brown">{{ str_replace('_', ' ', $asiento->origen) }}</span>
                                </td>
                                <td class="px-4 py-3 text-bakery-dark">{{ $asiento->descripcion ?? '-' }}</td>
                                <td class="px-4 py-3 text-right font-semibold tabular-nums text-emerald-700">{{ \App\Support\DisplayNumber::money($debe) }}</td>
                                <td class="px-4 py-3 text-right font-semibold tabular-nums text-bakery-brown">{{ \App\Support\DisplayNumber::money($haber) }}</td>
                                <td class="px-4 py-3 text-xs text-bakery-muted">{{ $asiento->usuario->name ?? '-' }}</td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('finanzas.libro-contable.show', $asiento) }}" class="text-xs font-semibold text-bakery-brown hover:text-bakery-gold">Ver</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-12 text-center">
                                    <p class="font-semibold text-bakery-dark">Aun no hay asientos contables</p>
                                    <p class="mt-1 text-sm text-bakery-muted">Las ventas, cancelaciones y gastos empezaran a llenar este libro automaticamente.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($asientos->hasPages())
                <div class="border-t border-bakery-amber/10 px-4 py-3">
                    {{ $asientos->links('vendor.pagination.bakery') }}
                </div>
            @endif
        </section>
    </div>
</div>
</x-app-layout>
