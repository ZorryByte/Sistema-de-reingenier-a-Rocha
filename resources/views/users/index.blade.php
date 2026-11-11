<x-app-layout>
    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="font-display text-xl font-bold text-bakery-dark">Gestion de Usuarios</h2>
                    <p class="text-sm text-bakery-muted mt-1">Administra los usuarios del sistema</p>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <!-- Filtro -->
                    <div class="inline-flex rounded-lg border border-bakery-amber/20 bg-white p-1">
                        <a href="{{ route('usuarios.index', ['filtro' => 'activos']) }}"
                           class="px-3 py-1 text-xs font-semibold rounded-md transition-colors {{ $filtro === 'activos' ? 'bg-bakery-gold text-white' : 'text-bakery-muted hover:text-bakery-dark' }}">
                            Activos
                        </a>
                        <a href="{{ route('usuarios.index', ['filtro' => 'inactivos']) }}"
                           class="px-3 py-1 text-xs font-semibold rounded-md transition-colors {{ $filtro === 'inactivos' ? 'bg-bakery-gold text-white' : 'text-bakery-muted hover:text-bakery-dark' }}">
                            Inactivos
                        </a>
                        <a href="{{ route('usuarios.index', ['filtro' => 'todos']) }}"
                           class="px-3 py-1 text-xs font-semibold rounded-md transition-colors {{ $filtro === 'todos' ? 'bg-bakery-gold text-white' : 'text-bakery-muted hover:text-bakery-dark' }}">
                            Todos
                        </a>
                    </div>
                    <button type="button" onclick="openCreateModal()"
                            class="inline-flex items-center px-4 py-2 bg-bakery-gold text-white text-sm font-semibold rounded-md hover:bg-bakery-brown transition-colors">
                        <svg class="h-4 w-4 mr-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                        </svg>
                        Nuevo Usuario
                    </button>
                </div>
            </div>

            <!-- Flash Messages -->
            @if (session('success'))
                <div class="mb-4 px-4 py-3 flash-success rounded-xl text-sm flex items-center gap-2">
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

            <!-- Users Table -->
            <div class="bg-white rounded-2xl shadow-sm border border-bakery-amber/10 overflow-hidden">
                <div class="overflow-x-auto" tabindex="0" role="region" aria-label="Tabla de usuarios">
                <table class="min-w-full divide-y divide-bakery-amber/10">
                    <thead class="bg-bakery-warm">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-bakery-muted uppercase tracking-wider">Nombre</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-bakery-muted uppercase tracking-wider">Email</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-bakery-muted uppercase tracking-wider">Rol</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-bakery-muted uppercase tracking-wider">Estado</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-bakery-muted uppercase tracking-wider">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-bakery-amber/10">
                        @forelse ($users as $user)
                            <tr class="hover:bg-bakery-warm transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-bakery-dark">{{ $user->name }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-bakery-muted">{{ $user->email }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-semibold
                                        {{ $user->esAdmin() ? 'bg-bakery-gold/15 text-bakery-brown' : 'bg-bakery-warm text-bakery-muted' }}">
                                        {{ $user->rol === 'admin' ? 'Admin' : 'Caja' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center text-xs font-semibold
                                        {{ $user->activo ? 'text-bakery-sage' : 'text-bakery-terracotta' }}">
                                        <span class="h-2 w-2 rounded-full mr-1.5 {{ $user->activo ? 'bg-bakery-sage' : 'bg-bakery-terracotta' }}"></span>
                                        {{ $user->activo ? 'Activo' : 'Inactivo' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                                    <div class="flex items-center justify-end space-x-3">
                                        <!-- Editar como texto -->
                                        <a href="{{ route('usuarios.edit', $user) }}"
                                           class="text-xs font-medium text-bakery-gold hover:text-bakery-brown transition-colors">
                                            Editar
                                        </a>
                                        <!-- Toggle Activo -->
                                        <form method="POST" action="{{ route('usuarios.toggle-activo', $user) }}" class="inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit"
                                                    class="text-xs font-medium transition-colors {{ $user->activo ? 'text-bakery-sage hover:text-bakery-terracotta' : 'text-bakery-terracotta hover:text-bakery-sage' }}"
                                                    title="{{ $user->activo ? 'Desactivar' : 'Activar' }}">
                                                {{ $user->activo ? 'Desactivar' : 'Activar' }}
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-8 text-center text-sm text-bakery-muted">
                                    No hay usuarios registrados.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                </div>
            </div>

            <!-- Pagination -->
            <div class="mt-4">
                {{ $users->links('vendor.pagination.bakery') }}
            </div>
        </div>
    </div>

    <!-- Modal Crear Usuario -->
    <div id="modal-create" class="fixed inset-0 z-50 hidden">
        <div class="fixed inset-0 bg-black/50" onclick="closeCreateModal()"></div>
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-md p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-display text-lg font-bold text-bakery-dark">Nuevo Usuario</h3>
                    {{-- Accessible label for screen readers --}}
                    <button type="button" onclick="closeCreateModal()" aria-label="Cerrar modal" class="text-bakery-muted hover:text-bakery-dark">
                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                @if ($errors->any())
                    <div class="mb-4 px-3 py-2 bg-bakery-terracotta/10 border border-bakery-terracotta/20 rounded-xl">
                        @foreach ($errors->all() as $error)
                            <p class="text-xs text-bakery-terracotta">{{ $error }}</p>
                        @endforeach
                    </div>
                @endif

                <!-- Required field legend -->
                <p class="text-xs text-bakery-muted mb-4 italic">Los campos marcados con * son obligatorios</p>

                <form action="{{ route('usuarios.store') }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="form-label form-label-required" for="name">Nombre</label>
                        <input type="text" id="name" name="name" value="{{ old('name') }}" required
                               class="form-input">
                    </div>
                    <div>
                        <label class="form-label form-label-required" for="email">Email</label>
                        <input type="email" id="email" name="email" value="{{ old('email') }}" required
                               class="form-input">
                    </div>
                    <div>
                        <label class="form-label form-label-required" for="password">Contrase&ntilde;a</label>
                        <input type="password" id="password" name="password" required minlength="8"
                               class="form-input">
                    </div>
                    <div>
                        <label class="form-label form-label-required" for="password_confirmation">Confirmar Contrase&ntilde;a</label>
                        <input type="password" id="password_confirmation" name="password_confirmation" required minlength="8"
                               class="form-input">
                    </div>
                    <div>
                        <label class="form-label form-label-required" for="rol">Rol</label>
                        <select id="rol" name="rol" required
                                class="form-select">
                            <option value="caja">Caja</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" onclick="closeCreateModal()" class="btn-secondary">
                            Cancelar
                        </button>
                        <button type="submit" class="btn-primary">
                            Crear
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function openCreateModal() {
            document.getElementById('modal-create').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }
        function closeCreateModal() {
            document.getElementById('modal-create').classList.add('hidden');
            document.body.style.overflow = '';
        }
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeCreateModal();
        });
    </script>
</x-app-layout>