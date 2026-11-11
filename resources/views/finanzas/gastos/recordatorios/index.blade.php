<x-app-layout>
<main class="finance-page gastos-page">
    <div class="gastos-shell finance-stack">
        <header class="finance-header">
            <div>
                <h2 class="finance-title">Recordatorios</h2>
                <p class="finance-description">Pagos frecuentes sin registro automático.</p>
            </div>
            <div class="finance-actions">
                <a href="{{ route('finanzas.gastos.index') }}" class="btn-secondary"><x-finanzas.icon name="arrow-left" />Volver a gastos</a>
                <a href="{{ route('finanzas.gastos.recordatorios.create') }}" class="btn-primary"><x-finanzas.icon name="plus" />Crear recordatorio</a>
            </div>
        </header>

        @if(session('success'))
            <div
                x-data="{ visible: true }"
                x-init="setTimeout(() => visible = false, 4200)"
                x-show="visible"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 translate-y-2"
                x-transition:enter-end="opacity-100 translate-y-0"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 translate-y-0"
                x-transition:leave-end="opacity-0 translate-y-2"
                class="finance-toast"
                role="status"
                aria-live="polite"
            >
                <span class="font-semibold">{{ session('success') }}</span>
                <button type="button" class="ml-3 rounded-md px-2 py-1 text-xs font-semibold transition hover:bg-bakery-sage/10" @click="visible = false">Cerrar</button>
            </div>
        @endif
        @error('recordatorio')<div class="rounded-lg border border-bakery-terracotta/25 bg-bakery-terracotta/10 px-4 py-3 text-sm text-bakery-terracotta" role="alert">{{ $message }}</div>@enderror

        <section class="finance-table-wrap">
            <div class="border-b border-bakery-amber/10 px-4 py-3">
                <h3 class="finance-section-title finance-icon-label"><x-finanzas.icon name="calendar" />Calendario</h3>
            </div>
            <div class="divide-y divide-bakery-amber/10">
                @forelse($recordatorios as $recordatorio)
                    <div class="grid gap-4 px-4 py-4 md:grid-cols-[minmax(0,1fr)_auto_auto] md:items-center">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="truncate text-sm font-semibold text-bakery-dark">{{ $recordatorio->concepto }}</p>
                                <span class="rounded-full px-2 py-0.5 text-[11px] font-semibold {{ $recordatorio->activo ? 'finance-status-success' : 'finance-status-muted' }}">{{ $recordatorio->activo ? 'Activo' : 'Pausado' }}</span>
                            </div>
                            <p class="mt-1 text-xs text-bakery-muted">{{ $recordatorio->categoria->nombre }} · cada {{ $recordatorio->intervalo }} {{ $recordatorio->unidad_frecuencia->label() }}</p>
                            @if($recordatorio->proveedor)<p class="mt-1 text-xs text-bakery-muted">{{ $recordatorio->proveedor->nombre }}</p>@endif
                        </div>
                        <div class="md:text-right">
                            <p class="text-xs font-semibold text-bakery-muted">Próxima</p>
                            <p class="mt-1 text-sm font-bold tabular-nums {{ $recordatorio->activo && $recordatorio->proxima_fecha->isPast() ? 'text-bakery-terracotta' : 'text-bakery-dark' }}">{{ $recordatorio->proxima_fecha->format('d/m/Y') }}</p>
                            @if($recordatorio->monto_estimado)<p class="mt-1 text-xs text-bakery-muted">{{ \App\Support\DisplayNumber::money($recordatorio->monto_estimado) }}</p>@endif
                        </div>
                        <div class="flex items-center gap-2 md:justify-end">
                            @if($recordatorio->activo)
                                <a href="{{ route('finanzas.gastos.create', ['recordatorio' => $recordatorio->id]) }}" class="btn-primary px-4 py-2 text-xs"><x-finanzas.icon name="receipt" />Registrar</a>
                            @endif
                            <details class="finance-action-menu">
                                <summary aria-label="Más acciones"><x-finanzas.icon name="more" /></summary>
                                <div class="finance-action-menu-list">
                                    <a href="{{ route('finanzas.gastos.recordatorios.edit', $recordatorio) }}" class="finance-action-menu-item"><x-finanzas.icon name="edit" />Editar</a>
                                    <form method="POST" action="{{ route('finanzas.gastos.recordatorios.toggle', $recordatorio) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="finance-action-menu-item"><x-finanzas.icon :name="$recordatorio->activo ? 'pause' : 'play'" />{{ $recordatorio->activo ? 'Pausar' : 'Activar' }}</button>
                                    </form>
                                </div>
                            </details>
                        </div>
                    </div>
                @empty
                    <div class="finance-empty">
                        <p class="font-medium text-bakery-dark">Sin recordatorios configurados</p>
                        <a href="{{ route('finanzas.gastos.recordatorios.create') }}" class="btn-primary mt-4 text-xs"><x-finanzas.icon name="plus" />Crear recordatorio</a>
                    </div>
                @endforelse
            </div>
            @if($recordatorios->hasPages())<div class="border-t border-bakery-amber/10 px-4 py-3">{{ $recordatorios->links('vendor.pagination.bakery') }}</div>@endif
        </section>
    </div>
</main>
</x-app-layout>
