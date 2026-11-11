@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'border-bakery-amber/30 focus:border-bakery-gold focus:ring-bakery-gold rounded-md shadow-sm']) }}>
