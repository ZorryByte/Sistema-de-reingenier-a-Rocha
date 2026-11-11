@props(['icon', 'label', 'pending' => 0, 'lowStock' => 0])

@php
    $paths = [
        'dashboard' => 'M3 3h7v7H3zM14 3h7v7h-7zM3 14h7v7H3zM14 14h7v7h-7z',
        'orders' => 'M8 5H5v16h14V5h-3M8 3h8v4H8zM8 11h8m-8 4h8',
        'sales' => 'M6 3h12v18l-3-2-3 2-3-2-3 2V3Zm3 5h6m-6 4h6',
        'customers' => 'M15 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2m13-6a4 4 0 0 1 5 4v2M15 3a4 4 0 0 1 0 8M13 7a4 4 0 1 1-8 0 4 4 0 0 1 8 0Z',
        'recipes' => 'M12 6c-3-3-6-3-9-2v15c3-1 6-1 9 2 3-3 6-3 9-2V4c-3-1-6-1-9 2Zm0 0v15',
        'products' => 'M3 7l9-4 9 4v10l-9 4-9-4V7Zm0 0 9 4 9-4m-9 4v10M7.5 5l9 4',
        'production' => 'M4 3h16v18H4zM4 8h16M8 5.5h.01m3.99 0H12M8 12h8v5H8z',
        'ingredients' => 'M12 21V11m0 5c-5 0-8-3-8-8 5 0 8 3 8 8Zm0-4c0-5 3-8 8-8 0 5-3 8-8 8Z',
        'inventory' => 'M3 3h18v4H3zM5 7v13h14V7m-10 5h6',
        'finance' => 'M3 3v18h18M8 17v-4m5 4V9m5 8V5',
        'users' => 'M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm-7 8v-1a4 4 0 0 1 4-4h6a4 4 0 0 1 4 4v1',
        'backups' => 'M4 6h16v5H4zM4 14h16v5H4zM7 8.5h.01M7 16.5h.01',
        'help' => 'M12 22a10 10 0 1 0 0-20 10 10 0 0 0 0 20ZM9 9a3 3 0 1 1 4 2.8c-.6.3-1 .7-1 1.7m0 3h.01',
        'logout' => 'M10 4H4v16h6m4-13 5 5-5 5m-6-5h11',
    ];
    $isOrders = $icon === 'orders';
    $count = $isOrders ? $pending : ($icon === 'ingredients' ? $lowStock : 0);
@endphp

<svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"
    stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
    <path d="{{ $paths[$icon] }}" />
</svg>
<span>{{ $label }}</span>
@if($isOrders || $count > 0)
    <span @if($isOrders) data-pedidos-nav-count @endif
        class="ml-auto min-w-[18px] shrink-0 rounded-full bg-bakery-terracotta px-1 text-center text-[10px] font-bold leading-[18px] text-white {{ $count > 0 ? '' : 'hidden' }}">
        {{ $count > 99 ? '99+' : $count }}
    </span>
@endif
