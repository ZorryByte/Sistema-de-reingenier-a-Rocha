@props([
    'backup' => (object) [],
])

@php
    $nivelConfig = [
        'diario' => ['label' => 'Diario', 'badge' => 'badge-success'],
        'semanal' => ['label' => 'Semanal', 'badge' => 'badge-warning'],
        'mensual' => ['label' => 'Mensual', 'badge' => 'badge-neutral'],
        'manual' => ['label' => 'Manual', 'badge' => 'badge-neutral'],
    ];
    $nivel = $nivelConfig[$backup->nivel ?? 'manual'] ?? $nivelConfig['manual'];

    $estadoConfig = [
        'completado' => ['icon' => 'check', 'badge' => 'badge-success', 'label' => 'Completado'],
        'fallido' => ['icon' => 'x', 'badge' => 'badge-danger', 'label' => 'Fallido'],
        'en_progreso' => ['icon' => 'spinner', 'badge' => 'badge-warning', 'label' => 'En progreso'],
    ];
    $estado = $estadoConfig[$backup->estado ?? 'completado'] ?? $estadoConfig['completado'];

    $cloudConfig = [
        'sincronizado' => ['badge' => 'badge-success', 'label' => 'Backblaze'],
        'pendiente' => ['badge' => 'badge-warning', 'label' => 'Pendiente'],
        'fallido' => ['badge' => 'badge-danger', 'label' => 'Fallido'],
        'no_configurado' => ['badge' => 'badge-neutral', 'label' => 'Local'],
    ];
    $cloud = $cloudConfig[$backup->cloud_estado ?? 'no_configurado'] ?? $cloudConfig['no_configurado'];
@endphp

<tr class="group hover:bg-bakery-warm/50 transition-colors duration-150">
    <td class="pl-4 pr-3 py-3">
        <input
            type="checkbox"
            name="backups[]"
            value="{{ $backup->id }}"
            class="w-4 h-4 rounded border-bakery-amber/30 text-bakery-gold focus:ring-bakery-gold/30"
            aria-label="Seleccionar respaldo {{ $backup->id }}"
        >
    </td>

    <td class="px-3 py-3">
        <span class="text-sm text-bakery-dark">{{ $backup->created_at ? (method_exists($backup->created_at, 'format') ? $backup->created_at->format('d/m/Y H:i') : $backup->created_at) : '—' }}</span>
    </td>

    <td class="px-3 py-3">
        <span class="{{ $nivel['badge'] }}">{{ $nivel['label'] }}</span>
    </td>

    <td class="px-3 py-3">
        <span class="text-sm text-bakery-muted">{{ $backup->tamano_humano ?? '—' }}</span>
    </td>

    <td class="px-3 py-3">
        <span class="{{ $estado['badge'] }}">
            @if($estado['icon'] === 'check')
                <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                </svg>
            @elseif($estado['icon'] === 'x')
                <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            @else
                <svg class="w-3 h-3 animate-spin" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
            @endif
            {{ $estado['label'] }}
        </span>
    </td>

    <td class="px-3 py-3">
        <span class="{{ $cloud['badge'] }}">{{ $cloud['label'] }}</span>
    </td>

    <td class="pr-4 pl-3 py-3">
        <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 focus-within:opacity-100 transition-opacity">
            <a
                href="{{ route('backups.download', $backup->id) }}"
                class="btn-icon"
                title="Descargar"
                aria-label="Descargar respaldo"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
            </a>

            <form method="POST" action="{{ route('backups.verify', $backup->id) }}" class="inline">
                @csrf
                <button
                    type="submit"
                    class="btn-icon"
                    title="Verificar"
                    aria-label="Verificar integridad del respaldo"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                </button>
            </form>

            <a
                href="{{ route('backups.restore', $backup->id) }}"
                class="btn-icon"
                title="Restaurar"
                aria-label="Restaurar respaldo"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m0 0a8.001 8.001 0 0115.356 2M4.582 9H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
            </a>

            <button
                type="button"
                class="btn-icon"
                title="Eliminar"
                aria-label="Eliminar respaldo"
                @click="$dispatch('open-confirm-modal', {
                    action: '{{ route('backups.destroy', $backup->id) }}',
                    method: 'DELETE',
                    confirmText: 'Eliminar respaldo',
                    consequences: 'Este respaldo será eliminado permanentemente.',
                    warning: 'Esta acción no se puede deshacer.'
                })"
            >
                <svg class="w-4 h-4 text-bakery-terracotta/70" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
            </button>
        </div>
    </td>
</tr>
