@props(['active' => false])

<a {{ $attributes->class([
    'flex min-h-11 w-full items-center gap-3 rounded-lg border-l-[3px] px-3 py-2 text-sm font-semibold leading-5 transition-colors focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-bakery-brown',
    'border-bakery-gold bg-bakery-warm text-bakery-dark' => $active,
    'border-transparent text-bakery-muted hover:bg-bakery-warm hover:text-bakery-dark' => ! $active,
])->when($active, fn ($attrs) => $attrs->merge(['aria-current' => 'page'])) }}>
    {{ $slot }}
</a>
