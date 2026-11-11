@props(['name', 'class' => 'h-[1em] w-[1em]'])

<svg {{ $attributes->merge(['class' => $class, 'fill' => 'none', 'stroke' => 'currentColor', 'stroke-width' => '1.8', 'viewBox' => '0 0 24 24', 'aria-hidden' => 'true']) }}>
    @switch($name)
        @case('arrow-left')
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 6l-6 6 6 6M9 12h12" />
            @break
        @case('bell')
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.4-1.4A2 2 0 0118 14.2V11a6 6 0 10-12 0v3.2a2 2 0 01-.6 1.4L4 17h5m2 3h2a2 2 0 002-2H9a2 2 0 002 2z" />
            @break
        @case('calendar')
            <path stroke-linecap="round" stroke-linejoin="round" d="M7 3v3m10-3v3M4 9h16M6 5h12a2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V7a2 2 0 012-2z" />
            @break
        @case('chart')
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 19V5m0 14h16M7 15l4-4 3 3 5-7" />
            @break
        @case('check')
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
            @break
        @case('edit')
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 20h4l10.5-10.5a2.1 2.1 0 00-3-3L5 17v3zM14 7l3 3" />
            @break
        @case('file')
            <path stroke-linecap="round" stroke-linejoin="round" d="M7 3h7l5 5v13H7a2 2 0 01-2-2V5a2 2 0 012-2z" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M14 3v6h5" />
            @break
        @case('filter')
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M7 12h10M10 18h4" />
            @break
        @case('list')
            <path stroke-linecap="round" stroke-linejoin="round" d="M8 6h12M8 12h12M8 18h12M4 6h.01M4 12h.01M4 18h.01" />
            @break
        @case('more')
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 12h.01M12 12h.01M18 12h.01" />
            @break
        @case('pause')
            <path stroke-linecap="round" stroke-linejoin="round" d="M8 5v14M16 5v14" />
            @break
        @case('play')
            <path stroke-linecap="round" stroke-linejoin="round" d="M8 5v14l11-7L8 5z" />
            @break
        @case('plus')
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14" />
            @break
        @case('receipt')
            <path stroke-linecap="round" stroke-linejoin="round" d="M7 3h10a2 2 0 012 2v16l-3-2-2 2-2-2-2 2-2-2-3 2V5a2 2 0 012-2z" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 8h6M9 12h6M9 16h4" />
            @break
        @case('refresh')
            <path stroke-linecap="round" stroke-linejoin="round" d="M20 7v5h-5M4 17v-5h5" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M18 12a6 6 0 00-10.4-4M6 12a6 6 0 0010.4 4" />
            @break
        @case('tag')
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 5v6.5L13.5 21 21 13.5 11.5 4H5a1 1 0 00-1 1z" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M8 8h.01" />
            @break
        @case('warning')
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4l9 16H3L12 4z" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v5m0 3h.01" />
            @break
    @endswitch
</svg>
