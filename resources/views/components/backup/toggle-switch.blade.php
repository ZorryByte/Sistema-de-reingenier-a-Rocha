@props([
    'name' => 'toggle',
    'label' => '',
    'checked' => false,
    'disabled' => false,
    'id' => null,
])

@php
    $switchId = $id ?? $name.'-switch';
@endphp

<div
    x-data="{
        enabled: {{ $checked ? 'true' : 'false' }},
    }"
    @backup-toggle.window="if ($event.detail.name === '{{ $name }}') enabled = Boolean($event.detail.enabled)"
    class="inline-flex items-center gap-3"
>
    <input type="hidden" name="{{ $name }}" :value="enabled ? '1' : '0'">

    <button
        id="{{ $switchId }}"
        type="button"
        role="switch"
        aria-label="{{ $label ?: $name }}"
        :aria-checked="enabled.toString()"
        @click="{{ $disabled ? '' : 'enabled = !enabled' }}"
        @keydown.space.prevent="{{ $disabled ? '' : 'enabled = !enabled' }}"
        {{ $disabled ? 'disabled' : '' }}
        :class="enabled ? 'bg-bakery-sage' : 'bg-bakery-muted/30'"
        class="relative inline-flex h-6 w-11 flex-shrink-0 rounded-full transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-bakery-gold/50 focus:ring-offset-2 {{ $disabled ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer' }}"
    >
        <span
            class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow-md ring-0 transition duration-200 ease-in-out"
            :class="enabled ? 'translate-x-5' : 'translate-x-0.5'"
        ></span>
    </button>

    @if($label)
        <span
            class="text-sm font-medium text-bakery-dark select-none {{ $disabled ? 'opacity-50' : 'cursor-pointer' }}"
            @click="{{ $disabled ? '' : 'enabled = !enabled' }}"
        >{{ $label }}</span>
    @endif
</div>
