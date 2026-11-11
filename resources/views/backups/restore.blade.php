<x-app-layout>
    <div class="py-8 px-6">
        <div class="mb-6">
            <div class="flex items-center gap-2 text-xs text-bakery-muted mb-1">
                <a href="{{ route('dashboard') }}" class="hover:text-bakery-brown transition">Dashboard</a>
                <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                </svg>
                <a href="{{ route('backups.index') }}" class="hover:text-bakery-brown transition">Respaldos</a>
                <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                </svg>
                <span>Restaurar</span>
            </div>
            <h1 class="text-2xl font-display font-bold text-bakery-dark">Restaurar Respaldo</h1>
            <p class="text-sm text-bakery-muted mt-1">Recupera datos desde el respaldo #{{ $backup->id }}</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 form-card">
                <h2 class="text-base font-semibold text-bakery-dark mb-4">Contenido del Respaldo</h2>

                @if(!empty($preview) && count($preview) > 0)
                    <div class="mb-5">
                        <h3 class="section-title mb-2">Tablas de base de datos</h3>
                        <div class="bg-bakery-warm rounded-xl p-3 space-y-1">
                            @foreach($preview as $tabla => $filas)
                                <div class="flex items-center justify-between py-1.5 px-2 rounded-lg hover:bg-white/50 transition">
                                    <div class="flex items-center gap-2">
                                        <svg class="w-3.5 h-3.5 text-bakery-muted" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                                        </svg>
                                        <span class="text-sm text-bakery-dark">{{ $tabla }}</span>
                                    </div>
                                    <span class="text-xs text-bakery-muted">{{ $filas }} filas</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @else
                    <div class="bg-bakery-warm rounded-xl p-4 mb-5">
                        <p class="text-sm text-bakery-muted">No se pudo analizar el contenido del respaldo.</p>
                    </div>
                @endif

                <div class="bg-bakery-terracotta/10 border border-bakery-terracotta/20 rounded-xl p-4 mb-6">
                    <div class="flex items-start gap-3">
                        <svg class="w-5 h-5 text-bakery-terracotta flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                        <div>
                            <p class="text-sm font-semibold text-bakery-terracotta">Esta acción es irreversible</p>
                            <p class="text-xs text-bakery-brown mt-1">Los datos actuales serán reemplazados por los del respaldo seleccionado.</p>
                        </div>
                    </div>
                </div>

                <form method="POST" action="{{ route('backups.restore.store', $backup->id) }}">
                    @csrf

                    <div class="mb-4">
                        <label class="form-label">Escribe <span class="font-bold text-bakery-terracotta">RESTAURAR</span> para confirmar</label>
                        <input
                            type="text"
                            name="confirmacion"
                            class="form-input text-lg font-mono tracking-wider"
                            placeholder="RESTAURAR"
                            autocomplete="off"
                            required
                        >
                    </div>

                    <div class="flex items-center justify-between">
                        <a href="{{ route('backups.index') }}" class="btn-secondary">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                            </svg>
                            Cancelar
                        </a>
                        <button type="submit" class="btn-danger">
                            Restaurar Respaldo
                        </button>
                    </div>
                </form>
            </div>

            <div class="lg:col-span-1">
                <div class="form-card sticky top-24">
                    <h3 class="text-sm font-semibold text-bakery-dark mb-3">Resumen</h3>
                    <div class="space-y-2.5 text-sm">
                        <div class="flex justify-between">
                            <span class="text-bakery-muted">Respaldo</span>
                            <span class="font-medium text-bakery-dark">#{{ $backup->id }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-bakery-muted">Fecha</span>
                            <span class="font-medium text-bakery-dark">{{ $backup->completado_en?->format('d/m/Y H:i') ?? '—' }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-bakery-muted">Nivel</span>
                            <span class="font-medium text-bakery-dark">{{ ucfirst($backup->nivel) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-bakery-muted">Tamaño</span>
                            <span class="font-medium text-bakery-dark">{{ $backup->tamano_humano }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
