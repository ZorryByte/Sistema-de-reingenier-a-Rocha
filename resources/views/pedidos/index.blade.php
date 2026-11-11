<x-app-layout>
<div class="px-4 py-6 sm:px-6 sm:py-8" x-data="pedidoBoardSync()">
    <div class="mx-auto w-full max-w-6xl">
        <header class="mb-5 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h1 class="text-2xl font-display font-bold tracking-tight text-bakery-dark">Comandas</h1>
                <p class="mt-1 text-sm text-bakery-muted">Una sola bandeja para preparar y entregar.</p>
            </div>

            <div class="flex items-center gap-3">
                <div class="min-w-[118px] text-right text-xs text-bakery-muted" aria-live="polite">
                    <span x-show="syncing">Actualizando...</span>
                    <span x-show="!syncing && syncError" class="text-bakery-terracotta">Sin conexi&oacute;n</span>
                    <span x-show="!syncing && !syncError">Al d&iacute;a <span x-text="lastSync"></span></span>
                </div>
                <a href="{{ route('pedidos.create') }}" class="btn-primary min-h-11 whitespace-nowrap">
                    <span class="text-lg leading-none" aria-hidden="true">+</span>
                    Nuevo
                </a>
            </div>
        </header>

        @if(session('success'))
            <div class="mb-4 flash-success rounded-xl px-4 py-3 text-sm" role="status">
                {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="mb-4 rounded-xl bg-bakery-terracotta/10 px-4 py-3 text-sm font-medium text-bakery-terracotta" role="alert">
                {{ session('error') }}
            </div>
        @endif

        <div id="pedido-board">
            @include('pedidos.partials.board')
        </div>
    </div>
</div>

@push('scripts')
<script>
function pedidoBoardSync() {
    return {
        intervalId: null,
        activeRequest: null,
        syncing: false,
        syncError: false,
        lastSync: 'ahora',

        init() {
            this.intervalId = window.setInterval(() => this.refresh(), 10000);
        },

        async refresh() {
            if (document.hidden || this.syncing) return;

            if (document.activeElement?.closest('#pedido-board')) return;

            this.syncing = true;
            this.activeRequest = new AbortController();

            try {
                const response = await fetch(window.location.href, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    signal: this.activeRequest.signal,
                });

                if (!response.ok) throw new Error('sync_failed');

                const payload = await response.json();
                const board = document.getElementById('pedido-board');
                if (board && !board.contains(document.activeElement) && typeof payload.html === 'string') {
                    board.innerHTML = payload.html;
                }

                document.querySelectorAll('[data-pedidos-nav-count]').forEach((badge) => {
                    badge.textContent = payload.active_count > 99 ? '99+' : payload.active_count;
                    badge.classList.toggle('hidden', payload.active_count < 1);
                });

                this.lastSync = new Intl.DateTimeFormat('es-GT', {
                    hour: '2-digit',
                    minute: '2-digit',
                    second: '2-digit',
                }).format(new Date(payload.synced_at));
                this.syncError = false;
            } catch (error) {
                if (error.name !== 'AbortError') this.syncError = true;
            } finally {
                this.syncing = false;
                this.activeRequest = null;
            }
        },

        destroy() {
            window.clearInterval(this.intervalId);
            this.activeRequest?.abort();
        },
    };
}
</script>
@endpush
</x-app-layout>
