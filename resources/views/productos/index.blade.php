<x-app-layout>
<div class="py-8 px-6" x-data="productosIndex()" x-cloak>

    <div class="max-w-6xl mx-auto">
    <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 mb-6">
        <div>
            <h2 class="text-2xl font-bold text-bakery-dark">Productos</h2>
            <p class="text-sm text-bakery-muted mt-1">Administra los productos del cat&aacute;logo.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('recetas.index') }}" class="btn-secondary">Ver recetas</a>
            <a href="{{ route('productos.create') }}" class="btn-primary">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                </svg>
                Nuevo producto
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-4 flash-success text-sm rounded-xl px-4 py-3">
            {{ session('success') }}
        </div>
    @endif

    <div class="bg-white rounded-2xl shadow-sm border border-bakery-amber/10 overflow-hidden">

        {{-- Filtros --}}
        <div class="p-4 border-b border-bakery-amber/10 space-y-3">
            <div class="relative">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-bakery-muted/50" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input type="text" x-model.debounce.300ms="busqueda"
                       class="form-input pl-10 py-2.5 text-sm"
                       placeholder="Buscar producto...">
            </div>
            <div class="flex flex-wrap gap-2">
                <button type="button" @click="statusFilter = 'all'"
                        :class="statusFilter === 'all'
                            ? 'bg-bakery-gold/15 border-bakery-gold/40 text-bakery-brown'
                            : 'bg-white border-bakery-amber/20 text-bakery-muted hover:bg-bakery-warm'"
                        class="px-3 py-1.5 text-xs font-medium rounded-full border transition duration-150">
                    Todos
                </button>
                <button type="button" @click="statusFilter = 'active'"
                        :class="statusFilter === 'active'
                            ? 'bg-bakery-gold/15 border-bakery-gold/40 text-bakery-brown'
                            : 'bg-white border-bakery-amber/20 text-bakery-muted hover:bg-bakery-warm'"
                        class="px-3 py-1.5 text-xs font-medium rounded-full border transition duration-150">
                    Activos
                </button>
                <button type="button" @click="statusFilter = 'inactive'"
                        :class="statusFilter === 'inactive'
                            ? 'bg-bakery-gold/15 border-bakery-gold/40 text-bakery-brown'
                            : 'bg-white border-bakery-amber/20 text-bakery-muted hover:bg-bakery-warm'"
                        class="px-3 py-1.5 text-xs font-medium rounded-full border transition duration-150">
                    Inactivos
                </button>
            </div>
        </div>

        {{-- Lista --}}
        <div class="divide-y divide-bakery-amber/5">
            <template x-for="producto in productosFiltrados" :key="producto.id">
                <div>
                    <button type="button" @click="toggleProduct(producto.id)"
                            class="w-full flex items-center justify-between px-4 py-3
                                   hover:bg-bakery-warm transition duration-150 text-left">
                        <div class="flex items-center gap-3 min-w-0">
                            <svg class="w-4 h-4 text-bakery-muted/40 transition-transform duration-150 flex-shrink-0"
                                 :class="expandedId === producto.id ? 'rotate-90' : ''"
                                 fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                            </svg>
                            <span class="min-w-0">
                                <span class="block text-sm font-semibold text-bakery-dark truncate" x-text="producto.nombre"></span>
                                <span class="mt-0.5 block text-xs text-bakery-muted/70" x-text="producto.receta_default ? 'Receta: ' + producto.receta_default.nombre : 'Sin receta general'"></span>
                            </span>
                        </div>
                        <div class="flex items-center gap-3 flex-shrink-0">
                            <span class="hidden sm:inline-flex text-xs font-medium px-2 py-0.5 rounded-full border"
                                  :class="producto.receta_default
                                      ? 'bg-bakery-sage/10 text-bakery-sage border-bakery-sage/30'
                                      : 'bg-bakery-terracotta/10 text-bakery-terracotta border-bakery-terracotta/30'"
                                  x-text="producto.receta_default ? 'Con receta' : 'Sin receta'"></span>
                            <span class="text-xs text-bakery-muted/50" x-text="formatUnit(producto.tipo_unidad)"></span>
                            <span class="text-xs font-medium px-2 py-0.5 rounded-full"
                                  :class="producto.activo
                                      ? 'bg-bakery-sage/10 text-bakery-sage border border-bakery-sage/30'
                                      : 'bg-bakery-warm text-bakery-muted border border-bakery-amber/20'"
                                  x-text="producto.activo ? 'Activo' : 'Inactivo'"></span>
                        </div>
                    </button>

                    <div x-show="expandedId === producto.id"
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 -translate-y-1"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         x-transition:leave="transition ease-in duration-150"
                         x-transition:leave-start="opacity-100 translate-y-0"
                         x-transition:leave-end="opacity-0 -translate-y-1"
                         class="px-4 pb-4 bg-bakery-warm/50">
                        <div class="ml-7">
                            <p class="text-xs font-semibold text-bakery-muted uppercase tracking-widest mb-2">Presentaciones</p>
                            <div class="bg-white rounded-xl border border-bakery-amber/10 overflow-hidden">
                                <div class="overflow-x-auto">
                                <table class="w-full text-xs">
                                    <thead class="bg-bakery-warm border-b border-bakery-amber/10">
                                        <tr class="text-bakery-muted">
                                            <th class="px-3 py-2 text-left font-medium">Nombre</th>
                                            <th class="px-3 py-2 text-left font-medium">Cantidad</th>
                                            <th class="px-3 py-2 text-left font-medium">Precio</th>
                                            <th class="px-3 py-2 text-left font-medium">Stock</th>
                                            <th class="px-3 py-2 text-left font-medium">Receta</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <template x-for="pres in producto.presentaciones_activas" :key="pres.id">
                                            <tr class="border-b border-bakery-amber/5 last:border-0 hover:bg-bakery-warm/50">
                                                <td class="px-3 py-2 text-bakery-dark" x-text="pres.nombre"></td>
                                                <td class="px-3 py-2 text-bakery-muted" x-text="parseFloat(pres.cantidad_unidades).toFixed(2).replace(/\.?0+$/, '')"></td>
                                                <td class="px-3 py-2 font-medium text-bakery-gold" x-text="formatMoney(pres.precio)"></td>
                                                <td class="px-3 py-2">
                                                    <span :class="pres.stock_actual <= pres.stock_minimo && pres.stock_minimo > 0
                                                        ? 'text-bakery-terracotta font-medium'
                                                        : 'text-bakery-muted'"
                                                          x-text="pres.stock_actual + ' / ' + pres.stock_minimo">
                                                    </span>
                                                </td>
                                                <td class="px-3 py-2">
                                                    <template x-if="pres.receta_default">
                                                        <a :href="pres.receta_default.edit_url"
                                                           class="text-bakery-gold hover:text-bakery-brown font-medium">
                                                            Editar
                                                        </a>
                                                    </template>
                                                    <template x-if="!pres.receta_default">
                                                        <a :href="'{{ route('recetas.create') }}?producto_id=' + producto.id"
                                                           class="text-bakery-muted/50 hover:text-bakery-gold">
                                                            Usar general
                                                        </a>
                                                    </template>
                                                </td>
                                            </tr>
                                        </template>
                                        <template x-if="producto.presentaciones_activas.length === 0">
                                            <tr>
                                                <td colspan="5" class="px-3 py-4 text-center text-bakery-muted/50">
                                                    Sin presentaciones
                                                </td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                                </div>
                            </div>

                            <div class="flex items-center gap-3 mt-3">
                                <a :href="producto.show_url"
                                   class="text-xs font-medium text-bakery-muted hover:text-bakery-dark hover:underline">
                                    Ver detalle
                                </a>
                                <a :href="producto.edit_url"
                                   class="text-xs font-medium text-bakery-gold hover:underline">
                                    Editar producto
                                </a>
                                <template x-if="producto.receta_default">
                                    <a :href="producto.receta_default.edit_url"
                                       class="text-xs font-medium text-bakery-gold hover:underline">
                                        Editar receta
                                    </a>
                                </template>
                                <form :action="producto.destroy_url" method="POST"
                                      x-data>
                                    @csrf @method('DELETE')
