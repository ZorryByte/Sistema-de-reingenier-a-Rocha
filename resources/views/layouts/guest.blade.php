<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ request()->routeIs('login') ? 'Iniciar sesión | ' : '' }}Rocha Bakery &amp; Café</title>

        <!-- Fonts: Montserrat (display) + Nunito (body) - mismo que app layout -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=montserrat:600,700|nunito:400,500,600,700" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>
            /* Fondo con patron sutil de puntos - sin requests externos */
            .bg-pattern {
                background-color: #f5f0e8;
                background-image: radial-gradient(circle, #e8ddd0 1px, transparent 1px);
                background-size: 24px 24px;
            }
            /* Animacion fade-in al cargar */
            @keyframes fadeInUp {
                from { opacity: 0; transform: translateY(12px); }
                to { opacity: 1; transform: translateY(0); }
            }
            .animate-fade-in {
                animation: fadeInUp 0.5s ease-out forwards;
            }
            /* Shake en errores de validacion */
            @keyframes shake {
                0%, 100% { transform: translateX(0); }
                20% { transform: translateX(-4px); }
                40% { transform: translateX(4px); }
                60% { transform: translateX(-4px); }
                80% { transform: translateX(4px); }
            }
            .animate-shake {
                animation: shake 0.4s ease-in-out;
            }
        </style>
    </head>
    <body class="font-sans text-bakery-dark antialiased bg-pattern min-h-screen flex flex-col items-center justify-center">
        <div class="w-full sm:max-w-md mx-auto px-6 py-8 animate-fade-in">
            {{ $slot }}
        </div>
    </body>
</html>