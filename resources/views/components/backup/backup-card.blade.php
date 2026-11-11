@props([
    'backup' => (object) [],
    'selected' => false,
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
        'completado' => ['icon' => 'check', 'color' => 'text-bakery-sage'],
        'fallido' => ['icon' => 'x', 'color' => 'text-bakery-terracotta'],
        'en_progreso' => ['icon' => 'spinner', 'color' => 'text-bakery-amber'],
    ];
    $estado = $estadoConfig[$backup->estado ?? 'completado'] ?? $estadoConfig['completado'];

    $cloudConfig = [
        'sincronizado' => ['badge' => 'badge-success', 'label' => 'Backblaze'],
        'pendiente' => ['badge' => 'badge-warning', 'label' => 'Pendiente nube'],
        'fallido' => ['badge' => 'badge-danger', 'label' => 'Nube fallida'],
        'no_configurado' => ['badge' => 'badge-neutral', 'label' => 'Local'],
    ];
    $cloud = $cloudConfig[$backup->cloud_estado ?? 'no_configurado'] ?? $cloudConfig['no_configurado'];
@endphp

<div
    x-data="{ isSelected: {{ $selected ? 'true' : 'false' }} }"
    @click="$dispatch('backup-card-clicked', { id: {{ $backup->id ?? 0 }}, date: '{{ $backup->created_at ? (method_exists($backup->created_at, 'format') ? $backup->created_at->format('d/m/Y H:i') : $backup->created_at) : '' }}' })"
    @backup-card-selected.window="isSelected = ($event.detail.id === {{ $backup->id ?? 0 }})"
    @keydown.space.prevent="$dispatch('backup-card-clicked', { id: {{ $backup->id ?? 0 }}, date: '{{ $backup->created_at ? (method_exists($backup->created_at, 'format') ? $backup->created_at->format('d/m/Y H:i') : $backup->created_at) : '' }}' })"
    @keydown.enter.prevent="$dispatch('backup-card-clicked', { id: {{ $backup->id ?? 0 }}, date: '{{ $backup->created_at ? (method_exists($backup->created_at, 'format') ? $backup->created_at->format('d/m/Y H:i') : $backup->created_at) : '' }}' })"
    class="relative p-4 rounded-2xl border-2 cursor-pointer transition-all duration-200"
    :class="isSelected
        ? 'border-bakery-gold bg-bakery-gold/5 shadow-md'
        : 'border-bakery-amber/15 bg-white hover:border-bakery-amber/30 hover:shadow-sm'"
    role="radio"
    :aria-checked="isSelected.toString()"
    tabindex="0"
>
    <div class="flex items-start gap-3">
        <div
            class="w-5 h-5 rounded-full border-2 flex-shrink-0 flex items-center justify-center transition-all duration-150 mt-0.5"
            :class="isSelected ? 'border-bakery-gold bg-bakery-gold' : 'border-bakery-amber/30'"
        >
            <svg
                x-show="isSelected"
                x-transition:enter="transition ease-out duration-150"
                x-transition:enter-start="opacity-0 scale-75"
                x-transition:enter-end="opacity-100 scale-100"
                class="w-3 h-3 text-white"
                fill="currentColor"
                viewBox="0 0 20 20"
            >
                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
            </svg>
        </div>

        <div class="flex-1 min-w-0">
            <div class="flex items-center justify-between mb-1.5">
                <span class="text-sm font-semibold text-bakery-dark">
                    {{ $backup->created_at ? (method_exists($backup->created_at, 'format') ? $backup->created_at->format('d/m/Y H:i') : $backup->created_at) : '—' }}
                </span>
                <span class="{{ $nivel['badge'] }}">{{ $nivel['label'] }}</span>
            </div>
            <div class="flex items-center gap-3">
                <span class="inline-flex items-center gap-1 text-xs text-bakery-muted">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"/>
                    </svg>
                    {{ $backup->tamano_humano ?? '—' }}
                </span>
                <span class="inline-flex items-center gap-1 {{ $estado['color'] }}">
                    @if($estado['icon'] === 'check')
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                        </svg>
                    @elseif($estado['icon'] === 'x')
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    @else
                        <svg class="w-3.5 h-3.5 animate-spin" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                    @endif
                </span>
                <span class="{{ $cloud['badge'] }}">{{ $cloud['label'] }}</span>
            </div>
        </div>
    </div>
</div>
