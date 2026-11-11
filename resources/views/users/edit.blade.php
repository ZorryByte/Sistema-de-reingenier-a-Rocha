<x-app-layout>
    <div class="py-8">
        <div class="max-w-md mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-6">
                <a href="{{ route('usuarios.index') }}" class="inline-flex items-center text-sm text-bakery-muted hover:text-bakery-dark mb-2">
                    <svg class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                    Volver a Gestion de Usuarios
                </a>
                <h2 class="font-display text-xl font-bold text-bakery-dark">Editar Usuario</h2>
                <p class="text-sm text-bakery-muted mt-1">{{ $user->name }}</p>
            </div>

            <!-- Flash Messages -->
            @if (session('success'))
                <div class="mb-4 px-4 py-3 bg-bakery-sage/10 border border-bakery-sage/30 rounded-md text-sm text-bakery-sage flex items-center gap-2">
                    <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    {{ session('success') }}
                </div>
            @endif
            @if (session('error'))
                <div class="mb-4 px-4 py-3 bg-bakery-terracotta/10 border border-bakery-terracotta/20 rounded-md text-sm text-bakery-terracotta flex items-center gap-2">
                    <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    {{ session('error') }}
                </div>
            @endif

            <!-- Edit Form -->
            <div class="bg-white rounded-lg shadow-sm border border-bakery-amber/10 p-6">
                @if ($errors->any())
                    <div class="mb-4 px-3 py-2 bg-bakery-terracotta/10 border border-bakery-terracotta/20 rounded-md">
                        @foreach ($errors->all() as $error)
                            <p class="text-xs text-bakery-terracotta">{{ $error }}</p>
                        @endforeach
                    </div>
                @endif

                <form action="{{ route('usuarios.update', $user) }}" method="POST" class="space-y-4">
                    @csrf
                    @method('PATCH')
                    <div>
                        <label class="block text-sm font-medium text-bakery-dark mb-1">Nombre</label>
                        <input type="text" name="name" value="{{ old('name', $user->name) }}" required
                               class="w-full border border-bakery-amber/30 rounded-md px-3 py-2 text-sm focus:border-bakery-gold focus:ring-1 focus:ring-bakery-gold outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-bakery-dark mb-1">Email</label>
                        <input type="email" name="email" value="{{ old('email', $user->email) }}" required
                               class="w-full border border-bakery-amber/30 rounded-md px-3 py-2 text-sm focus:border-bakery-gold focus:ring-1 focus:ring-bakery-gold outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-bakery-dark mb-1">
                            Contraseña
                            <span class="text-xs text-bakery-muted">(dejar vacio para mantener)</span>
                        </label>
                        <input type="password" name="password" minlength="8"
                               class="w-full border border-bakery-amber/30 rounded-md px-3 py-2 text-sm focus:border-bakery-gold focus:ring-1 focus:ring-bakery-gold outline-none"
                               placeholder="Minimo 8 caracteres">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-bakery-dark mb-1">Confirmar Contraseña</label>
                        <input type="password" name="password_confirmation" minlength="8"
                               class="w-full border border-bakery-amber/30 rounded-md px-3 py-2 text-sm focus:border-bakery-gold focus:ring-1 focus:ring-bakery-gold outline-none"
                               placeholder="Repetir contraseña">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-bakery-dark mb-1">Rol</label>
                        <select name="rol" required
                                class="w-full border border-bakery-amber/30 rounded-md px-3 py-2 text-sm focus:border-bakery-gold focus:ring-1 focus:ring-bakery-gold outline-none">
                            <option value="caja" {{ old('rol', $user->rol) === 'caja' ? 'selected' : '' }}>Caja</option>
                            <option value="admin" {{ old('rol', $user->rol) === 'admin' ? 'selected' : '' }}>Admin</option>
                        </select>
                    </div>
                    <div>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="hidden" name="activo" value="0">
                            <input type="checkbox" name="activo" value="1"
                                {{ old('activo', $user->activo) ? 'checked' : '' }}
                                class="rounded border-bakery-amber/30 text-bakery-gold focus:ring-1 focus:ring-bakery-gold">
                            <span class="text-sm font-medium text-bakery-dark">Usuario activo</span>
                        </label>
                    </div>
                    <div class="flex justify-end gap-2 pt-2">
                        <a href="{{ route('usuarios.index') }}" class="px-4 py-2 text-sm font-medium text-bakery-muted border border-bakery-amber/30 rounded-md hover:bg-bakery-warm transition-colors">
                            Cancelar
                        </a>
                        <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-bakery-gold rounded-md hover:bg-bakery-brown transition-colors">
                            Actualizar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>