<button type="button"
                                        @click="$dispatch('open-confirm-modal', {action: producto.destroy_url, method: 'DELETE', confirmText: 'Desactivar', consequences: 'El producto dejar&aacute; de estar disponible. Las ventas activas no se ver&aacute;n afectadas.'})"
                                        aria-label="Desactivar producto"
                                        class="text-xs font-medium text-bakery-terracotta hover:underline">
                                        Desactivar
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </template>

            <template x-if="productosFiltrados.length === 0">
                <div class="empty-state">
                    <svg class="empty-state-icon" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                    </svg>
                    <p class="empty-state-text">No se encontraron productos</p>
                </div>
            </template>
        </div>
    </div>
    </div>
</div>

<script>
const productosData = @json($productos);

function productosIndex() {
    return {
        busqueda: '',
        expandedId: null,
        statusFilter: 'all',
        productos: productosData,

        toggleProduct(id) {
            this.expandedId = this.expandedId === id ? null : id;
        },

        get productosFiltrados() {
            let resultado = this.productos;
            if (this.statusFilter === 'active') {
                resultado = resultado.filter(p => p.activo);
            } else if (this.statusFilter === 'inactive') {
                resultado = resultado.filter(p => !p.activo);
            }
            const q = this.busqueda.toLowerCase().trim();
            if (q) {
                resultado = resultado.filter(p => p.nombre.toLowerCase().includes(q));
            }
            return resultado;
        },

        formatMoney(value) {
            return window.RochaFormat.money(Number(value || 0));
        },

        formatUnit(value) {
            return value === 'unidad' ? 'und.' : value;
        },
    }
}
</script>

<style>
[x-cloak] { display: none !important; }
</style>
</x-app-layout>
