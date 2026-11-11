<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center px-4 py-2 bg-bakery-terracotta border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-600 active:bg-red-700 focus:outline-none focus:ring-2 focus:ring-bakery-terracotta focus:ring-offset-2 transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>
