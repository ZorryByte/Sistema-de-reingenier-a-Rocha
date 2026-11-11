<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center px-4 py-2 bg-bakery-gold border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-bakery-brown focus:bg-bakery-brown active:bg-bakery-dark focus:outline-none focus:ring-2 focus:ring-bakery-gold focus:ring-offset-2 transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>
