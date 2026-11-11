@props(['value'])

<label {{ $attributes->merge(['class' => 'block font-medium text-sm text-bakery-dark']) }}>
    {{ $value ?? $slot }}
</label>
