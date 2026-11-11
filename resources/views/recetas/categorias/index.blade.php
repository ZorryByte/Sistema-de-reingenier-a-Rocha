<x-app-layout>
<div class="py-10 px-6 max-w-5xl mx-auto" x-data="{ editing: null }">

    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between mb-8">
        <div>
            <a href="{{ route('recetas.index') }}" class="text-[13px] font-semibold text-bakery-gold hover:text-bakery-brown">
                Volver a recetas
            </a>
            <h1 class="mt-3 text-xl font-display font-bold text-bakery-dark tracking-tight leading-tight">Categorias de recetas</h1>
            <p class="text-[13px] text-bakery-muted mt-1 leading-relaxed">Mantiene organizado el catalogo y facilita busquedas por tipo de receta.</p>
        </div>
    </div>

    @if(session('error'))
        <div class="mb-6 bg-red-50 border border-red-200/60 text-red-700 rounded-xl px-5 py-3.5 text-[13px] leading-relaxed" role="alert">
            {{ session('error') }}
        </div>
    @endif

    @if($errors->any())
        <div class="mb-6 bg-red-50 border border-red-200/60 text-red-700 rounded-xl px-5 py-3.5 text-[13px] leading-relaxed" role="alert">
            <p class="font-semibold mb-1">Revisa los datos:</p>
            <ul class="space-y-1 list-disc list-inside">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-[minmax(0,1fr)_320px] gap-6">
        <div class="form-card overflow-hidden p-0">
            <div class="px-5 py-4 border-b border-bakery-amber/10 flex items-center justify-between">
                <div>
                    <h2 class="text-sm font-semibold text-bakery-dark">Listado alfabetico</h2>
                    <p class="text-xs text-bakery-muted mt-0.5">{{ $categorias->count() }} categorias registradas</p>
                </div>
            </div>

            <div class="divide-y divide-bakery-amber/10">
                @forelse($categorias as $categoria)
                    <div class="px-5 py-4">
                        <div x-show="editing !== {{ $categoria->id }}" class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="font-semibold text-bakery-dark truncate">{{ $categoria->nombre }}</p>
                                </div>
                                <p class="text-xs text-bakery-muted mt-1">
                                    {{ $categoria->recetas_count }} receta{{ $categoria->recetas_count === 1 ? '' : 's' }} asociada{{ $categoria->recetas_count === 1 ? '' : 's' }}
                                </p>
                            </div>
                            <div class="flex flex-wrap items-center gap-2">
                                <button type="button" @click="editing = {{ $categoria->id }}" class="btn-ghost px-3 py-1.5 text-xs">
                                    Editar
                                </button>
                                <button type="button"
                                        @click="$dispatch('open-confirm-modal', {
                                            action: '{{ route('recetas.categorias.destroy', $categoria) }}',
                                            method: 'DELETE',
                                            confirmText: 'Eliminar categoria',
                                            consequences: 'Se quitara esta categoria de {{ $categoria->recetas_count }} receta{{ $categoria->recetas_count === 1 ? '' : 's' }}. Las recetas no se eliminaran.'
                                        })"
                                        aria-label="Eliminar categoria {{ $categoria->nombre }}. Se quitara de {{ $categoria->recetas_count }} receta{{ $categoria->recetas_count === 1 ? '' : 's' }}."
                                        class="btn-ghost px-3 py-1.5 text-xs text-bakery-terracotta hover:text-bakery-terracotta hover:bg-bakery-terracotta/10">
                                    Eliminar
                                </button>
                            </div>
                        </div>

                        <form x-show="editing === {{ $categoria->id }}" x-cloak method="POST" action="{{ route('recetas.categorias.update', $categoria) }}" class="grid gap-3 sm:grid-cols-[1fr_auto_auto] sm:items-start">
                            @csrf
                            @method('PATCH')
                            <div>
                                <label class="sr-only" for="nombre-{{ $categoria->id }}">Nombre</label>
                                <input id="nombre-{{ $categoria->id }}" name="nombre" value="{{ old('nombre', $categoria->nombre) }}" class="form-input py-2.5 text-[13px]" maxlength="60" required>
                            </div>
                            <button type="submit" class="btn-primary px-4 py-2.5 text-[13px]">Guardar</button>
                            <button type="button" @click="editing = null" class="btn-secondary px-4 py-2.5 text-[13px]">Cancelar</button>
                        </form>
                    </div>
                @empty
                    <div class="empty-state">
                        <p class="empty-state-text">No hay categorias registradas</p>
                    </div>
                @endforelse
            </div>
        </div>

        <form method="POST" action="{{ route('recetas.categorias.store') }}" class="form-card h-fit">
            @csrf
            <div class="mb-5">
                <h2 class="section-title">Nueva categoria</h2>
                <p class="section-subtitle">Usa nombres claros para busqueda y filtros.</p>
            </div>
            <div class="space-y-4">
                <div>
                    <label class="form-label form-label-required" for="nombre">Nombre</label>
                    <input id="nombre" name="nombre" value="{{ old('nombre') }}" class="form-input" maxlength="60" placeholder="Ej: Panes" required>
                </div>
                <button type="submit" class="btn-primary w-full">Crear categoria</button>
            </div>
        </form>
    </div>
</div>
</x-app-layout>
