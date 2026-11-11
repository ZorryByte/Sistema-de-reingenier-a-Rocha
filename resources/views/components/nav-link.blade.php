@props(['active' => false])

<a {{ $attributes->class([
    'inline-flex min-h-10 items-center gap-1 whitespace-nowrap rounded-lg px-1.5 py-2 text-xs 2xl:gap-1.5 2xl:px-2.5 2xl:text-[13px] font-semibold leading-5 transition-colors focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-bakery-brown',
    'bg-bakery-warm text-bakery-dark ring-1 ring-inset ring-bakery-gold/35' => $active,
    'text-bakery-muted hover:bg-bakery-cream hover:text-bakery-dark' => ! $active,
])->when($active, fn ($attrs) => $attrs->merge(['aria-current' => 'page'])) }}>
    {{ $slot }}
</a>
