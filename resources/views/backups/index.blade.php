<x-app-layout>
<div class="py-8 px-6" x-data="{
    countdown: 60,
    refreshing: false,
    init() {
        setInterval(() => {
            this.countdown--;
            if (this.countdown <= 0) {
                this.refreshing = true;
                window.location.reload();
            }
        }, 1000);
    },
    refreshNow() {
        this.refreshing = true;
        window.location.reload();
    }
}">

    <div class="mb-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-2 text-xs text-bakery-muted mb-1">
                    <a href="{{ route('dashboard') }}" class="hover:text-bakery-brown transition">Dashboard</a>
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                    </svg>
                    <span>Respaldos</span>
                </div>
                <h1 class="text-2xl font-display font-bold text-bakery-dark">Gestión de Respaldos</h1>
                <p class="text-sm text-bakery-muted mt-1">Protege tus datos con respaldos automáticos y manuales</p>
            </div>
            <div class="flex items-center gap-3">
                <button
                    type="button"
                    @click="refreshNow()"
                    class="btn-ghost text-xs flex items-center gap-1.5"
                    title="Actualizar ahora"
                >
                    <svg class="w-3.5 h-3.5" :class="refreshing ? 'animate-spin' : ''" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    <span x-text="refreshing ? 'Actualizando...' : countdown + 's'"></span>
                </button>
                <button
                    @click="$dispatch('open-confirm-modal', {
                        action: '{{ route('backups.store') }}',
                        method: 'POST',
                        confirmText: 'Crear respaldo',
                        consequences: 'Se creará un nuevo respaldo completo de la base de datos.',
                        warning: ''
                    })"
                    class="btn-primary"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                    </svg>
                    Crear respaldo
                </button>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-4 flash-success text-sm rounded-xl px-4 py-3">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="mb-4 bg-bakery-terracotta/10 border border-bakery-terracotta/20 text-bakery-terracotta text-sm rounded-xl px-4 py-3">
            {{ session('error') }}
        </div>
    @endif

    @php
        $gaugeLabels = [
            'fresh' => 'Respaldo reciente',
            'aging' => 'Respaldo pendiente',
            'stale' => 'Respaldo desactualizado',
            'never' => 'Sin respaldos',
        ];
    @endphp

    <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mb-6">
        <div class="form-card flex flex-col items-center justify-center py-6">
            <x-backup.vault-gauge
                :status="$lastBackupStatus"
                :label="$gaugeLabels[$lastBackupStatus] ?? 'Sin información'"
            />
            @if($backups->count() > 0 && $backups->first()->created_at)
                <p class="text-xs text-bakery-muted mt-2">
                    {{ $backups->first()->created_at->diffForHumans() }}
                </p>
            @endif
        </div>

        <div class="form-card">
            <h3 class="section-title mb-3">Almacenamiento</h3>
            <x-backup.storage-meter
                :used="$storageInfo['used'] ?? 0"
                :total="$storageInfo['total'] ?? 10737418240"
            />
        </div>

        <div class="form-card">
            <h3 class="section-title mb-4">Resumen</h3>
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-bakery-muted">Total respaldos</span>
                    <span class="text-sm font-semibold text-bakery-dark" id="backup-count">{{ $backups->total() ?? $backups->count() }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-bakery-muted">Último respaldo</span>
                    <span class="text-sm font-medium text-bakery-dark">
                        {{ $backups->count() > 0 && $backups->first()->created_at ? $backups->first()->created_at->format('d/m/Y H:i') : '—' }}
                    </span>
                </div>
                <div class="h-px bg-bakery-amber/15"></div>
                <div class="flex flex-col gap-2">
                    @if($backups->count() > 0)
                        <a href="{{ route('backups.download', $backups->first()->id) }}" class="btn-secondary text-xs justify-center">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                            </svg>
                            Descargar último
                        </a>
                        <a href="{{ route('backups.restore', $backups->first()->id) }}" class="btn-secondary text-xs justify-center">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m0 0a8.001 8.001 0 0115.356 2M4.582 9H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                            Restaurar último
                        </a>
                    @endif
                    <a href="{{ route('backups.configure') }}" class="btn-ghost text-xs justify-center">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        Configurar
                    </a>
                </div>
            </div>
        </div>
    </div>

    <section class="mb-6 overflow-hidden rounded-xl bg-bakery-dark text-white" aria-labelledby="backup-estimate-title">
        <div class="flex flex-col gap-5 p-5 lg:flex-row lg:items-center lg:justify-between">
            <div class="max-w-2xl">
                <h2 id="backup-estimate-title" class="text-base font-semibold">Próximo respaldo: aproximadamente {{ $sizeEstimate['estimated'] }}</h2>
                <p class="mt-1 text-sm leading-6 text-white/70">Rango previsto {{ $sizeEstimate['range'] }}. Incluye {{ $sizeEstimate['database'] }} de base de datos y {{ $sizeEstimate['files'] }} de archivos críticos.</p>
                <p class="mt-1 text-xs text-white/55">{{ $sizeEstimate['method'] }}. Margen de variación: 20%.</p>
            </div>
            <div class="flex items-center gap-3 rounded-xl bg-white/10 px-4 py-3">
                <span class="h-2.5 w-2.5 rounded-full {{ $driveConfigured ? 'bg-bakery-sage' : 'bg-bakery-terracotta' }}" aria-hidden="true"></span>
                <div>
                    <p class="text-sm font-semibold">Google Drive</p>
                    <p class="text-xs text-white/65">{{ $driveConfigured ? 'Listo para sincronizar' : 'Configuración pendiente' }}</p>
                </div>
            </div>
        </div>
    </section>

    <div class="bg-white rounded-2xl shadow-sm border border-bakery-amber/15 overflow-hidden">
        <div class="p-5 border-b border-bakery-amber/10">
            <div class="flex items-center justify-between">
                <h2 class="text-base font-semibold text-bakery-dark">Historial de respaldos</h2>
                <span class="text-xs text-bakery-muted" id="backup-count-label">{{ $backups->total() ?? $backups->count() }} respaldo{{ ($backups->total() ?? $backups->count()) !== 1 ? 's' : '' }}</span>
            </div>
        </div>

        @if($backups->isEmpty())
            <div class="empty-state py-16">
                <svg class="empty-state-icon" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m-12 8v2m6-2v2m-6 0v2"/>
                </svg>
                <p class="empty-state-text">No hay respaldos creados todavía</p>
                <button
                    @click="$dispatch('open-confirm-modal', {
                        action: '{{ route('backups.store') }}',
                        method: 'POST',
                        confirmText: 'Crear primer respaldo',
                        consequences: 'Se creará un respaldo completo de la base de datos.',
                        warning: ''
                    })"
                    class="btn-primary mt-4"
                >
                    Crear primer respaldo
                </button>
            </div>
        @else
            <div class="overflow-x-auto" id="backup-table-body">
                <table class="w-full">
                    <thead class="bg-bakery-warm/50">
                        <tr>
                            <th class="pl-4 pr-3 py-3 text-left"><span class="sr-only">Seleccionar</span></th>
                            <th class="px-3 py-3 text-left text-xs font-semibold text-bakery-muted uppercase tracking-wider">Fecha</th>
                            <th class="px-3 py-3 text-left text-xs font-semibold text-bakery-muted uppercase tracking-wider">Nivel</th>
                            <th class="px-3 py-3 text-left text-xs font-semibold text-bakery-muted uppercase tracking-wider">Tamaño</th>
                            <th class="px-3 py-3 text-left text-xs font-semibold text-bakery-muted uppercase tracking-wider">Estado</th>
                            <th class="px-3 py-3 text-left text-xs font-semibold text-bakery-muted uppercase tracking-wider">Nube</th>
                            <th class="pr-4 pl-3 py-3 text-right text-xs font-semibold text-bakery-muted uppercase tracking-wider">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="backups-tbody" class="divide-y divide-bakery-amber/10">
                        @foreach($backups as $backup)
                            <x-backup.backup-row :backup="$backup" />
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($backups->hasPages())
                <div class="p-4 border-t border-bakery-amber/10">
                    {{ $backups->links('vendor.pagination.bakery') }}
                </div>
            @endif
        @endif
    </div>

    <div
        x-data="{ show: false, message: '', type: 'success' }"
        x-show="show"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-4"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 translate-y-4"
        @toast.window="show = true; message = $event.detail.message; type = $event.detail.type; setTimeout(() => show = false, 4000)"
        class="fixed bottom-6 right-6 z-50"
        style="display: none;"
        x-cloak
    >
        <div
            class="flex items-center gap-3 px-4 py-3 rounded-xl shadow-lg border"
            :class="type === 'success' ? 'bg-bakery-sage text-white border-bakery-sage/30' : 'bg-bakery-terracotta text-white border-bakery-terracotta/30'"
        >
            <svg x-show="type === 'success'" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
            </svg>
            <svg x-show="type === 'error'" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
            </svg>
            <span class="text-sm font-medium" x-text="message"></span>
        </div>
    </div>
</div>
</x-app-layout>
