<x-guest-layout>
    <div>
        <!-- Logo + Branding (centrado) -->
        <div class="mb-6 text-center">
            <x-application-logo class="w-32 h-auto object-contain mx-auto" />
            <h1 class="font-display text-2xl font-bold text-bakery-dark mt-3">Rocha Bakery & Café</h1>
            <p class="text-sm text-bakery-muted mt-1">Sistema de gestión</p>
        </div>

        <!-- Session Status -->
        <x-auth-session-status class="mb-4 w-full" :status="session('status')" />

        <!-- Login Form -->
        <form method="POST" action="{{ route('login') }}" class="w-full" x-data="{ showPassword: false }" :class="{ 'animate-shake': {{ $errors->any() ? 'true' : 'false' }} }">
            @csrf

            <!-- Email Address -->
            <div>
                <x-input-label for="email" :value="__('Email')" />
                <div class="flex items-center border border-bakery-amber/30 rounded-md overflow-hidden focus-within:border-bakery-gold focus-within:ring-1 focus-within:ring-bakery-gold mt-1">
                    <div class="flex items-center justify-center w-10 shrink-0">
                        <svg class="h-5 w-5 text-bakery-muted/50" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z" />
                            <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z" />
                        </svg>
                    </div>
                    <x-text-input id="email" :aria-invalid="$errors->has('email') ? 'true' : null" :aria-describedby="$errors->has('email') ? 'login-email-error' : null" class="flex-1 border-0 focus:border-0 focus:ring-0" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" placeholder="correo@ejemplo.com" />
                </div>
                <x-input-error id="login-email-error" :messages="$errors->get('email')" class="mt-2" />
            </div>

            <!-- Password -->
            <div class="mt-4">
                <x-input-label for="password" :value="__('Contraseña')" />
                <div class="flex items-center border border-bakery-amber/30 rounded-md overflow-hidden focus-within:border-bakery-gold focus-within:ring-1 focus-within:ring-bakery-gold mt-1">
                    <div class="flex items-center justify-center w-10 shrink-0">
                        <svg class="h-5 w-5 text-bakery-muted/50" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <x-text-input id="password" :aria-invalid="$errors->has('password') ? 'true' : null" :aria-describedby="$errors->has('password') ? 'login-password-error' : null" class="flex-1 border-0 focus:border-0 focus:ring-0"
                        x-bind:type="showPassword ? 'text' : 'password'"
                        name="password"
                        required autocomplete="current-password"
                        placeholder="••••••••" />
                    <button type="button" class="flex items-center justify-center w-10 shrink-0 text-bakery-muted/50 hover:text-bakery-gold transition-colors" @click="showPassword = !showPassword" :aria-label="showPassword ? 'Ocultar contraseña' : 'Mostrar contraseña'">
                        <svg x-show="!showPassword" class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M10 12a2 2 0 100-4 2 2 0 000 4z" />
                            <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd" />
                        </svg>
                        <svg x-show="showPassword" class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="display: none;">
                            <path fill-rule="evenodd" d="M3.707 2.293a1 1 0 00-1.414 1.414l14 14a1 1 0 001.414-1.414l-1.473-1.473A10.014 10.014 0 0019.542 10C18.268 5.943 14.478 3 10 3a9.958 9.958 0 00-4.512 1.074l-1.78-1.781zm4.261 4.26l1.514 1.515a2.003 2.003 0 012.45 2.45l1.514 1.514a4 4 0 00-5.478-5.478z" clip-rule="evenodd" />
                            <path d="M12.454 16.697L9.75 13.992a4 4 0 01-3.742-3.741L2.335 6.578A9.98 9.98 0 00.458 10c1.274 4.057 5.065 7 9.542 7 .847 0 1.669-.105 2.454-.303z" />
                        </svg>
                    </button>
                </div>
                <x-input-error id="login-password-error" :messages="$errors->get('password')" class="mt-2" />
            </div>

            <!-- Remember Me -->
            <div class="block mt-4">
                <label for="remember_me" class="inline-flex items-center cursor-pointer">
                    <input id="remember_me" type="checkbox" class="rounded border-bakery-amber/30 text-bakery-gold shadow-sm focus:ring-bakery-gold" name="remember">
                    <span class="ms-2 text-sm text-bakery-muted">{{ __('Recordarme') }}</span>
                </label>
            </div>

            <!-- Submit + Forgot Password -->
            <div class="flex items-center justify-between mt-6">
                @if (Route::has('password.request'))
                    <a class="text-sm text-bakery-muted hover:text-bakery-gold underline underline-offset-2 transition-colors" href="{{ route('password.request') }}">
                        {{ __('¿Olvidaste tu contraseña?') }}
                    </a>
                @endif

                <x-primary-button class="ms-auto">
                    {{ __('Iniciar Sesión') }}
                </x-primary-button>
            </div>
        </form>
    </div>
</x-guest-layout>