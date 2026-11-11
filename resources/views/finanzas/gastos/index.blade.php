@php
    $serieChart = $serieSeisMeses->values();
    $seriePeriodo = $serieChart->isNotEmpty()
        ? $serieChart->first()['label'].' - '.$serieChart->last()['label']
        : 'Últimos 6 meses';
    $hayFiltros = request()->filled('desde')
        || request()->filled('hasta')
        || request()->filled('categoria_id')
        || request()->filled('proveedor_id')
        || request()->filled('metodo_pago')
        || request()->filled('estado');
@endphp

<x-app-layout>
<main class="finance-page gastos-page">
    <div class="gastos-shell finance-stack">
        <header class="finance-header">
            <div>
                <h2 class="finance-title">Gastos</h2>
                <p class="finance-description">Pagos externos con fuente y asiento contable.</p>
            </div>
            <div class="finance-actions">
                <a href="{{ route('finanzas.gastos.recordatorios.index') }}" class="btn-secondary"><x-finanzas.icon name="bell" />Recordatorios</a>
                <a href="{{ route('finanzas.gastos.create') }}" class="btn-primary"><x-finanzas.icon name="plus" />Registrar gasto</a>
            </div>
        </header>

        @if(session('success'))
            <div class="flash-success rounded-lg px-4 py-3 text-sm" role="status">{{ session('success') }}</div>
        @endif

        <section class="finance-chart-panel" aria-labelledby="gastos-seis-meses">
            <div>
                <h3 id="gastos-seis-meses" class="finance-section-title finance-icon-label"><x-finanzas.icon name="chart" />Últimos 6 meses</h3>
                <p class="finance-section-note">{{ $seriePeriodo }}</p>
            </div>

            <div class="finance-expense-chart mt-3">
                <canvas
                    role="img"
                    aria-label="Gastos por mes durante los últimos seis meses"
                    data-gastos-six-month-chart
                    data-chart='@json($serieChart)'
                ></canvas>
            </div>
        </section>

        @if($analisisPeriodo['recordatorios_pendientes'] > 0)
            <section class="flex flex-col gap-3 rounded-lg border border-bakery-gold/25 bg-bakery-gold/10 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-sm font-semibold text-bakery-dark">{{ $analisisPeriodo['recordatorios_pendientes'] }} recordatorio(s) pendiente(s)</p>
                <a href="{{ route('finanzas.gastos.recordatorios.index') }}" class="finance-icon-label text-sm font-semibold text-bakery-brown transition hover:text-bakery-dark"><x-finanzas.icon name="bell" />Revisar</a>
            </section>
        @endif

        <section class="finance-filter" aria-labelledby="gastos-filtros">
            <div class="mb-3 flex items-center justify-between gap-3">
                <h3 id="gastos-filtros" class="finance-section-title finance-icon-label"><x-finanzas.icon name="filter" />Filtros</h3>
                @if($hayFiltros)
                    <a href="{{ route('finanzas.gastos.index') }}" class="text-xs font-semibold text-bakery-brown transition hover:text-bakery-dark">Limpiar</a>
                @endif
            </div>
            <form method="GET" action="{{ route('finanzas.gastos.index') }}" class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-[repeat(6,minmax(0,1fr))_auto] xl:items-end">
                <div>
                    <label for="desde" class="form-label">Desde</label>
                    <input id="desde" type="date" name="desde" value="{{ request('desde') }}" class="form-input" @error('desde') aria-invalid="true" aria-describedby="desde-error" @enderror>
                    @error('desde') <p id="desde-error" class="form-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="hasta" class="form-label">Hasta</label>
                    <input id="hasta" type="date" name="hasta" value="{{ request('hasta') }}" class="form-input" @error('hasta') aria-invalid="true" aria-describedby="hasta-error" @enderror>
                    @error('hasta') <p id="hasta-error" class="form-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="categoria_id" class="form-label">Categoría</label>
                    <select id="categoria_id" name="categoria_id" class="form-select">
                        <option value="">Todas</option>
                        @foreach($categorias as $categoria)
                            <option value="{{ $categoria->id }}" @selected(request('categoria_id') == $categoria->id)>{{ $categoria->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="proveedor_id" class="form-label">Proveedor</label>
                    <select id="proveedor_id" name="proveedor_id" class="form-select">
                        <option value="">Todos</option>
                        @foreach($proveedores as $proveedor)
                            <option value="{{ $proveedor->id }}" @selected(request('proveedor_id') == $proveedor->id)>{{ $proveedor->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="metodo_pago" class="form-label">Método</label>
                    <select id="metodo_pago" name="metodo_pago" class="form-select">
                        <option value="">Todos</option>
                        @foreach($metodosPago as $metodo)
                            <option value="{{ $metodo->value }}" @selected(request('metodo_pago') === $metodo->value)>{{ $metodo->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="estado" class="form-label">Estado</label>
                    <select id="estado" name="estado" class="form-select">
                        <option value="">Todos</option>
                        @foreach($estados as $estado)
                            <option value="{{ $estado->value }}" @selected(request('estado') === $estado->value)>{{ $estado->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <button type="submit" class="btn-secondary w-full xl:w-auto">Aplicar</button>
                </div>
            </form>
        </section>

        <div class="grid grid-cols-1 gap-5 lg:grid-cols-[minmax(0,1fr)_20rem]">
            <section class="finance-table-wrap">
                <div class="border-b border-bakery-amber/10 px-4 py-3">
                    <h3 class="finance-section-title finance-icon-label"><x-finanzas.icon name="list" />Historial</h3>
                    <p class="finance-section-note">{{ $gastos->total() }} registros</p>
                </div>

                <div class="finance-mobile-list">
                    @forelse($gastos as $gasto)
                        <a href="{{ route('finanzas.gastos.show', $gasto) }}" class="finance-mobile-item">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-bakery-dark">{{ $gasto->concepto }}</p>
                                    <p class="mt-1 text-xs text-bakery-muted">{{ $gasto->categoria->nombre }} · {{ $gasto->fecha->format('d/m/Y') }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm font-bold tabular-nums {{ $gasto->estado->value === 'anulado' ? 'text-bakery-muted line-through' : 'text-bakery-terracotta' }}">{{ \App\Support\DisplayNumber::money($gasto->monto) }}</p>
                                    <p class="mt-1 text-[11px] font-semibold text-bakery-muted">{{ $gasto->estado->label() }}</p>
                                </div>
                            </div>
                        </a>
                    @empty
                        <div class="finance-empty">
                            <p class="font-medium text-bakery-dark">No hay gastos en esta consulta</p>
                            <p class="mt-1 text-xs text-bakery-muted">Ajusta los filtros o registra un gasto.</p>
                        </div>
                    @endforelse
                </div>

                <div class="finance-desktop-table">
                    <div class="finance-table-scroll">
                        <table class="finance-table min-w-[760px]">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Concepto</th>
                                    <th>Categoría</th>
                                    <th>Proveedor</th>
                                    <th>Estado</th>
                                    <th class="text-right">Monto</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($gastos as $gasto)
                                    <tr class="finance-row">
                                        <td>{{ $gasto->fecha->format('d/m/Y') }}</td>
                                        <td><a href="{{ route('finanzas.gastos.show', $gasto) }}" class="font-semibold text-bakery-dark underline-offset-2 hover:text-bakery-brown hover:underline">{{ $gasto->concepto }}</a></td>
                                        <td>{{ $gasto->categoria->nombre }}</td>
                                        <td class="text-bakery-muted">{{ $gasto->proveedor?->nombre ?? 'Sin proveedor' }}</td>
                                        <td>
                                            <span class="rounded-full px-2 py-0.5 text-xs font-semibold {{ $gasto->estado->value === 'anulado' ? 'finance-status-muted' : 'finance-status-success' }}">
                                                {{ $gasto->estado->label() }}
                                            </span>
                                        </td>
                                        <td class="text-right font-bold tabular-nums {{ $gasto->estado->value === 'anulado' ? 'text-bakery-muted line-through' : 'text-bakery-terracotta' }}">{{ \App\Support\DisplayNumber::money($gasto->monto) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6"><div class="finance-empty"><p class="font-medium text-bakery-dark">No hay gastos en esta consulta</p></div></td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if($gastos->hasPages())
                    <div class="border-t border-bakery-amber/10 px-4 py-3">{{ $gastos->links('vendor.pagination.bakery') }}</div>
                @endif
            </section>

            <aside class="finance-panel finance-panel-pad self-start" aria-labelledby="distribucion-gastos">
                <h3 id="distribucion-gastos" class="finance-section-title finance-icon-label"><x-finanzas.icon name="tag" />Categoría mensual</h3>
                @php $maxCategoria = max(1, (float) ($analisisPeriodo['distribucion']->max('total') ?? 1)); @endphp
                <div class="mt-4 space-y-4">
                    @forelse($analisisPeriodo['distribucion']->take(6) as $categoria)
                        <div>
                            <div class="flex items-center justify-between gap-3 text-xs">
                                <span class="truncate font-medium text-bakery-dark">{{ $categoria['nombre'] }}</span>
                                <span class="font-semibold tabular-nums text-bakery-muted">{{ \App\Support\DisplayNumber::money($categoria['total']) }}</span>
                            </div>
                            <div class="mt-2 h-2 overflow-hidden rounded-sm bg-bakery-warm">
                                <div class="h-full rounded-sm bg-bakery-terracotta" style="width: {{ max(3, ($categoria['total'] / $maxCategoria) * 100) }}%"></div>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-bakery-muted">Sin gastos registrados este mes.</p>
                    @endforelse
                </div>
                <dl class="mt-6 border-t border-bakery-amber/10 pt-4 text-xs">
                    <div class="flex justify-between gap-3 py-1.5"><dt class="text-bakery-muted">Fijos</dt><dd class="font-semibold tabular-nums">{{ \App\Support\DisplayNumber::money($analisisPeriodo['fijos']) }}</dd></div>
                    <div class="flex justify-between gap-3 py-1.5"><dt class="text-bakery-muted">Variables</dt><dd class="font-semibold tabular-nums">{{ \App\Support\DisplayNumber::money($analisisPeriodo['variables']) }}</dd></div>
                    <div class="flex justify-between gap-3 py-1.5"><dt class="text-bakery-muted">Promedio de 6 meses</dt><dd class="font-semibold tabular-nums">{{ \App\Support\DisplayNumber::money($analisisPeriodo['promedio_mensual']) }}</dd></div>
                    <p class="pt-1 text-[11px] text-bakery-muted">Incluye meses sin gastos.</p>
                </dl>
            </aside>
        </div>
    </div>
</main>
</x-app-layout>
