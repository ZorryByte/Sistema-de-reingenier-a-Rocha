<nav aria-label="Navegación principal" x-data="{ open: false }"
     x-init="$watch('open', value => $nextTick(() => (value ? $refs.mobileClose : $refs.mobileTrigger)?.focus()))"
     @resize.window="if (open && window.matchMedia('(min-width: 1280px)').matches) open = false"
     @keydown.escape.window="if (open) open = false"
     @keydown.tab.window="if (open) window.rochaTrapTab($event, $refs.mobilePanel)" class="m-0 bg-white border-b border-bakery-amber/10 shadow-sm sticky top-0 z-40">
    @php
        $esAdmin = Auth::check() && Auth::user()->esAdmin();
        $navGroups = [
            'Operación' => [
                ['dashboard', 'dashboard', 'Dashboard', 'dashboard', true],
                ['pedidos.index', 'pedidos.*', 'Comandas', 'orders', true],
                ['ventas.index', 'ventas.*', 'Ventas', 'sales', true],
            ],
            'Administración' => [
                ['clientes.index', 'clientes.*', 'Clientes', 'customers', false],
                ['recetas.index', 'recetas.*', 'Recetas', 'recipes', true],
                ['productos.index', 'productos.*', 'Productos', 'products', true],
                ['produccion.index', 'produccion.*', 'Producción', 'production', true],
                ['ingredientes.index', 'ingredientes.*', 'Ingredientes', 'ingredients', true],
                ['control-inventario.index', 'control-inventario.*', 'Control de inventario', 'inventory', true],
                ['finanzas.index', 'finanzas.*', 'Finanzas', 'finance', false],
            ],
        ];

        if (!$esAdmin) {
            unset($navGroups['Administración']);
        }
    @endphp

    {{-- ==========================================
        PRIMARY NAVIGATION — Desktop
        ========================================== --}}
    <div class="mx-auto max-w-[100rem] px-4 sm:px-6 xl:px-3 2xl:px-8">
        <div class="grid min-h-14 grid-cols-[1fr_auto] items-center gap-4 xl:grid-cols-[auto_1fr_auto] xl:gap-2 2xl:gap-5">

            {{-- Logo --}}
            <div class="flex items-center">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-2">
                    <x-application-logo class="block h-7 w-auto fill-current text-bakery-brown" />
                </a>
            </div>

            <div class="hidden items-center justify-center gap-1.5 xl:flex 2xl:gap-3">
                @foreach($navGroups as $group => $items)
                    <ul class="flex items-center gap-0.5 2xl:gap-1 {{ $loop->first ? '' : 'border-l border-bakery-amber/25 pl-1.5 2xl:pl-3' }}" aria-label="{{ $group }}">
                        @foreach($items as [$routeName, $pattern, $label, $icon, $prefetch])
                            <li>
                                <x-nav-link :href="route($routeName)" :active="request()->routeIs($pattern)"
                                    x-on:mouseenter="{{ $prefetch ? 'window.Laravel?.prefetch?.($el.href)' : '' }}">
                                    <x-navigation-label :$icon :$label :pending="$pendingPedidos" :low-stock="$stockBajoCount" />
                                </x-nav-link>
                            </li>
                        @endforeach
                    </ul>
                @endforeach
            </div>

            <div class="hidden items-center xl:flex">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center gap-2 px-2 py-1.5 rounded-lg hover:bg-bakery-warm transition-colors duration-150 focus:outline-none focus-visible:ring-2 focus-visible:ring-bakery-gold/40 focus-visible:ring-offset-2">
                            {{-- Nombre truncado para evitar overflow --}}
                            <span class="text-xs font-medium text-bakery-brown max-w-[88px] truncate 2xl:max-w-[112px]">
                                {{ Auth::user()->name }}
                            </span>
                            {{-- Dot de rol: gold = admin, sage = caja --}}
                            <span class="w-2 h-2 rounded-full {{ $esAdmin ? 'bg-bakery-gold' : 'bg-bakery-sage' }}" aria-hidden="true"></span>
                            <svg class="fill-current h-3.5 w-3.5 text-bakery-muted" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        @if($esAdmin)
                            <x-dropdown-link :href="route('usuarios.index')">
                                <span class="flex items-center gap-2">
                                    <x-navigation-label icon="users" label="Gestión de Usuarios" />
                                </span>
                            </x-dropdown-link>
                        @endif

                        <x-dropdown-link :href="route('ayuda')">
                            <span class="flex items-center gap-2">
                                <x-navigation-label icon="help" label="Ayuda" />
                            </span>
                        </x-dropdown-link>

                        @if($esAdmin)
                            <x-dropdown-link :href="route('backups.index')">
                                <span class="flex items-center gap-2">
                                    <x-navigation-label icon="backups" label="Respaldos" />
                                </span>
                            </x-dropdown-link>
                        @endif

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-dropdown-link :href="route('logout')"
                                onclick="event.preventDefault(); this.closest('form').submit();">
                                <span class="flex items-center gap-2">
                                    <x-navigation-label icon="logout" label="Cerrar sesión" />
                                </span>
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            {{-- Hamburger button — Mobile only --}}
            <div class="flex items-center xl:hidden">
                <button type="button" x-ref="mobileTrigger" :aria-expanded="open" aria-controls="mobile-navigation" @click="open = ! open" class="inline-flex items-center justify-center h-11 w-11 rounded-lg text-bakery-brown/60 hover:text-bakery-brown hover:bg-bakery-warm focus:outline-none focus-visible:ring-2 focus-visible:ring-bakery-gold/40 transition-colors duration-150" aria-label="Abrir menú">
                    <svg class="h-5 w-5" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    {{-- ==========================================
        MOBILE DRAWER — Lateral derecho
        ==========================================
        - Overlay oscuro semitransparente bloquea interacción con fondo
        - Drawer se desliza desde la derecha con translateX (GPU-accelerated)
        - Auto-close al hacer click en un link (@click="open = false")
        - Cierra al pulsar el fondo o Escape
        - x-cloak previene flash antes de Alpine.js
        --}}
    <div x-show="open"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-50 xl:hidden"

        style="display: none;"
        x-cloak>

        {{-- Overlay — click fuera cierra el drawer --}}
        <div class="absolute inset-0 bg-bakery-dark/30" @click="open = false"></div>

        {{-- Panel del drawer — alineado a la derecha con ml-auto --}}
        <div x-show="open" id="mobile-navigation" x-ref="mobilePanel" role="dialog" aria-modal="true" aria-label="Menú de navegación"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="translate-x-full"
             x-transition:enter-end="translate-x-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="translate-x-0"
             x-transition:leave-end="translate-x-full"
             class="relative ml-auto h-full w-80 max-w-full bg-bakery-cream shadow-xl border-l border-bakery-amber/10 flex flex-col"
             style="display: none;">

            {{-- Drawer Header: Logo + botón cerrar --}}
            <div class="flex shrink-0 items-center justify-between gap-4 px-4 py-3 border-b border-bakery-amber/10">
                <a href="{{ route('dashboard') }}" @click="open = false" class="flex items-center gap-2">
                    <x-application-logo class="block h-7 w-auto fill-current text-bakery-brown" />
                </a>
                <button type="button" x-ref="mobileClose" @click="open = false" class="grid h-11 w-11 place-items-center rounded-lg text-bakery-brown/60 hover:text-bakery-brown hover:bg-bakery-warm transition-colors duration-150 focus:outline-none focus-visible:ring-2 focus-visible:ring-bakery-gold/40" aria-label="Cerrar menú">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="min-h-0 flex-1 overflow-y-auto overscroll-contain">
                <div class="grid gap-4 px-3 py-3">
                    @foreach($navGroups as $group => $items)
                        <div>
                            <p class="px-3 pb-1.5 text-xs font-semibold text-bakery-muted">{{ $group }}</p>
                            <ul class="grid gap-1" aria-label="{{ $group }}">
                                @foreach($items as [$routeName, $pattern, $label, $icon])
                                    <li>
                                        <x-responsive-nav-link :href="route($routeName)" :active="request()->routeIs($pattern)" @click="open = false">
                                            <x-navigation-label :$icon :$label :pending="$pendingPedidos" :low-stock="$stockBajoCount" />
                                        </x-responsive-nav-link>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endforeach
                </div>

                {{-- Drawer Footer: User info + logout --}}
                <div class="border-t border-bakery-amber/20 px-3 py-3">
                    <div class="mb-1 flex items-center gap-2 px-3">
                        <span class="w-2 h-2 shrink-0 rounded-full {{ $esAdmin ? 'bg-bakery-gold' : 'bg-bakery-sage' }}" aria-hidden="true"></span>
                        <span class="min-w-0 break-words text-sm font-semibold text-bakery-brown">{{ Auth::user()->name }}</span>
                    </div>

                    @if($esAdmin)
                        <x-responsive-nav-link :href="route('usuarios.index')" :active="request()->routeIs('usuarios.*')" @click="open = false">
                            <x-navigation-label icon="users" label="Gestión de Usuarios" />
                        </x-responsive-nav-link>

                        <x-responsive-nav-link :href="route('backups.index')" :active="request()->routeIs('backups.*')" @click="open = false">
                            <x-navigation-label icon="backups" label="Respaldos" />
                        </x-responsive-nav-link>
                    @endif

                    <x-responsive-nav-link :href="route('ayuda')" :active="request()->routeIs('ayuda')" @click="open = false">
                        <x-navigation-label icon="help" label="Ayuda" />
                    </x-responsive-nav-link>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <x-responsive-nav-link :href="route('logout')"
                            onclick="event.preventDefault(); this.closest('form').submit();"
                            @click="open = false">
                            <x-navigation-label icon="logout" label="Cerrar sesión" />
                        </x-responsive-nav-link>
                    </form>
                </div>
            </div>
        </div>
    </div>
</nav>
