<div
    x-data="{
        show: false,
        opener: null,
        action: '',
        method: 'POST',
        confirmText: 'Confirmar',
        consequences: '',
        warning: '',
        openForm(actionUrl, methodType = 'POST', text = 'Confirmar', consequences = '', warning = '') {
            this.opener = document.activeElement;
            this.action = actionUrl;
            this.method = methodType;
            this.confirmText = text;
            this.consequences = consequences;
            this.warning = warning;
            this.show = true;
            this.$nextTick(() => this.$refs.cancelButton?.focus());
        }
    }"
    x-init="$watch('show', value => {
        if (value) {
            document.body.classList.add('overflow-y-hidden');
        } else {
            document.body.classList.remove('overflow-y-hidden');
            this.opener?.focus();
        }
    })"
    x-on:open-confirm-modal.window="openForm($event.detail.action, $event.detail.method, $event.detail.confirmText, $event.detail.consequences, $event.detail.warning || '')"
    x-on:close-confirm-modal.window="show = false"
    x-on:keydown.escape.window="if (show) show = false"
    x-on:keydown.tab.window="if (show) window.rochaTrapTab($event, $refs.confirmPanel)"
    x-show="show"
    class="fixed inset-0 overflow-y-auto px-4 py-6 sm:px-0 z-50"
    style="display: none;"
    x-cloak
>
    {{-- Backdrop --}}
    <div
        x-show="show"
        class="fixed inset-0 bg-black/30 transition-all"
        x-on:click="show = false"
        x-transition:enter="ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
    ></div>

    {{-- Modal panel --}}
    <div
        x-show="show"
        class="mb-6 bg-white rounded-2xl shadow-xl transform transition-all sm:w-full sm:max-w-md sm:mx-auto"
        x-ref="confirmPanel"
        role="dialog"
        aria-modal="true"
        aria-labelledby="confirm-modal-title"
        x-transition:enter="ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
        x-transition:leave="ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
        x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
    >
        <div class="p-6">
            {{-- Icon + Title --}}
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 rounded-full bg-bakery-terracotta/10 flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-bakery-terracotta" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <h3 id="confirm-modal-title" class="text-lg font-display font-bold text-bakery-dark" x-text="confirmText"></h3>
            </div>

            {{-- Consequences --}}
            <p class="text-sm text-bakery-muted mb-4" x-text="consequences" x-show="consequences"></p>

            {{-- Warning --}}
            <div x-show="warning" class="mb-4 bg-bakery-amber/10 border border-bakery-amber/30 rounded-xl px-4 py-3 flex items-start gap-2">
                <svg class="w-4 h-4 text-bakery-amber flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <p class="text-sm text-bakery-brown" x-text="warning"></p>
            </div>

            {{-- Form --}}
            <form :action="action" :method="method === 'GET' ? 'GET' : 'POST'">
                @csrf
                <template x-if="method === 'DELETE'">
                    <input type="hidden" name="_method" value="DELETE">
                </template>
                <template x-if="method === 'PATCH'">
                    <input type="hidden" name="_method" value="PATCH">
                </template>
                <div class="flex gap-2 justify-end">
                    <button type="button" x-ref="cancelButton" @click="show = false" class="btn-secondary text-sm">
                        Cancelar
                    </button>
                    <button type="submit" class="bg-bakery-terracotta hover:bg-bakery-terracotta/80 text-white text-sm font-medium px-4 py-2 rounded-xl transition">
                        <span x-text="confirmText"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
