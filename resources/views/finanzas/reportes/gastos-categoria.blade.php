<x-app-layout>
@php
    $maxCategoria = max(0.01, (float) $categorias->max('total'));
    $categoriaPrincipal = $categorias->first();
@endphp

<div class="finance-page">
    <div class="finance-stack">
        <header class="finance-header">
            <div>
                <p class="finance-kicker">Distribucion operativa</p>
                <h2 class="finance-title">Gastos por categoria</h2>
                <p class="finance-description">Distribucion de gastos operativos por periodo para detectar categorias que explican el mayor egreso.</p>
            </div>

            <form method="GET" action="{{ route('finanzas.reportes.gastos-categoria') }}" class="finance-filter w-full lg:w-auto">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-end">
                    <div>
                        <label for="mes" class="form-label">Periodo</label>
                        <select id="mes" name="mes" class="form-select sm:w-56" onchange="this.form.submit()">
                            @foreach($meses as $m)
                                <option value="{{ $m['value'] }}" {{ $mes == $m['value'] ? 'selected' : '' }}>{{ $m['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </form>
        </header>

        <section class="finance-hero-panel">
            <div class="grid grid-cols-1 lg:grid-cols-[minmax(0,1fr)_18rem]">
                <div class="p-5 sm:p-6">
                    <h3 class="finance-section-title">Distribucion visual</h3>
                    <p class="mt-1 text-xs text-bakery-muted">Las barras muestran que categoria explica mas salida del periodo.</p>

                    <div class="mt-5 space-y-4">
                        @forelse($categorias->take(8) as $cat)
                            @php
                                $width = $totalGeneral > 0 ? ($cat['total'] / $maxCategoria) * 100 : 0;
                                $percent = $totalGeneral > 0 ? round(($cat['total'] / $totalGeneral) * 100, 1) : 0;
                            @endphp
                            <div>
                                <div class="flex items-center justify-between gap-3 text-sm">
                                    <span class="font-semibold text-bakery-dark">{{ $cat['nombre'] }}</span>
                                    <span class="text-xs text-bakery-muted">{{ $percent }}%</span>
                                </div>
                                <div class="mt-2 h-2 overflow-hidden rounded-full bg-bakery-warm">
                                    <span class="block h-full rounded-full {{ $cat['tipo'] == 'Fijo' ? 'bg-bakery-brown' : 'bg-bakery-sage' }}" style="width: {{ $width }}%"></span>
                                </div>
                            </div>
                        @empty
                            <div class="finance-empty">
                                <p class="font-medium text-bakery-dark">Sin distribucion disponible</p>
                                <p class="mt-1 text-xs text-bakery-muted">Cuando registres gastos, este panel mostrara el peso de cada categoria.</p>
                            </div>
                        @endforelse
                    </div>
                </div>

                <aside class="finance-hero-dark p-5">
                    <p class="text-xs font-bold text-white/60">Total del periodo</p>
                    <p class="mt-3 text-3xl font-bold tabular-nums">{{ \App\Support\DisplayNumber::money($totalGeneral) }}</p>
                    <div class="mt-5 border-t border-white/10 pt-4">
                        <p class="text-xs text-white/55">Categoria principal</p>
                        <p class="mt-1 text-sm font-semibold">{{ $categoriaPrincipal['nombre'] ?? 'Sin gastos' }}</p>
                        <p class="mt-1 text-xs text-white/50">
                            @if($categoriaPrincipal)
                                {{ \App\Support\DisplayNumber::money($categoriaPrincipal['total']) }} registrados
                            @else
                                Registra gastos para calcularla.
                            @endif
                        </p>
                    </div>
                </aside>
            </div>
        </section>

        <section class="finance-table-wrap">
            <div class="flex items-center justify-between border-b border-bakery-amber/10 px-4 py-3">
                <h3 class="finance-section-title">Detalle por categoria</h3>
                <span class="text-sm font-bold text-bakery-dark tabular-nums">{{ $categorias->count() }} categorias</span>
            </div>
            <div class="divide-y divide-bakery-amber/5 p-4">
                @forelse($categorias as $cat)
                    <div class="flex flex-col gap-2 py-3 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex items-center gap-3">
                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-medium
                                {{ $cat['tipo'] == 'Fijo' ? 'bg-bakery-gold/15 text-bakery-brown' : 'bg-bakery-sage/15 text-bakery-sage' }}">
                                {{ $cat['tipo'] }}
                            </span>
                            <span class="text-sm font-medium text-bakery-dark">{{ $cat['nombre'] }}</span>
                        </div>
                        <div class="text-left sm:text-right">
                            <span class="text-sm font-semibold text-bakery-dark tabular-nums">{{ \App\Support\DisplayNumber::money($cat['total']) }}</span>
                            <span class="ml-2 text-xs text-bakery-muted">
                                {{ $totalGeneral > 0 ? round(($cat['total'] / $totalGeneral) * 100, 1) : 0 }}%
                            </span>
                        </div>
                    </div>
                @empty
                    <div class="finance-empty">
                        <p class="font-medium text-bakery-dark">No hay gastos registrados en este periodo</p>
                        <p class="mt-1 text-xs text-bakery-muted">Registra gastos para ver su distribucion por categoria.</p>
                    </div>
                @endforelse
            </div>
        </section>
    </div>
</div>
</x-app-layout>
