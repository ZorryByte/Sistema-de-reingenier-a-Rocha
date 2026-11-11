<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Rocha Bakery &amp; Café</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>
    <link href="https://fonts.bunny.net/css?family=montserrat:600,700|nunito:400,500,600,700&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="m-0 font-sans antialiased bg-bakery-cream min-h-screen flex flex-col">

    {{-- Navbar --}}
    @include('layouts.navigation')

    <main class="flex-1 dashboard-bg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            @if(request()->routeIs('finanzas.*'))
                @include('finanzas.partials.nav')
            @endif

            {{ $slot }}
        </div>
    </main>

    {{-- Footer --}}
    <footer class="bg-white border-t border-bakery-amber/20">
        <div class="max-w-7xl mx-auto px-4 py-4 text-sm text-bakery-muted text-center">
            &copy; {{ date('Y') }} - Rocha B&C
        </div>
    </footer>

    @stack('scripts')
    <x-confirm-modal />
</body>
</html>
