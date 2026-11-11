<x-app-layout>
    <div class="crm-workspace">
        <a class="crm-back" href="{{ route('clientes.index') }}"><span aria-hidden="true">←</span> Clientes</a>

        @if(session('success'))
            <p class="rounded-lg bg-bakery-warm p-3 text-bakery-dark" role="status">{{ session('success') }}</p>
        @endif

        <header class="crm-heading">
            <div class="min-w-0">
                <h1>{{ $cliente->nombre }}</h1>
                <div class="mt-2 flex flex-wrap items-center gap-3 text-sm">
                    <a class="crm-text-link tabular-nums" href="tel:{{ $cliente->telefono }}">{{ $cliente->telefono_visible }}</a>
                    <span class="border-l border-bakery-amber/40 pl-3 text-bakery-muted">{{ $cliente->archivado_at ? 'Archivado' : 'Activo' }}</span>
                </div>
            </div>
            <div class="crm-actions">
                <a class="btn-secondary" href="{{ route('clientes.edit', $cliente) }}">Editar contacto</a>
                <form method="POST" action="{{ route($cliente->archivado_at ? 'clientes.reactivar' : 'clientes.archivar', $cliente) }}">
                    @csrf @method('PATCH')
                    <button class="btn-ghost" type="submit">{{ $cliente->archivado_at ? 'Reactivar cliente' : 'Archivar cliente' }}</button>
                </form>
            </div>
        </header>

        <dl class="crm-surface crm-metrics">
            <div>
                <dt class="text-sm text-bakery-muted">Total comprado</dt>
                <dd class="font-display font-bold tabular-nums text-bakery-dark">{{ \App\Support\DisplayNumber::money($compras->total) }}</dd>
            </div>
            <div>
                <dt class="text-sm text-bakery-muted">Compras</dt>
                <dd class="font-display font-bold tabular-nums text-bakery-dark">{{ $compras->cantidad }}</dd>
            </div>
            <div>
                <dt class="text-sm text-bakery-muted">Última compra</dt>
                <dd class="font-display font-bold tabular-nums text-bakery-dark">{{ $compras->ultima ? \Illuminate\Support\Carbon::parse($compras->ultima)->format('d/m/Y') : 'Sin compras' }}</dd>
            </div>
        </dl>

        <div class="crm-profile-grid">
            <div class="crm-surface min-w-0">
                <section class="crm-ledger" id="ventas-cliente">
                    <h2>Ventas recientes</h2>
                    <ul class="divide-y divide-bakery-amber/15">
                        @forelse($ventas as $venta)
                            <li>
                                <a class="crm-transaction crm-row-link" href="{{ route('ventas.show', $venta) }}">
                                    <span class="min-w-0">
                                        <span class="block break-words font-semibold">Venta {{ $venta->numero_factura ?? '#'.$venta->id }}</span>
                                        <span class="block text-sm text-bakery-muted">{{ $venta->created_at?->format('d/m/Y') }} · {{ $venta->estado->label() }}</span>
                                    </span>
                                    <span class="tabular-nums">{{ \App\Support\DisplayNumber::money($venta->total) }}</span>
                                    <span class="crm-row-arrow" aria-hidden="true">›</span>
                                </a>
                            </li>
                        @empty
                            <li class="py-3 text-sm text-bakery-muted">Sin ventas vinculadas.</li>
                        @endforelse
                    </ul>
                    {{ $ventas->fragment('ventas-cliente')->links() }}
                </section>

                <section class="crm-ledger border-t border-bakery-amber/25" id="pedidos-cliente">
                    <h2>Pedidos y órdenes recientes</h2>
                    <ul class="divide-y divide-bakery-amber/15">
                        @forelse($pedidos as $pedido)
                            <li>
                                <a class="crm-transaction crm-row-link" href="{{ route('pedidos.show', $pedido) }}">
                                    <span class="min-w-0">
                                        <span class="block font-semibold">{{ $pedido->esOrden() ? 'Orden' : 'Pedido' }} #{{ $pedido->id }}</span>
                                        <span class="block text-sm text-bakery-muted">{{ $pedido->fecha?->format('d/m/Y') }} · {{ $pedido->estado->label() }}</span>
                                    </span>
                                    <span class="crm-row-arrow" aria-hidden="true">›</span>
                                </a>
                            </li>
                        @empty
                            <li class="py-3 text-sm text-bakery-muted">Sin pedidos vinculados.</li>
                        @endforelse
                    </ul>
                    {{ $pedidos->fragment('pedidos-cliente')->links() }}
                </section>
            </div>

            <section class="crm-surface crm-ledger">
                <h2>Artículos más comprados</h2>
                <ol class="divide-y divide-bakery-amber/15">
                    @forelse($articulos as $articulo)
                        <li class="flex justify-between gap-4 py-3 text-sm">
                            <div class="min-w-0 break-words">
                                <p class="font-semibold text-bakery-dark">{{ $articulo->presentacion->producto?->nombre ?? 'Producto eliminado' }}</p>
                                <p class="text-bakery-muted">{{ $articulo->presentacion->nombre }}</p>
                            </div>
                            <span class="tabular-nums text-bakery-dark">{{ \App\Support\DisplayNumber::quantity($articulo->unidades) }}</span>
                        </li>
                    @empty
                        <li class="py-3 text-sm text-bakery-muted">Sin artículos comprados.</li>
                    @endforelse
                </ol>
            </section>
        </div>
    </div>
</x-app-layout>
