<x-app-layout>
<div class="finance-page">
    <div class="finance-stack">
        <header class="finance-header">
            <div>
                <p class="finance-kicker">Asiento #{{ $asiento->id }}</p>
                <h2 class="finance-title">Detalle contable</h2>
                <p class="finance-description">{{ $asiento->descripcion ?? 'Asiento generado automaticamente por el sistema.' }}</p>
            </div>
            <div class="finance-actions">
                <a href="{{ route('finanzas.libro-contable.index') }}" class="btn-ghost">Volver al libro</a>
            </div>
        </header>

        <section class="finance-panel finance-panel-pad">
            <dl class="grid grid-cols-1 gap-4 md:grid-cols-4">
                <div>
                    <dt class="finance-section-note">Fecha</dt>
                    <dd class="mt-1 font-semibold text-bakery-dark">{{ $asiento->fecha_contable->format('d/m/Y H:i') }}</dd>
                </div>
                <div>
                    <dt class="finance-section-note">Origen</dt>
                    <dd class="mt-1 font-semibold text-bakery-dark">{{ str_replace('_', ' ', $asiento->origen) }}</dd>
                </div>
                <div>
                    <dt class="finance-section-note">Estado</dt>
                    <dd class="mt-1 font-semibold text-bakery-dark">{{ ucfirst($asiento->estado) }}</dd>
                </div>
                <div>
                    <dt class="finance-section-note">Usuario</dt>
                    <dd class="mt-1 font-semibold text-bakery-dark">{{ $asiento->usuario->name ?? '-' }}</dd>
                </div>
            </dl>
        </section>

        <section class="finance-table-wrap">
            <div class="border-b border-bakery-amber/10 px-4 py-4">
                <h3 class="finance-section-title">Partida doble</h3>
                <p class="finance-section-note">El total del debe debe coincidir con el total del haber.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-bakery-warm/70 text-left text-xs font-semibold uppercase tracking-[0.06em] text-bakery-muted">
                        <tr>
                            <th class="px-4 py-3">Cuenta</th>
                            <th class="px-4 py-3">Descripcion</th>
                            <th class="px-4 py-3 text-right">Debe</th>
                            <th class="px-4 py-3 text-right">Haber</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-bakery-amber/10">
                        @foreach($asiento->detalles as $detalle)
                            <tr>
                                <td class="px-4 py-3 font-semibold text-bakery-dark">{{ $detalle->cuenta->codigo }} - {{ $detalle->cuenta->nombre }}</td>
                                <td class="px-4 py-3 text-bakery-muted">{{ $detalle->descripcion ?? '-' }}</td>
                                <td class="px-4 py-3 text-right font-semibold tabular-nums text-emerald-700">{{ \App\Support\DisplayNumber::money((float) $detalle->debe) }}</td>
                                <td class="px-4 py-3 text-right font-semibold tabular-nums text-bakery-brown">{{ \App\Support\DisplayNumber::money((float) $detalle->haber) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-bakery-warm/55 font-bold text-bakery-dark">
                        <tr>
                            <td colspan="2" class="px-4 py-3 text-right">Totales</td>
                            <td class="px-4 py-3 text-right tabular-nums">{{ \App\Support\DisplayNumber::money((float) $asiento->detalles->sum('debe')) }}</td>
                            <td class="px-4 py-3 text-right tabular-nums">{{ \App\Support\DisplayNumber::money((float) $asiento->detalles->sum('haber')) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </section>
    </div>
</div>
</x-app-layout>
