<x-app-layout>
    <div
        class="mx-auto w-full max-w-7xl px-4 py-7 sm:px-6 sm:py-10"
        x-data="helpCenter(@js($busqueda))"
    >
        <header class="border-b border-bakery-brown/15 pb-7">
            <nav class="mb-4 flex items-center gap-2 text-sm text-bakery-muted" aria-label="Ruta de navegación">
                <a href="{{ route('dashboard') }}" class="rounded focus:outline-none focus-visible:ring-2 focus-visible:ring-bakery-gold/50 hover:text-bakery-brown">
                    Inicio
                </a>
                <span aria-hidden="true">/</span>
                <span class="font-semibold text-bakery-dark">Ayuda</span>
            </nav>

            <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_minmax(22rem,34rem)] lg:items-end">
                <div>
                    <h1 class="font-display text-2xl font-bold tracking-tight text-bakery-dark sm:text-3xl">Centro de ayuda</h1>
                    <p class="mt-2 max-w-xl text-base leading-relaxed text-bakery-muted">
                        Encuentra una tarea y sigue los pasos necesarios.
                    </p>
                </div>

                <div class="relative">
                    <label for="help-search" class="mb-2 block text-sm font-semibold text-bakery-dark">¿Qué necesitas hacer?</label>
                    <div class="flex min-h-12 items-center gap-3 border-b-2 border-bakery-amber/35 bg-transparent px-1 transition focus-within:border-bakery-gold">
                        <input
                            id="help-search"
                            type="search"
                            x-model="query"
                            @input.debounce.150ms="search"
                            @keydown.escape="clear"
                            placeholder="Buscar una tarea: vender, entregar, inventario..."
                            class="min-w-0 flex-1 border-0 bg-transparent px-0 py-3 text-base text-bakery-dark placeholder:text-bakery-muted focus:ring-0"
                            autocomplete="off"
                        >
                        <button
                            type="button"
                            x-show="query"
                            x-cloak
                            @click="clear"
                            class="min-h-11 rounded px-2 text-sm font-semibold text-bakery-brown focus:outline-none focus-visible:ring-2 focus-visible:ring-bakery-gold/50"
                        >
                            Limpiar
                        </button>
                    </div>
                </div>
            </div>
        </header>

        <section x-show="query" x-cloak class="border-b border-bakery-amber/20 py-7" aria-labelledby="search-results-title">
            <div class="flex items-baseline justify-between gap-4">
                <h2 id="search-results-title" class="font-display text-lg font-bold text-bakery-dark">Resultados</h2>
                <p class="text-sm text-bakery-muted" aria-live="polite">
                    <span x-text="results.length"></span>
                    <span x-text="results.length === 1 ? ' tarea' : ' tareas'"></span>
                </p>
            </div>

            <div x-show="results.length" class="mt-4 divide-y divide-bakery-amber/15">
                <template x-for="result in results" :key="result.url">
                    <a :href="result.url" class="group grid min-h-16 gap-1 py-4 focus:outline-none focus-visible:ring-2 focus-visible:ring-bakery-gold/50 sm:grid-cols-[minmax(12rem,0.7fr)_minmax(0,1fr)] sm:gap-8">
                        <span class="font-semibold text-bakery-dark group-hover:text-bakery-brown" x-text="result.title"></span>
                        <span class="text-sm leading-relaxed text-bakery-muted" x-text="result.summary"></span>
                    </a>
                </template>
            </div>

            <div x-show="!results.length" class="mt-5 border-l-2 border-bakery-gold/60 bg-bakery-gold/5 px-5 py-4">
                <p class="font-bold text-bakery-dark">
                    No encontramos esa tarea.
                </p>

            <p class="mt-1 text-sm leading-relaxed text-bakery-muted">
                Prueba con una palabra más corta, como “venta”, “pedido”, “stock” o “inventario”.
            </p>

    <button
        type="button"
        @click="clear"
        class="mt-4 min-h-11 font-bold text-bakery-brown underline decoration-bakery-amber underline-offset-4"
    >
        Volver a tareas frecuentes
    </button>
