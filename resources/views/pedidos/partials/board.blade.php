<section aria-label="Bandeja de comandas">
    <div class="mb-4 rounded-xl bg-white p-3 shadow-sm sm:p-4">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <nav class="grid grid-cols-3 rounded-lg bg-bakery-warm p-1" aria-label="Filtrar por tipo">
                @foreach([
                    '' => ['Todos', $contadores['todos']],
                    'pedido' => ['Pedidos', $contadores['pedido']],
                    'orden' => ['Órdenes', $contadores['orden']],
                ] as $tipo => [$label, $total])
                    <a href="{{ route('pedidos.index', array_filter(array_merge(request()->except(['tipo', 'page']), ['tipo' => $tipo]))) }}"
                       class="flex min-h-10 items-center justify-center gap-1.5 rounded-md px-3 text-sm font-semibold transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-bakery-gold
                       {{ request('tipo', '') === $tipo ? 'bg-white text-bakery-dark shadow-sm' : 'text-bakery-muted hover:text-bakery-dark' }}"
                       @if(request('tipo', '') === $tipo) aria-current="page" @endif>
                        {{ $label }}
                        <span class="tabular-nums text-xs opacity-65">{{ $total }}</span>
                    </a>
                @endforeach
            </nav>

            <form method="GET" action="{{ route('pedidos.index') }}" class="grid gap-2 sm:grid-cols-[minmax(160px,1fr)_auto_auto] lg:flex lg:items-center">
                @if(request('tipo'))
                    <input type="hidden" name="tipo" value="{{ request('tipo') }}">
                @endif
                <label class="sr-only" for="estado-pedido">Estado</label>
                <select id="estado-pedido" name="estado" class="form-input min-h-10 py-2 text-sm">
                    <option value="">Cualquier estado</option>
                    @foreach(\App\Enums\PedidoEstado::cases() as $estado)
                        <option value="{{ $estado->value }}" @selected(request('estado') === $estado->value)>
                            {{ $estado->label() }} ({{ $contadores[$estado->value] }})
                        </option>
                    @endforeach
                </select>

                <label class="sr-only" for="fecha-pedido">Fecha de entrega</label>
                <input id="fecha-pedido" type="date" name="fecha" value="{{ request('fecha') }}"
                       class="form-input min-h-10 py-2 text-sm">

                <div class="flex gap-2">
                    <button type="submit" class="btn-secondary min-h-10 flex-1 px-4 py-2 text-sm">Aplicar</button>
                    @if(request()->hasAny(['estado', 'fecha']))
                        <a href="{{ route('pedidos.index', request()->only('tipo')) }}"
                           class="btn-ghost min-h-10 px-3 py-2 text-sm">Limpiar</a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    @if($pedidos->isEmpty())
        <div class="rounded-xl bg-white px-6 py-14 text-center shadow-sm">
            <div class="mx-auto flex h-11 w-11 items-center justify-center rounded-lg bg-bakery-warm text-lg font-bold text-bakery-brown" aria-hidden="true">0</div>
            <h2 class="mt-4 text-base font-bold text-bakery-dark">No hay resultados</h2>
            <p class="mt-1 text-sm text-bakery-muted">Cambia los filtros o crea un registro.</p>
            <a href="{{ route('pedidos.create') }}" class="btn-primary mt-5 inline-flex min-h-11">Crear nuevo</a>
        </div>
    @else
        <div class="overflow-hidden rounded-xl bg-white shadow-sm">
            <div class="hidden grid-cols-[7rem_minmax(0,1fr)_9rem_7rem_1.5rem] gap-4 border-b border-bakery-amber/10 bg-bakery-warm/70 px-5 py-2.5 text-xs font-semibold text-bakery-muted md:grid">
                <span>Entrega</span>
                <span>Registro</span>
                <span>Estado</span>
                <span class="text-right">Estimado</span>
                <span></span>
            </div>

            <div class="space-y-px bg-bakery-amber/10">
                @foreach($pedidos as $pedido)
                    @php
                        $esOrden = $pedido->esOrden();
                        $estado = $pedido->estado->value;
                        $estadoClase = match ($pedido->estado) {
                            \App\Enums\PedidoEstado::EnPreparacion => 'bg-bakery-amber/12 text-bakery-brown',
                            \App\Enums\PedidoEstado::OrdenLista => 'bg-bakery-gold/15 text-bakery-brown',
                            \App\Enums\PedidoEstado::Entregado => 'bg-bakery-sage/15 text-bakery-sage',
                            \App\Enums\PedidoEstado::Cancelado => 'bg-bakery-terracotta/10 text-bakery-terracotta',
                        };
                        $minutos = $esOrden ? (int) $pedido->fecha->diffInMinutes(now()) : null;
                        $total = $pedido->detalles->sum(fn ($detalle) => $detalle->subtotalCents() / 100);
                    @endphp

                    <a href="{{ route('pedidos.show', $pedido) }}"
                       class="grid min-h-[84px] gap-3 bg-white px-4 py-4 transition hover:bg-bakery-warm/55 focus-visible:relative focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-bakery-gold md:grid-cols-[7rem_minmax(0,1fr)_9rem_7rem_1.5rem] md:items-center md:gap-4 md:px-5">
                        <div>
                            @if($esOrden)
                                <span class="block text-base font-bold tabular-nums text-bakery-dark">
                                    {{ intdiv($minutos, 60) }}h {{ $minutos % 60 }}m
                                </span>
                                <span class="text-xs text-bakery-muted">transcurrido</span>
                            @elseif($pedido->fecha_entrega_estimada)
                                <span class="block text-sm font-bold tabular-nums text-bakery-dark">
                                    {{ $pedido->fecha_entrega_estimada->format('d/m/Y') }}
                                </span>
                                <span class="text-xs tabular-nums text-bakery-muted">{{ $pedido->fecha_entrega_estimada->format('H:i') }}</span>
                            @else
                                <span class="text-sm text-bakery-muted">Sin fecha</span>
                            @endif
                        </div>

                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="text-sm font-bold text-bakery-dark">#{{ $pedido->id }}</span>
                                <span class="rounded-md bg-bakery-warm px-2 py-1 text-xs font-semibold text-bakery-brown">
                                    {{ $esOrden ? 'Orden' : 'Pedido' }}
                                </span>
                                <span class="md:hidden rounded-md px-2 py-1 text-xs font-semibold {{ $estadoClase }}">
                                    {{ $pedido->estado->label() }}
                                </span>
                            </div>
                            <p class="mt-1 truncate text-sm text-bakery-muted">
                                {{ $pedido->detalles->take(2)->map(fn ($detalle) => \App\Support\DisplayNumber::quantity((float) $detalle->cantidad).'× '.($detalle->presentacion->producto->nombre ?? 'Producto'))->join(' · ') }}
                                @if($pedido->detalles->count() > 2) · +{{ $pedido->detalles->count() - 2 }} @endif
                            </p>
                            <p class="mt-1 truncate text-xs text-bakery-muted/80">
                                {{ $pedido->usuario->name }}@if($pedido->notas) · {{ $pedido->notas }}@endif
                            </p>
                        </div>

                        <div class="hidden md:block">
                            <span class="inline-flex rounded-md px-2.5 py-1.5 text-xs font-semibold {{ $estadoClase }}">
                                {{ $pedido->estado->label() }}
                            </span>
                        </div>

                        <div class="text-left md:text-right">
                            <span class="text-sm font-bold tabular-nums text-bakery-dark">{{ \App\Support\DisplayNumber::money($total) }}</span>
                        </div>

                        <span class="hidden text-right text-lg text-bakery-muted/50 md:block" aria-hidden="true">›</span>
                    </a>
                @endforeach
            </div>
        </div>

        <div class="mt-4">
            {{ $pedidos->links('vendor.pagination.bakery') }}
        </div>
    @endif
</section>
