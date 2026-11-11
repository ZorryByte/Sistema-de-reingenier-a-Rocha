@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'font-medium text-sm text-bakery-sage']) }}>
        {{ $status }}
    </div>
@endif
