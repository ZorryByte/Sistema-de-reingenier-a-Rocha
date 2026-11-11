<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center px-4 py-2 bg-white border border-bakery-amber/30 rounded-md font-semibold text-xs text-bakery-dark uppercase tracking-widest shadow-sm hover:bg-bakery-warm focus:outline-none focus:ring-2 focus:ring-bakery-gold focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>
