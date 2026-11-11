@props([
    'used' => 0,
    'total' => 10737418240,
])

@php
    $percentage = $total > 0 ? min(($used / $total) * 100, 100) : 0;

    $formatBytes = function ($bytes) {
        if ($bytes <= 0) return '0 B';
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = floor(log($bytes, 1024));
        return number_format($bytes / pow(1024, $i), $i > 1 ? 1 : 0) . ' ' . $units[$i];
    };

    $usedFormatted = $formatBytes($used);
    $totalFormatted = $formatBytes($total);

    if ($percentage < 70) {
        $barColor = 'bg-bakery-sage';
        $textColor = 'text-bakery-sage';
    } elseif ($percentage < 90) {
        $barColor = 'bg-bakery-amber';
        $textColor = 'text-bakery-amber';
    } else {
        $barColor = 'bg-bakery-terracotta';
        $textColor = 'text-bakery-terracotta';
    }
@endphp

<div x-data="{ animate: false }" x-init="setTimeout(() => animate = true, 300)">
    <div class="flex items-end justify-between mb-2">
        <div>
            <span class="text-2xl font-display font-bold text-bakery-dark">{{ $usedFormatted }}</span>
            <span class="text-sm text-bakery-muted ml-1">/ {{ $totalFormatted }}</span>
        </div>
        <span class="text-lg font-bold {{ $textColor }}">{{ round($percentage) }}%</span>
    </div>

    <div class="h-3 bg-bakery-warm rounded-full overflow-hidden border border-bakery-amber/15">
        <div
            class="h-full {{ $barColor }} rounded-full transition-all duration-700 ease-out"
            :style="animate ? 'width: {{ number_format($percentage, 1) }}%' : 'width: 0%'"
        ></div>
    </div>

    <div class="mt-2.5 flex items-center gap-1.5">
        @if($percentage >= 90)
            <svg class="w-3.5 h-3.5 text-bakery-terracotta flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            <span class="text-xs text-bakery-terracotta font-medium">Almacenamiento casi lleno</span>
        @elseif($percentage >= 70)
            <svg class="w-3.5 h-3.5 text-bakery-amber flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            <span class="text-xs text-bakery-amber font-medium">Almacenamiento moderado</span>
        @else
            <svg class="w-3.5 h-3.5 text-bakery-sage flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
            </svg>
            <span class="text-xs text-bakery-sage font-medium">Espacio disponible</span>
        @endif
    </div>
</div>
