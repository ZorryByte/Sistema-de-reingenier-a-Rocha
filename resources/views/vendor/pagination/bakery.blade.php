@if ($paginator->hasPages())
<nav role="navigation" aria-label="Paginación" class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 px-4 py-3 border-t border-bakery-amber/10">

    {{-- Result counter --}}
    <div class="text-sm text-bakery-muted order-2 sm:order-1">
        Mostrando
        <span class="font-medium text-bakery-dark">{{ $paginator->firstItem() }}</span>
        -
        <span class="font-medium text-bakery-dark">{{ $paginator->lastItem() }}</span>
        de
        <span class="font-medium text-bakery-dark">{{ $paginator->total() }}</span>
        resultados
    </div>

    {{-- Page buttons --}}
    <div class="flex items-center gap-1 order-1 sm:order-2">

        {{-- Previous --}}
        @if ($paginator->onFirstPage())
            <span class="inline-flex items-center gap-1 px-3 py-2 text-sm text-bakery-muted/40 rounded-xl cursor-not-allowed min-h-[44px]" aria-disabled="true">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                Anterior
            </span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" class="inline-flex items-center gap-1 px-3 py-2 text-sm text-bakery-muted hover:bg-bakery-warm hover:text-bakery-dark rounded-xl transition min-h-[44px]" rel="prev" aria-label="Página anterior">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                Anterior
            </a>
        @endif

        {{-- Page numbers (desktop) --}}
        <div class="hidden sm:flex items-center gap-1 mx-2">
            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="inline-flex items-center justify-center w-10 h-10 text-sm text-bakery-muted rounded-xl">
                        ...
                    </span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span class="inline-flex items-center justify-center w-10 h-10 text-sm font-semibold text-white bg-bakery-gold rounded-xl" aria-current="page" aria-label="Página {{ $page }}">
                                {{ $page }}
                            </span>
                        @else
                            <a href="{{ $url }}" class="inline-flex items-center justify-center w-10 h-10 text-sm text-bakery-muted hover:bg-bakery-warm hover:text-bakery-dark rounded-xl transition" aria-label="Ir a página {{ $page }}">
                                {{ $page }}
                            </a>
                        @endif
                    @endforeach
                @endif
            @endforeach
        </div>

        {{-- Page info (mobile) --}}
        <span class="sm:hidden inline-flex items-center justify-center px-3 h-10 text-sm font-medium text-bakery-dark bg-bakery-warm rounded-xl">
            {{ $paginator->currentPage() }}/{{ $paginator->lastPage() }}
        </span>

        {{-- Next --}}
        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" class="inline-flex items-center gap-1 px-3 py-2 text-sm text-bakery-muted hover:bg-bakery-warm hover:text-bakery-dark rounded-xl transition min-h-[44px]" rel="next" aria-label="Página siguiente">
                Siguiente
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </a>
        @else
            <span class="inline-flex items-center gap-1 px-3 py-2 text-sm text-bakery-muted/40 rounded-xl cursor-not-allowed min-h-[44px]" aria-disabled="true">
                Siguiente
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </span>
        @endif
    </div>
</nav>
@endif