</div>
        </section>

        <div x-show="!query" class="lg:grid lg:grid-cols-[15rem_minmax(0,1fr)] lg:gap-12">
            <aside class="border-b border-bakery-amber/20 py-6 lg:sticky lg:top-4 lg:self-start lg:border-b-0 lg:border-r lg:pr-8" aria-label="Temas de ayuda">
                <h2 class="mb-3 text-sm font-bold text-bakery-dark">Temas</h2>
                <nav class="flex gap-2 overflow-x-auto pb-1 lg:block lg:space-y-1 lg:overflow-visible">
                    @foreach($categorias as $categoria => $items)
                        <a
                            href="#{{ Str::slug($categoria) }}"
                            class="min-h-11 shrink-0 whitespace-nowrap rounded px-3 py-2.5 text-sm font-semibold text-bakery-muted transition hover:bg-white hover:text-bakery-dark focus:outline-none focus-visible:ring-2 focus-visible:ring-bakery-gold/50 lg:block lg:whitespace-normal"
                        >
                            {{ $categoria }}
                        </a>
                    @endforeach
                </nav>
            </aside>

            <div class="min-w-0 py-7 sm:py-9">
                @if($seleccionado)
                    <article class="max-w-3xl" aria-labelledby="help-article-title">
                        <a href="{{ route('ayuda') }}" class="inline-flex min-h-11 items-center text-sm font-semibold text-bakery-brown underline decoration-bakery-amber underline-offset-4 focus:outline-none focus-visible:ring-2 focus-visible:ring-bakery-gold/50">
                            Ver todas las tareas
                        </a>

                        <header class="mt-4 border-b border-bakery-amber/20 pb-6">
                            <p class="text-sm font-semibold text-bakery-muted">{{ $seleccionado['categoria'] }}</p>
                            <h2 id="help-article-title" class="mt-2 text-balance font-display text-2xl font-bold tracking-tight text-bakery-dark sm:text-3xl">
                                {{ $seleccionado['titulo'] }}
                            </h2>
                            <p class="mt-3 max-w-[70ch] text-base leading-relaxed text-bakery-muted">{{ $seleccionado['resumen'] }}</p>
                        </header>

                        <ol class="mt-7 space-y-5">
                            @foreach($seleccionado['pasos'] as $paso)
                                <li class="grid grid-cols-[2rem_minmax(0,1fr)] gap-3 text-base leading-relaxed text-bakery-dark">
                                    <span class="font-display font-bold tabular-nums text-bakery-gold" aria-hidden="true">{{ $loop->iteration }}</span>
                                    <span>{{ $paso }}</span>
                                </li>
                            @endforeach
                        </ol>

                        <div class="mt-8 border-y border-bakery-amber/20 py-5">
                            <h3 class="text-sm font-bold text-bakery-dark">Al terminar</h3>
                            <p class="mt-1 text-base leading-relaxed text-bakery-muted">{{ $seleccionado['resultado'] }}</p>
                        </div>

                        @if($seleccionado['advertencia'])
                            <div class="mt-6 border border-bakery-amber/25 bg-bakery-warm px-5 py-4">
                                <h3 class="text-sm font-bold text-bakery-dark">Antes de continuar</h3>
                                <p class="mt-1 text-sm leading-relaxed text-bakery-muted">{{ $seleccionado['advertencia'] }}</p>
                            </div>
                        @endif

                        <div class="mt-7 flex flex-col gap-3 sm:flex-row sm:items-center">
                            <a href="{{ route($seleccionado['ruta']) }}" class="btn-primary min-h-11">{{ $seleccionado['accion'] }}</a>
                        </div>

                        @if(count($seleccionado['relacionados']))
                            <footer class="mt-10 border-t border-bakery-brown/15 pt-6">
                                <h3 class="text-sm font-bold text-bakery-dark">También puede ayudarte</h3>
                                <div class="mt-3 flex flex-col items-start gap-2">
                                    @foreach($seleccionado['relacionados'] as $relacionado)
                                        <a href="{{ route('ayuda', ['tema' => $relacionado['slug']]) }}" class="min-h-11 py-2 font-semibold text-bakery-brown underline decoration-bakery-amber underline-offset-4 focus:outline-none focus-visible:ring-2 focus-visible:ring-bakery-gold/50">
                                            {{ $relacionado['titulo'] }}
                                        </a>
                                    @endforeach
                                </div>
                            </footer>
                        @endif
                    </article>
                @else
                    <section aria-labelledby="frequent-title">
                        <h2 id="frequent-title" class="font-display text-xl font-bold text-bakery-dark">Tareas frecuentes</h2>
                        <div class="mt-4 grid border-y border-bakery-amber/20 sm:grid-cols-2">
                            @foreach($frecuentes as $articulo)
                                <a
                                    href="{{ route('ayuda', ['tema' => $articulo['slug']]) }}"
                                    class="group min-h-24 border-b border-bakery-amber/15 py-5 transition focus:outline-none focus-visible:ring-2 focus-visible:ring-bakery-gold/50 sm:odd:pr-6 sm:even:border-l sm:even:pl-6 {{ $loop->iteration > 2 ? 'sm:border-b-0' : '' }}"
                                >
                                    <span class="block font-semibold text-bakery-dark group-hover:text-bakery-brown">{{ $articulo['titulo'] }}</span>
                                    <span class="mt-1 block text-sm leading-relaxed text-bakery-muted">{{ $articulo['resumen'] }}</span>
                                </a>
                            @endforeach
                        </div>
                    </section>

                    <div class="mt-10 space-y-10">
                        @foreach($categorias as $categoria => $items)
                            <section id="{{ Str::slug($categoria) }}" class="scroll-mt-6" aria-labelledby="category-{{ Str::slug($categoria) }}">
                                <h2 id="category-{{ Str::slug($categoria) }}" class="font-display text-lg font-bold text-bakery-dark">{{ $categoria }}</h2>
                                <div class="mt-3 divide-y divide-bakery-amber/15 border-t border-bakery-amber/20">
                                    @foreach($items as $articulo)
                                        <a
                                            href="{{ route('ayuda', ['tema' => $articulo['slug']]) }}"
                                            class="group grid min-h-16 gap-1 py-4 focus:outline-none focus-visible:ring-2 focus-visible:ring-bakery-gold/50 sm:grid-cols-[minmax(12rem,0.7fr)_minmax(0,1fr)] sm:gap-8"
                                        >
                                            <span class="font-semibold text-bakery-dark group-hover:text-bakery-brown">{{ $articulo['titulo'] }}</span>
                                            <span class="text-sm leading-relaxed text-bakery-muted">{{ $articulo['resumen'] }}</span>
                                        </a>
                                    @endforeach
                                </div>
                            </section>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            function helpCenter(items) {
                return {
                    items,
                    query: '',
                    results: [],

                    normalize(value) {
                        return value
                            .toLocaleLowerCase('es')
                            .normalize('NFD')
                            .replace(/[\u0300-\u036f]/g, '')
                            .trim();
                    },

                    search() {
                        const term = this.normalize(this.query);
                        this.results = term === ''
                            ? []
                            : this.items.filter((item) => this.normalize(item.searchable).includes(term));
                    },

                    clear() {
                        this.query = '';
                        this.results = [];
                        this.$nextTick(() => document.getElementById('help-search')?.focus());
                    },
                };
            }
        </script>
    @endpush
</x-app-layout>
