@props(['lines' => 3, 'hasHeader' => true])

<div class="bg-white rounded-2xl shadow-sm border border-bakery-amber/20 p-5 animate-pulse">
    @if($hasHeader)
    <div class="h-4 bg-bakery-amber/10 rounded w-1/3 mb-4"></div>
    @endif
    @for($i = 0; $i < $lines; $i++)
    <div class="h-3 bg-bakery-amber/10 rounded mb-2 {{ $i === $lines - 1 ? '' : '' }}" style="width: {{ 100 - ($i * 20) }}%"></div>
    @endfor
</div>
