<x-app-layout>
<main class="finance-page gastos-page">
    <div class="mx-auto w-full max-w-5xl px-4 sm:px-6">
        <a href="{{ route('finanzas.gastos.index') }}" class="finance-icon-label text-sm font-semibold text-bakery-muted transition hover:text-bakery-brown"><x-finanzas.icon name="arrow-left" />Volver a gastos</a>

        <header class="mt-5 flex flex-col gap-4 border-b border-bakery-amber/15 pb-5 sm:flex-row sm:items-end sm:justify-between">
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                    <h2 class="finance-title finance-icon-label truncate"><x-finanzas.icon name="receipt" />{{ $gasto->concepto }}</h2>
                    <span class="rounded-full px-2 py-1 text-xs font-semibold {{ $gasto->estado->value === 'anulado' ? 'finance-status-muted' : 'finance-status-success' }}">{{ $gasto->estado->label() }}</span>
                </div>
                <p class="finance-description">Gasto #{{ $gasto->id }} · registrado {{ $gasto->created_at->format('d/m/Y H:i') }}</p>
            </div>
            <p class="text-3xl font-bold tabular-nums {{ $gasto->estado->value === 'anulado' ? 'text-bakery-muted line-through' : 'text-bakery-terracotta' }}">{{ \App\Support\DisplayNumber::money($gasto->monto) }}</p>
        </header>

        @if(session('success'))<div class="flash-success mt-5 rounded-lg px-4 py-3 text-sm" role="status">{{ session('success') }}</div>@endif

        <div class="mt-7 grid grid-cols-1 gap-7 lg:grid-cols-[minmax(0,1fr)_20rem]">
            <div class="space-y-7">
                <section aria-labelledby="detalle-gasto">
                    <h3 id="detalle-gasto" class="finance-section-title finance-icon-label"><x-finanzas.icon name="list" />Detalle</h3>
                    <dl class="mt-3 grid grid-cols-1 border-y border-bakery-amber/10 sm:grid-cols-2">
                        @foreach([
                            ['Fecha de pago', $gasto->fecha->format('d/m/Y')],
                            ['Categoría', $gasto->categoria->nombre],
                            ['Tipo', $gasto->categoria->tipo->label()],
                            ['Método', $gasto->metodo_pago->label()],
                            ['Proveedor', $gasto->proveedor?->nombre ?? 'Sin proveedor'],
                            ['Documento', $gasto->numero_documento ?: 'Sin documento'],
                            ['Registrado por', $gasto->user?->name ?? 'Usuario no disponible'],
                            ['Cuenta', $gasto->categoria->cuenta->codigo.' · '.$gasto->categoria->cuenta->nombre],
                        ] as [$label, $value])
                            <div class="border-b border-bakery-amber/10 py-3 sm:odd:pr-5 sm:even:pl-5"><dt class="text-xs font-semibold text-bakery-muted">{{ $label }}</dt><dd class="mt-1 text-sm font-medium text-bakery-dark">{{ $value }}</dd></div>
                        @endforeach
                    </dl>
                    @if($gasto->monto_estimado_recordatorio)
                        <div class="mt-4 rounded-lg border border-bakery-amber/15 bg-bakery-warm px-4 py-3 text-sm text-bakery-dark">
                            Estimado: <strong class="tabular-nums">{{ \App\Support\DisplayNumber::money($gasto->monto_estimado_recordatorio) }}</strong>
                        </div>
                    @endif
                    @if($gasto->nota)<div class="mt-4"><p class="text-xs font-semibold text-bakery-muted">Nota</p><p class="mt-1 max-w-3xl whitespace-pre-line text-sm leading-relaxed text-bakery-dark">{{ $gasto->nota }}</p></div>@endif
                </section>

                <section class="border-t border-bakery-amber/15 pt-6" aria-labelledby="asientos-gasto">
                    <h3 id="asientos-gasto" class="finance-section-title finance-icon-label"><x-finanzas.icon name="refresh" />Trazabilidad contable</h3>
                    <div class="mt-3 space-y-3">
                        @foreach($gasto->asientos->sortBy('created_at') as $asiento)
                            <a href="{{ route('finanzas.libro-contable.show', $asiento) }}" class="block rounded-lg border border-bakery-amber/15 px-4 py-3 transition hover:border-bakery-amber/35 hover:bg-bakery-warm/50">
                                <div class="flex items-start justify-between gap-4"><div><p class="text-sm font-semibold text-bakery-dark">{{ $asiento->origen === 'gasto' ? 'Registro del gasto' : 'Reversión del gasto' }}</p><p class="mt-1 text-xs text-bakery-muted">Asiento #{{ $asiento->id }} · {{ $asiento->fecha_contable->format('d/m/Y H:i') }}</p></div><span class="text-xs font-semibold text-bakery-brown">Ver asiento</span></div>
                            </a>
                        @endforeach
                    </div>
                </section>
            </div>

            <aside class="space-y-5">
                <section class="finance-panel finance-panel-pad">
                    <h3 class="finance-section-title finance-icon-label"><x-finanzas.icon name="file" />Comprobante</h3>
                    @if($gasto->comprobante_path)
                        <p class="mt-2 break-words text-sm font-medium text-bakery-dark">{{ $gasto->comprobante_nombre }}</p>
                        <p class="mt-1 text-xs text-bakery-muted">{{ number_format($gasto->comprobante_tamano / 1024, 1) }} KB · privado</p>
                        <a href="{{ route('finanzas.gastos.comprobante', $gasto) }}" class="btn-secondary mt-4 w-full text-center"><x-finanzas.icon name="file" />Descargar</a>
                    @else
                        <p class="mt-2 text-sm text-bakery-muted">Sin archivo adjunto.</p>
                    @endif
                </section>

                @if($gasto->estado->value === 'registrado')
                    <section class="rounded-lg border p-4 finance-status-danger" x-data="{ confirmando: false }">
                        <h3 class="finance-icon-label text-sm font-bold text-bakery-dark"><x-finanzas.icon name="warning" />Anular gasto</h3>
                        <p class="mt-1 text-xs leading-relaxed text-bakery-muted">Conserva el registro y crea una reversión.</p>
                        <form method="POST" action="{{ route('finanzas.gastos.anular', $gasto) }}" class="mt-4" @submit.prevent="confirmando ? $el.submit() : confirmando = true">
                            @csrf @method('PATCH')
                            <label for="motivo" class="form-label form-label-required">Motivo</label>
                            <textarea id="motivo" name="motivo" rows="3" maxlength="300" class="form-textarea" required @error('motivo') aria-invalid="true" aria-describedby="motivo-error" @enderror>{{ old('motivo') }}</textarea>
                            @error('motivo') <p id="motivo-error" class="form-error">{{ $message }}</p> @enderror
                            <div x-show="confirmando" x-cloak class="mt-3 rounded-lg border border-bakery-terracotta/25 bg-bakery-terracotta/10 px-3 py-2 text-xs text-bakery-terracotta" role="alert" aria-live="polite">
                                Confirma la anulación. El motivo escrito se conservará.
                            </div>
                            <div class="mt-3 grid gap-2">
                                <button type="submit" class="w-full rounded-lg border border-bakery-terracotta/30 px-4 py-2 text-sm font-semibold text-bakery-terracotta transition hover:bg-bakery-terracotta/10">
                                    <span x-text="confirmando ? 'Confirmar anulación' : 'Anular y revertir'">Anular y revertir</span>
                                </button>
                                <button type="button" x-show="confirmando" x-cloak @click="confirmando = false" class="btn-ghost w-full">Volver al motivo</button>
                            </div>
                        </form>
                    </section>
                @else
                    <section class="finance-panel finance-panel-pad">
                        <h3 class="finance-section-title finance-icon-label"><x-finanzas.icon name="warning" />Anulación</h3>
                        <p class="mt-2 text-sm text-bakery-dark">{{ $gasto->motivo_anulacion }}</p>
                        <p class="mt-2 text-xs text-bakery-muted">{{ $gasto->anulado_at?->format('d/m/Y H:i') }} · {{ $gasto->usuarioAnulacion->name }}</p>
                    </section>
                @endif
            </aside>
        </div>
    </div>
</main>
</x-app-layout>
