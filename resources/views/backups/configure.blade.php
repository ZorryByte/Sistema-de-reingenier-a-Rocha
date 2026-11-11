<x-app-layout>
    <div class="mb-6">
        <div class="flex items-center gap-2 text-xs text-bakery-muted mb-1">
            <a href="{{ route('dashboard') }}" class="hover:text-bakery-brown transition">Dashboard</a>
            <svg class="w-3 h-3" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
            </svg>
            <a href="{{ route('backups.index') }}" class="hover:text-bakery-brown transition">Respaldos</a>
            <svg class="w-3 h-3" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
            </svg>
            <span>Configurar</span>
        </div>
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-display font-bold text-bakery-dark">Configurar respaldos</h1>
                <p class="text-sm text-bakery-muted mt-1">Programa y guarda copias recuperables.</p>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div role="status" class="mb-4 rounded-xl border border-bakery-sage/25 bg-bakery-sage/10 px-4 py-3 text-sm font-medium text-bakery-dark">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div role="alert" class="mb-4 rounded-xl border border-bakery-terracotta/25 bg-bakery-terracotta/10 px-4 py-3 text-sm font-medium text-bakery-terracotta">
            {{ session('error') }}
        </div>
    @endif

    <form
        method="POST"
        action="{{ route('backups.configure.store') }}"
        x-data="{
            saving: false,
            frequency: @js(old('backup_frequency', $backupFrequency ?? 'daily')),
            rotationType: @js(old('rotation_type', $rotationType ?? 'keep_days')),
            backupScope: @js(old('backup_scope', $backupScope ?? 'full')),
            backupDisk: @js(old('backup_disk', $backupDisk ?? 'local')),
            applyRecommended() {
                this.frequency = 'daily';
                this.rotationType = 'keep_days';
                this.backupScope = 'full';
                this.backupDisk = 'google_drive';
                this.$dispatch('backup-toggle', { name: 'auto_backup_enabled', enabled: true });
                this.$refs.backupTime.value = '02:00';
                this.$refs.backupDay.value = 'monday';
                this.$refs.rotationKeepDays.value = 30;
                this.$refs.rotationKeepCount.value = 7;
                this.$refs.cloudPath.value = 'backups';
            }
        }"
        @submit="saving = true"
    >
        @csrf

        <div class="form-card mb-6 border-bakery-gold/30 bg-bakery-warm/60">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div class="max-w-2xl">
                    <span class="inline-flex items-center gap-2 rounded-full border border-bakery-gold/30 bg-white px-3 py-1 text-xs font-semibold text-bakery-brown">
                        <span class="h-2 w-2 rounded-full {{ ($lastBackupStatus['estado'] ?? '') === 'success' ? 'bg-bakery-sage' : 'bg-bakery-amber' }}"></span>
                        Estado de protección
                    </span>
                    <h2 class="mt-3 text-lg font-semibold text-bakery-dark">Protección de respaldos</h2>
                    <p class="mt-1 text-sm leading-6 text-bakery-muted">
                        Copia local y copia en Google Drive.
                    </p>
                </div>
                <button type="button" class="hidden btn-secondary text-sm" @click="applyRecommended()">
                    Usar recomendación
                </button>
            </div>

            <dl class="mt-5 grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-xl border border-bakery-amber/15 bg-white/70 p-3">
                    <dt class="text-xs font-medium text-bakery-muted">Último respaldo</dt>
                    <dd class="mt-1 text-sm font-semibold text-bakery-dark">
                        {{ $ultimo?->completado_en?->diffForHumans() ?? 'Aún no hay respaldo completo' }}
                    </dd>
                </div>
                <div class="rounded-xl border border-bakery-amber/15 bg-white/70 p-3">
                    <dt class="text-xs font-medium text-bakery-muted">Próximo respaldo</dt>
                    <dd class="mt-1 text-sm font-semibold text-bakery-dark">{{ $nextBackupLabel }}</dd>
                </div>
                <div class="rounded-xl border border-bakery-amber/15 bg-white/70 p-3">
                    <dt class="text-xs font-medium text-bakery-muted">Destino actual</dt>
                    <dd class="mt-1 text-sm font-semibold text-bakery-dark">{{ $destinationLabel }}</dd>
                </div>
                <div class="rounded-xl border border-bakery-amber/15 bg-white/70 p-3">
                    <dt class="text-xs font-medium text-bakery-muted">Retención</dt>
                    <dd class="mt-1 text-sm font-semibold text-bakery-dark">{{ $retentionLabel }}</dd>
                </div>
            </dl>

            <div class="hidden mt-4 rounded-xl border border-bakery-sage/20 bg-white/70 p-3 text-sm leading-6 text-bakery-muted">
                <strong class="font-semibold text-bakery-dark">Recomendado:</strong>
                respaldos automáticos diarios a las 02:00, base completa, destino local + respaldo en la nube y eliminación de copias mayores a 30 días.
            </div>
            <div class="mt-4 grid gap-3 lg:grid-cols-[minmax(0,1.35fr)_minmax(240px,.65fr)]">
                <div class="rounded-xl bg-bakery-dark p-4 text-white">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <p class="text-xs font-semibold text-white/70">Tamaño estimado del próximo respaldo</p>
                            <p class="mt-1 text-2xl font-bold tabular-nums">{{ $sizeEstimate['estimated'] }}</p>
                        </div>
                        <span class="rounded-full bg-white/10 px-2.5 py-1 text-xs font-semibold">Rango {{ $sizeEstimate['range'] }}</span>
                    </div>
                    <p class="mt-3 text-xs leading-5 text-white/70">Base de datos {{ $sizeEstimate['database'] }} + archivos críticos {{ $sizeEstimate['files'] }}. Margen de variación: 20%.</p>
                </div>
                <div class="rounded-xl border border-bakery-amber/20 bg-white p-4">
                    <div class="flex items-center gap-2">
                        <span class="h-2.5 w-2.5 rounded-full {{ $driveConfigured ? 'bg-bakery-sage' : 'bg-bakery-terracotta' }}" aria-hidden="true"></span>
                        <p class="text-sm font-semibold text-bakery-dark">Google Drive</p>
                    </div>
                    <p class="mt-2 text-xs leading-5 text-bakery-muted">{{ $driveConfigured ? 'Conectado.' : 'Falta conectar.' }}</p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="space-y-6">
                <div class="form-card">
                    <div class="flex items-center justify-between mb-6">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-bakery-gold/10 flex items-center justify-center">
                                <svg class="w-5 h-5 text-bakery-gold" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-base font-semibold text-bakery-dark">Respaldos automáticos</h3>
                                <p class="text-xs text-bakery-muted mt-0.5">Crea copias en el horario elegido.</p>
                            </div>
                        </div>
                        <x-backup.toggle-switch
                            name="auto_backup_enabled"
                            label="Activar respaldos automáticos"
                            :checked="old('auto_backup_enabled', $autoBackupEnabled ?? false)"
                            id="auto-backup-toggle"
                        />
                    </div>

                    <div class="space-y-4">
                        <fieldset>
                            <legend class="form-label">Frecuencia</legend>
                            <div class="grid grid-cols-3 gap-2">
                                @foreach(['daily' => 'Diario', 'weekly' => 'Semanal', 'monthly' => 'Mensual'] as $value => $lbl)
                                    <label class="relative">
                                        <input
                                            type="radio"
                                            name="backup_frequency"
                                            value="{{ $value }}"
                                            class="sr-only peer"
                                            x-model="frequency"
                                            {{ old('backup_frequency', $backupFrequency ?? 'daily') === $value ? 'checked' : '' }}
                                        >
                                        <div class="px-4 py-2.5 text-sm font-medium text-center rounded-xl border-2 cursor-pointer transition-all
                                            peer-checked:border-bakery-gold peer-checked:bg-bakery-gold/10 peer-checked:text-bakery-dark
                                            peer-focus-visible:ring-2 peer-focus-visible:ring-bakery-gold/40 peer-focus-visible:ring-offset-2
                                            border-bakery-amber/20 text-bakery-muted hover:border-bakery-amber/40 hover:bg-bakery-warm">
                                            {{ $lbl }}
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                            @error('backup_frequency')
                                <p class="mt-1.5 text-xs font-medium text-red-700">{{ $message }}</p>
                            @enderror
                        </fieldset>

                        <div>
                            <label for="backup_time" class="form-label">Hora de ejecución</label>
                            <input
                                type="time"
                                id="backup_time"
                                name="backup_time"
                                x-ref="backupTime"
                                class="form-input max-w-[180px]"
                                value="{{ old('backup_time', $backupTime ?? '02:00') }}"
                            >
                            <p class="text-xs text-bakery-muted mt-1.5">Lo más seguro es hacerlo fuera del horario de atención.</p>
                            @error('backup_time')
                                <p class="mt-1.5 text-xs font-medium text-red-700">{{ $message }}</p>
                            @enderror
                        </div>

                        <div x-show="frequency === 'weekly'" x-cloak>
                            <label for="backup_day" class="form-label">Día de la semana</label>
                            <select id="backup_day" name="backup_day" x-ref="backupDay" class="form-select max-w-[200px]">
                                @foreach(['monday' => 'Lunes', 'tuesday' => 'Martes', 'wednesday' => 'Miércoles', 'thursday' => 'Jueves', 'friday' => 'Viernes', 'saturday' => 'Sábado', 'sunday' => 'Domingo'] as $value => $lbl)
                                    <option value="{{ $value }}" {{ old('backup_day', $backupDay ?? 'monday') === $value ? 'selected' : '' }}>
                                        {{ $lbl }}
                                    </option>
                                @endforeach
                            </select>
                            @error('backup_day')
                                <p class="mt-1.5 text-xs font-medium text-red-700">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="form-card">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-10 h-10 rounded-xl bg-bakery-brown/10 flex items-center justify-center">
                            <svg class="w-5 h-5 text-bakery-brown" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-base font-semibold text-bakery-dark">Política de retención</h3>
                                <p class="text-xs text-bakery-muted mt-0.5">Define cuánto historial conservar.</p>
                        </div>
                    </div>

                    <fieldset class="space-y-3">
                        <legend class="sr-only">Política de retención</legend>

                        <label class="flex items-center gap-3 p-3 rounded-xl border cursor-pointer hover:bg-bakery-warm transition"
                            :class="rotationType === 'keep_all' ? 'border-bakery-gold bg-bakery-gold/5' : 'border-bakery-amber/20'">
                            <input type="radio" name="rotation_type" value="keep_all" x-model="rotationType"
                                class="w-4 h-4 text-bakery-gold focus:ring-bakery-gold/30"
                                {{ old('rotation_type', $rotationType ?? 'keep_all') === 'keep_all' ? 'checked' : '' }}>
                            <div class="flex-1">
                                <span class="text-sm font-medium text-bakery-dark">Mantener todos</span>
                            </div>
                        </label>

                        <label class="flex items-center gap-3 p-3 rounded-xl border cursor-pointer hover:bg-bakery-warm transition"
                            :class="rotationType === 'keep_count' ? 'border-bakery-gold bg-bakery-gold/5' : 'border-bakery-amber/20'">
                            <input type="radio" name="rotation_type" value="keep_count" x-model="rotationType"
                                class="w-4 h-4 text-bakery-gold focus:ring-bakery-gold/30"
                                {{ old('rotation_type', $rotationType ?? 'keep_all') === 'keep_count' ? 'checked' : '' }}>
                            <div class="flex-1">
                                <span class="text-sm font-medium text-bakery-dark">Mantener últimos</span>
                                <div class="flex items-center gap-2 mt-1" x-show="rotationType === 'keep_count'" x-cloak>
                                    <input type="number" name="rotation_keep_count" x-ref="rotationKeepCount" class="form-input w-20 py-1.5 text-sm"
                                        min="1" max="100" value="{{ old('rotation_keep_count', $rotationKeepCount ?? 7) }}">
                                    <span class="text-xs text-bakery-muted">respaldos</span>
                                </div>
                            </div>
                        </label>

                        <label class="flex items-center gap-3 p-3 rounded-xl border cursor-pointer hover:bg-bakery-warm transition"
                            :class="rotationType === 'keep_days' ? 'border-bakery-gold bg-bakery-gold/5' : 'border-bakery-amber/20'">
                            <input type="radio" name="rotation_type" value="keep_days" x-model="rotationType"
                                class="w-4 h-4 text-bakery-gold focus:ring-bakery-gold/30"
                                {{ old('rotation_type', $rotationType ?? 'keep_all') === 'keep_days' ? 'checked' : '' }}>
                            <div class="flex-1">
                                <span class="text-sm font-medium text-bakery-dark">Eliminar mayores de</span>
                                <div class="flex items-center gap-2 mt-1" x-show="rotationType === 'keep_days'" x-cloak>
                                    <input type="number" name="rotation_keep_days" x-ref="rotationKeepDays" class="form-input w-20 py-1.5 text-sm"
                                        min="1" max="365" value="{{ old('rotation_keep_days', $rotationKeepDays ?? 30) }}">
                                    <span class="text-xs text-bakery-muted">días</span>
                                </div>
                            </div>
                        </label>
                    </fieldset>
                    @error('rotation_type')
                        <p class="mt-1.5 text-xs font-medium text-red-700">{{ $message }}</p>
                    @enderror
                    @error('rotation_keep_count')
                        <p class="mt-1.5 text-xs font-medium text-red-700">{{ $message }}</p>
                    @enderror
                    @error('rotation_keep_days')
                        <p class="mt-1.5 text-xs font-medium text-red-700">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="space-y-6">
                <div class="form-card">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-10 h-10 rounded-xl bg-bakery-sage/10 flex items-center justify-center">
                            <svg class="w-5 h-5 text-bakery-sage" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-base font-semibold text-bakery-dark">Contenido del respaldo</h3>
                            <p class="text-xs text-bakery-muted mt-0.5">Datos y archivos necesarios para recuperar el sistema.</p>
                        </div>
                    </div>

                    <fieldset class="space-y-3">
                        <legend class="sr-only">Contenido del respaldo</legend>

                        <label class="flex items-start gap-3 p-3 rounded-xl border cursor-pointer hover:bg-bakery-warm transition"
                            :class="backupScope === 'full' ? 'border-bakery-gold bg-bakery-gold/5' : 'border-bakery-amber/20'">
                            <input type="radio" name="backup_scope" value="full" x-model="backupScope"
                                class="mt-0.5 w-4 h-4 text-bakery-gold focus:ring-bakery-gold/30"
                                {{ old('backup_scope', $backupScope ?? 'full') === 'full' ? 'checked' : '' }}>
                            <div class="flex-1">
                                <span class="text-sm font-medium text-bakery-dark">Base de datos</span>
                                <p class="text-xs text-bakery-muted mt-0.5">Todas las tablas del negocio.</p>
                            </div>
                        </label>

                    </fieldset>
                    @error('backup_scope')
                        <p class="mt-1.5 text-xs font-medium text-red-700">{{ $message }}</p>
                    @enderror

                    <label class="mt-3 flex items-center gap-3 p-3 rounded-xl border border-bakery-amber/20">
                        <input type="hidden" name="include_files" value="1">
                        <span class="flex h-5 w-5 items-center justify-center rounded-full bg-bakery-sage text-white" aria-hidden="true">✓</span>
                        <div class="flex-1">
                            <span class="text-sm font-medium text-bakery-dark">Archivos</span>
                            <p class="text-xs text-bakery-muted mt-0.5">Imágenes y documentos guardados.</p>
                        </div>
                    </label>
                </div>

                <div class="form-card">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-10 h-10 rounded-xl bg-bakery-brown/10 flex items-center justify-center">
                            <svg class="w-5 h-5 text-bakery-brown" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-base font-semibold text-bakery-dark">Almacenamiento</h3>
                            <p class="text-xs text-bakery-muted mt-0.5">Elige dónde guardar las copias.</p>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <fieldset>
                            <legend class="form-label">Destino</legend>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                <label class="relative">
                                    <input
                                        type="radio"
                                        name="backup_disk"
                                        value="local"
                                        class="sr-only peer"
                                        x-model="backupDisk"
                                        {{ old('backup_disk', $backupDisk ?? 'local') === 'local' ? 'checked' : '' }}
                                    >
                                    <div class="px-4 py-2.5 text-sm font-medium text-center rounded-xl border-2 cursor-pointer transition-all
                                        peer-checked:border-bakery-gold peer-checked:bg-bakery-gold/10 peer-checked:text-bakery-dark
                                        peer-focus-visible:ring-2 peer-focus-visible:ring-bakery-gold/40 peer-focus-visible:ring-offset-2
                                        border-bakery-amber/20 text-bakery-muted hover:border-bakery-amber/40 hover:bg-bakery-warm">
                                        Solo local
                                    </div>
                                </label>
                                <label class="relative">
                                    <input
                                        type="radio"
                                        name="backup_disk"
                                        value="google_drive"
                                        class="sr-only peer"
                                        x-model="backupDisk"
                                        {{ old('backup_disk', $backupDisk ?? 'local') === 'google_drive' ? 'checked' : '' }}
                                    >
                                    <div class="px-4 py-2.5 text-sm font-medium text-center rounded-xl border-2 cursor-pointer transition-all
                                        peer-checked:border-bakery-gold peer-checked:bg-bakery-gold/10 peer-checked:text-bakery-dark
                                        peer-focus-visible:ring-2 peer-focus-visible:ring-bakery-gold/40 peer-focus-visible:ring-offset-2
                                        border-bakery-amber/20 text-bakery-muted hover:border-bakery-amber/40 hover:bg-bakery-warm">
                                        Local + Google Drive
                                    </div>
                                </label>
                            </div>
                            <p class="text-xs text-bakery-muted mt-1.5">Guarda una segunda copia fuera de este equipo.</p>
                            <input type="hidden" name="cloud_disk" value="google_drive">
                            @error('backup_disk')
                                <p class="mt-1.5 text-xs font-medium text-red-700">{{ $message }}</p>
                            @enderror
                        </fieldset>

                        <div x-show="backupDisk === 'google_drive'" x-cloak>
                            <input type="hidden" name="cloud_path" x-ref="cloudPath" value="backups">
                            <div class="rounded-xl border border-bakery-amber/20 p-4">
                                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <span class="h-2.5 w-2.5 rounded-full {{ $driveConfigured ? 'bg-bakery-sage' : 'bg-bakery-amber' }}" aria-hidden="true"></span>
                                            <p class="text-sm font-semibold text-bakery-dark">Google Drive</p>
                                        </div>
                                        <p class="mt-1 text-xs text-bakery-muted">{{ $driveConfigured ? ($driveAccountEmail ?: 'Cuenta conectada') : 'Sin conectar' }}</p>
                                    </div>
                                    <div class="flex flex-wrap gap-2">
                                        @if($driveConfigured)
                                            <a href="{{ route('backups.google.check') }}" class="btn-secondary text-xs">Comprobar</a>
                                            <a href="{{ route('backups.google.connect') }}" class="btn-secondary text-xs">Cambiar cuenta</a>
                                            <button type="submit" formaction="{{ route('backups.google.disconnect') }}" formmethod="POST" formnovalidate class="btn-ghost text-xs text-bakery-terracotta">Desconectar</button>
                                        @elseif($driveClientConfigured)
                                            <a href="{{ route('backups.google.connect') }}" class="btn-secondary text-xs">Conectar cuenta</a>
                                        @endif
                                    </div>
                                </div>

                                @if(! $driveConfigured && ! $driveManagedClient)
                                    <div class="mt-4 space-y-3">
                                        <div>
                                            <label for="google_client_id" class="form-label">ID de cliente</label>
                                            <input id="google_client_id" name="google_client_id" class="form-input" value="{{ old('google_client_id', $driveClientId) }}" autocomplete="off">
                                            @error('google_client_id')<p class="mt-1 text-xs font-medium text-bakery-terracotta">{{ $message }}</p>@enderror
                                        </div>
                                        <div>
                                            <label for="google_client_secret" class="form-label">Clave de cliente</label>
                                            <input id="google_client_secret" name="google_client_secret" type="password" class="form-input" autocomplete="new-password" placeholder="{{ $driveClientConfigured ? 'Guardada' : '' }}">
                                            @error('google_client_secret')<p class="mt-1 text-xs font-medium text-bakery-terracotta">{{ $message }}</p>@enderror
                                        </div>
                                        <div>
                                            <label for="google_callback_url" class="form-label">URL autorizada</label>
                                            <input id="google_callback_url" class="form-input" value="{{ route('backups.google.callback') }}" readonly>
                                        </div>
                                        <p class="text-xs text-bakery-muted">Guarda los cambios y conecta la cuenta.</p>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="p-3 bg-bakery-warm rounded-xl border border-bakery-amber/15">
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-bakery-muted">Espacio disponible</span>
                                <span class="font-medium text-bakery-dark">{{ $availableSpace ?? '—' }}</span>
                            </div>
                        </div>

                        <label class="hidden flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="compress_backups" value="1"
                                class="w-4 h-4 text-bakery-gold focus:ring-bakery-gold/30 rounded"
                                {{ old('compress_backups', $compressBackups ?? true) ? 'checked' : '' }}>
                            <span class="text-sm text-bakery-dark">Comprimir respaldos para ahorrar espacio</span>
                        </label>
                        <input type="hidden" name="compress_backups" value="0">
                    </div>
                </div>

                <div class="form-card">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 rounded-xl bg-bakery-amber/10 flex items-center justify-center">
                            <svg class="w-5 h-5 text-bakery-amber" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-base font-semibold text-bakery-dark">Notificaciones</h3>
                            <p class="text-xs text-bakery-muted mt-0.5">Avisos simples para saber si el respaldo terminó bien.</p>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <x-backup.toggle-switch
                            name="notify_on_success"
                            label="Notificar cuando termine exitosamente"
                            :checked="old('notify_on_success', $notifyOnSuccess ?? true)"
                        />
                        <x-backup.toggle-switch
                            name="notify_on_failure"
                            label="Notificar cuando falle"
                            :checked="old('notify_on_failure', $notifyOnFailure ?? true)"
                        />
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-6 flex items-center justify-end gap-3">
            <a href="{{ route('backups.index') }}" class="btn-secondary">Cancelar</a>
            <button type="submit" class="btn-primary" :disabled="saving">
                <svg x-show="saving" class="w-4 h-4 animate-spin" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                <span x-show="!saving">Guardar configuración</span>
                <span x-show="saving">Guardando...</span>
            </button>
        </div>
    </form>
</x-app-layout>
