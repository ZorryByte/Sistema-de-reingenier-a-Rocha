@props([
    'status' => 'never',
    'label' => '',
])

@php
    $circumference = 251.2;
    $configs = [
        'fresh' => [
            'offset' => 0,
            'color' => 'bakery-sage',
            'text' => 'text-bakery-sage',
            'border' => 'border-bakery-sage/30',
        ],
        'aging' => [
            'offset' => 75,
            'color' => 'bakery-amber',
            'text' => 'text-bakery-amber',
            'border' => 'border-bakery-amber/30',
        ],
        'stale' => [
            'offset' => 176,
            'color' => 'bakery-terracotta',
            'text' => 'text-bakery-terracotta',
            'border' => 'border-bakery-terracotta/30',
        ],
        'never' => [
            'offset' => 251.2,
            'color' => 'bakery-muted',
            'text' => 'text-bakery-muted',
            'border' => 'border-bakery-muted/30',
        ],
    ];
    $c = $configs[$status] ?? $configs['never'];
@endphp

<div x-data="{ ready: false }" x-init="setTimeout(() => ready = true, 150)" class="flex flex-col items-center">
    <div class="relative w-28 h-28">
        <svg class="w-full h-full -rotate-90" viewBox="0 0 100 100">
            <circle
                cx="50" cy="50" r="40"
                fill="none"
                stroke="currentColor"
                stroke-width="8"
                class="text-bakery-amber/10"
            />
            <circle
                cx="50" cy="50" r="40"
                fill="none"
                stroke="currentColor"
                stroke-width="8"
                stroke-linecap="round"
                class="backup-gauge-ring {{ $c['text'] }}"
                stroke-dasharray="{{ $circumference }}"
                :stroke-dashoffset="ready ? {{ $c['offset'] }} : {{ $circumference }}"
            />
        </svg>

        @for($i = 0; $i < 12; $i++)
            <div
                class="absolute w-0.5 h-1.5 bg-bakery-amber/20 rounded-full"
                style="left: 50%; top: 2px; transform-origin: 50% 54px; transform: translateX(-50%) rotate({{ $i * 30 }}deg);"
            ></div>
        @endfor

        <div class="absolute inset-3 bg-white rounded-full shadow-inner flex items-center justify-center border-2 {{ $c['border'] }}">
            @if($status === 'fresh')
                <svg class="w-8 h-8 {{ $c['text'] }}" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                </svg>
            @elseif($status === 'aging')
                <svg class="w-7 h-7 {{ $c['text'] }}" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            @elseif($status === 'stale')
                <svg class="w-7 h-7 {{ $c['text'] }}" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            @else
                <svg class="w-7 h-7 {{ $c['text'] }}" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20 12H4"/>
                </svg>
            @endif
        </div>
    </div>

    @if($label)
        <p class="mt-3 text-sm font-semibold {{ $c['text'] }}">{{ $label }}</p>
    @endif
</div>
