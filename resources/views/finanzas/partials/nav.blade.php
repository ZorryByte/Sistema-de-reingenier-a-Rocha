@php
    $movimientos = [
        ['label' => 'Inicio', 'route' => 'finanzas.index', 'active' => request()->routeIs('finanzas.index')],
        ['label' => 'Libro contable', 'route' => 'finanzas.libro-contable.index', 'active' => request()->routeIs('finanzas.libro-contable.*')],
        ['label' => 'Libro mayor', 'route' => 'finanzas.libro-mayor.index', 'active' => request()->routeIs('finanzas.libro-mayor.*')],
        ['label' => 'Gastos', 'route' => 'finanzas.gastos.index', 'active' => request()->routeIs('finanzas.gastos.*')],
        ['label' => 'Proveedores', 'route' => 'finanzas.proveedores.index', 'active' => request()->routeIs('finanzas.proveedores.*')],
    ];

    $analisis = [
        ['label' => 'Resultado antes de costo', 'route' => 'finanzas.reportes.resultados'],
        ['label' => 'Rentabilidad', 'route' => 'finanzas.reportes.rentabilidad'],
        ['label' => 'Precios proveedor', 'route' => 'finanzas.reportes.precios-proveedor'],
        ['label' => 'Gastos por categoría', 'route' => 'finanzas.reportes.gastos-categoria'],
        ['label' => 'Tendencia ventas', 'route' => 'finanzas.reportes.tendencia-ventas'],
        ['label' => 'Valor de inventario', 'route' => 'finanzas.reportes.valor-inventario'],
    ];
@endphp

<div class="pt-4 sm:pt-5">
    <nav class="finance-panel px-3 py-3" aria-label="Navegacion de finanzas">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center">
            <div class="min-w-0 lg:flex-1">
                <p class="px-1 text-[11px] font-bold uppercase tracking-[0.12em] text-bakery-muted">Contaduria</p>
                <div class="mt-1 flex gap-2 overflow-x-auto pb-1" role="list">
                    @foreach($movimientos as $link)
                        <a href="{{ route($link['route']) }}"
                           class="whitespace-nowrap rounded-xl px-3 py-2 text-sm font-semibold transition focus:outline-none focus:ring-2 focus:ring-bakery-gold/30 {{ $link['active'] ? 'bg-bakery-gold/15 text-bakery-brown' : 'text-bakery-muted hover:bg-bakery-warm hover:text-bakery-brown' }}"
                           @if($link['active']) aria-current="page" @endif>
                            {{ $link['label'] }}
                        </a>
                    @endforeach
                </div>
            </div>

            <div class="hidden h-10 w-px bg-bakery-amber/20 lg:block" aria-hidden="true"></div>

            <div class="min-w-0 lg:flex-[1.4]">
                <p class="px-1 text-[11px] font-bold uppercase tracking-[0.12em] text-bakery-muted">Analisis</p>
                <div class="mt-1 flex gap-2 overflow-x-auto pb-1" role="list">
                    @foreach($analisis as $reporte)
                        @php($active = request()->routeIs($reporte['route']))
                        <a href="{{ route($reporte['route']) }}"
                           class="whitespace-nowrap rounded-lg border px-3 py-1.5 text-xs font-medium transition focus:outline-none focus:ring-2 focus:ring-bakery-gold/30 {{ $active ? 'border-bakery-gold bg-bakery-gold/10 text-bakery-brown' : 'border-bakery-amber/20 text-bakery-muted hover:border-bakery-gold/40 hover:text-bakery-brown' }}"
                           @if($active) aria-current="page" @endif>
                            {{ $reporte['label'] }}
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    </nav>
</div>
