@props([
    'entregados' => 0,
    'stockCritico' => [],
    'stockNegativo' => 0,
    'backup' => [],
])

@php
    $isAdmin = auth()->user()?->esAdmin() ?? false;
    $alertasStock = count($stockCritico) + ((int) $stockNegativo > 0 ? 1 : 0);
    $backupFecha = $backup['fecha'] ?? null;
    $backupLabel = $backupFecha && is_object($backupFecha) && method_exists($backupFecha, 'diffForHumans')
        ? '&Uacute;lt. respaldo hace ' . e($backupFecha->diffForHumans())
        : ($backupFecha ? '&Uacute;lt. respaldo registrado' : 'Sin respaldos');
@endphp

<section aria-label="Estado operativo rapido" data-state="success" x-data="{ state: 'success' }">
    <ul class="dashboard-micro-status" role="list" aria-live="polite">
        <li class="dashboard-micro-item">
            <svg class="dashboard-icon-sm flex-shrink-0 text-bakery-sage" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <a href="{{ route('pedidos.index', ['estado' => 'entregado']) }}" class="dashboard-micro-link">
                <span class="tabular-nums font-bold text-bakery-dark">{{ $entregados }}</span>
                <span class="text-bakery-muted">entregados hoy</span>
            </a>
        </li>

        <li class="dashboard-micro-item">
            @if($alertasStock > 0)
                <svg class="dashboard-icon-sm flex-shrink-0 text-bakery-amber" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M5.07 19h13.86c1.54 0 2.5-1.67 1.73-3L13.73 4a2 2 0 00-3.46 0L3.34 16c-.77 1.33.19 3 1.73 3z"/>
                </svg>
                @if($isAdmin)
                    <a href="{{ route('control-inventario.index') }}" class="dashboard-micro-link dashboard-micro-warning">
                        <span class="tabular-nums font-bold">{{ $alertasStock }}</span>
                        <span>en stock bajo</span>
                    </a>
                @else
                    <span class="dashboard-micro-warning">
                        <span class="tabular-nums font-bold">{{ $alertasStock }}</span>
                        <span>en stock bajo</span>
                    </span>
                @endif
            @else
                <svg class="dashboard-icon-sm flex-shrink-0 text-bakery-sage" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                </svg>
                <span class="text-bakery-muted">Stock en orden</span>
            @endif
        </li>

        <li class="dashboard-micro-item">
            <svg class="dashboard-icon-sm flex-shrink-0 text-bakery-brown" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 7v10c0 2 4 3 8 3s8-1 8-3V7M4 7c0-2 4-3 8-3s8 1 8 3M4 7c0 1.5 3 2.5 8 2.5s8-1 8-2.5M4 12c0 1.5 3 2.5 8 2.5s8-1 8-2.5"/>
            </svg>
            @if($isAdmin && $backupFecha)
                <a href="{{ route('backups.index') }}" class="dashboard-micro-link">
                    <span class="text-bakery-muted">{!! $backupLabel !!}</span>
                </a>
            @else
                <span class="text-bakery-muted">{!! $backupLabel !!}</span>
            @endif

            @if($isAdmin)
                <form method="POST" action="{{ route('backups.store') }}" class="inline">
                    @csrf
                    <button type="submit" class="dashboard-micro-btn">
                        <svg class="dashboard-icon-action" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                        </svg>
                        Respaldo
                    </button>
                </form>
            @endif
        </li>
    </ul>
</section>
