<x-app-layout>
<div class="dashboard-shell">

    <section class="dashboard-hero-bare" aria-label="Encabezado del panel">
        <div class="dashboard-hero-copy">
            <p class="dashboard-caption">Panel operativo</p>
            <h1 class="dashboard-h1">
                {!! now()->hour < 12 ? 'Buenos d&iacute;as' : (now()->hour < 18 ? 'Buenas tardes' : 'Buenas noches') !!},
                <span class="text-bakery-brown">{{ explode(' ', Auth::user()->name)[0] }}</span>
            </h1>
            <p class="dashboard-body mt-2">
                {{ now()->locale('es')->translatedFormat('l d \d\e M, Y') }} &middot; Datos listos para operar
            </p>
        </div>

        <div class="dashboard-actions">
            <a href="{{ route('ventas.create') }}" class="btn-primary min-h-[44px]">
                <svg class="dashboard-icon-action" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                Nueva venta
            </a>
            <a href="{{ route('pedidos.create') }}" class="btn-secondary min-h-[44px]">
                <svg class="dashboard-icon-action" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                Nuevo pedido
            </a>
        </div>
    </section>

    <x-dashboard.today-headline
        :ventasHoy="$ventasHoy"
        :ventasAyer="$ventasAyer"
        :pedidosPendientes="$pedidosPendientes"
        :cocinaPedidosCount="$cocinaPedidosCount"
        :cocinaOrdenesCount="$cocinaOrdenesCount"
        :stockCritico="$stockCritico"
        :stockNegativo="$stockNegativo"
    />

    <section class="dashboard-zone-top" aria-label="Ventas y cocina">
        <div class="lg:col-span-7">
            <x-dashboard.revenue-chart :items="$ventasRangoHoy['items'] ?? []" :total="$ventasRangoHoy['total'] ?? 0" :insights="$ventasRangoHoy['insights'] ?? []" />
        </div>
        <div class="lg:col-span-5">
            <x-dashboard.comanda-resumen
                :orders="$cocinaItems"
                :pedidos="$cocinaPedidosCount"
                :ordenes="$cocinaOrdenesCount"
                :pendientes="$pedidosPendientes"
            />
        </div>
    </section>

    <section class="dashboard-zone-mid" aria-label="Accion siguiente y productos">
        <div class="lg:col-span-8">
            <x-dashboard.next-action
                :ventasHoy="$ventasHoy"
                :pedidosPendientes="$pedidosPendientes"
                :cocinaPedidosCount="$cocinaPedidosCount"
                :cocinaOrdenesCount="$cocinaOrdenesCount"
                :stockCritico="$stockCritico"
                :stockNegativo="$stockNegativo"
            />
        </div>
        <div class="lg:col-span-4">
            <x-dashboard.top-productos :items="$topProductos" :ventasHoy="$ventasHoy" />
        </div>
    </section>

    <section class="dashboard-zone-bottom" aria-label="Estado operativo">
        <div class="lg:col-span-12">
            <x-dashboard.micro-status
                :entregados="$pedidosEntregadosCount"
                :stockCritico="$stockCritico"
                :stockNegativo="$stockNegativo"
                :backup="$estadoBackup"
            />
        </div>
    </section>

</div>
</x-app-layout>
