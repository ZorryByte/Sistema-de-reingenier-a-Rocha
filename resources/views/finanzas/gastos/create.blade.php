<x-app-layout>
<main class="finance-page gastos-page">
    <div class="gastos-form-shell">
        <a href="{{ route('finanzas.gastos.index') }}" class="finance-icon-label text-sm font-semibold text-bakery-muted transition hover:text-bakery-brown"><x-finanzas.icon name="arrow-left" />Volver a gastos</a>

        <header class="mt-5 border-b border-bakery-amber/15 pb-5">
            <h2 class="finance-title finance-icon-label"><x-finanzas.icon name="receipt" />Registrar gasto</h2>
            <p class="finance-description">Solo pagos externos.</p>
        </header>

        @if(session('success'))
            <div class="flash-success mt-5 rounded-lg px-4 py-3 text-sm" role="status">{{ session('success') }}</div>
        @endif

        @if($recordatorio)
            <div class="gastos-section-strong mt-5 text-sm text-bakery-dark">
                <p class="finance-icon-label font-semibold"><x-finanzas.icon name="bell" />{{ $recordatorio->concepto }}</p>
                <p class="mt-1 text-xs text-bakery-muted">Categoría y proveedor vienen del recordatorio.</p>
            </div>
        @endif

        @if($errors->any())
            <div class="mt-5 rounded-lg border border-bakery-terracotta/25 bg-bakery-terracotta/10 px-4 py-3 text-sm text-bakery-terracotta" role="alert">
                Revisa los campos indicados.
            </div>
        @endif

        <form method="POST" action="{{ route('finanzas.gastos.store') }}" enctype="multipart/form-data" class="mt-7 space-y-6">
            @csrf
            <input type="hidden" name="token_registro" value="{{ $tokenRegistro }}">
            <input type="hidden" name="recordatorio_gasto_id" value="{{ old('recordatorio_gasto_id', $recordatorio?->id) }}">
            <input type="hidden" name="periodo_recordatorio" value="{{ old('periodo_recordatorio', $recordatorio?->proxima_fecha?->format('Y-m-d')) }}">
            @if($recordatorio)
                <input type="hidden" name="categoria_id" value="{{ $recordatorio->categoria_id }}">
                <input type="hidden" name="proveedor_id" value="{{ $recordatorio->proveedor_id }}">
            @endif

            <section class="gastos-section" aria-labelledby="gasto-datos">
                <h3 id="gasto-datos" class="finance-section-title finance-icon-label"><x-finanzas.icon name="receipt" />Pago</h3>
                <div class="mt-4 grid grid-cols-1 gap-5 sm:grid-cols-2">
                    @if($recordatorio)
                        <div>
                            <p class="form-label">Categoría</p>
                            <p class="finance-soft-field">{{ $recordatorio->categoria->nombre }} · {{ $recordatorio->categoria->tipo->label() }}</p>
                            @error('categoria_id') <p id="categoria_id-error" class="form-error">{{ $message }}</p> @enderror
                        </div>
                    @else
                        <div>
                            <label for="categoria_id" class="form-label form-label-required">Categoría</label>
                            <select id="categoria_id" name="categoria_id" class="form-select" required @error('categoria_id') aria-invalid="true" aria-describedby="categoria_id-error" @enderror>
                                <option value="">Seleccionar</option>
                                @foreach($categorias as $categoria)
                                    <option value="{{ $categoria->id }}" @selected(old('categoria_id') == $categoria->id)>{{ $categoria->nombre }} · {{ $categoria->tipo->label() }}</option>
                                @endforeach
                            </select>
                            @error('categoria_id') <p id="categoria_id-error" class="form-error">{{ $message }}</p> @enderror
                        </div>
                    @endif

                    <div>
                        <label for="fecha" class="form-label form-label-required">Fecha</label>
                        <input id="fecha" type="date" name="fecha" value="{{ old('fecha', now()->format('Y-m-d')) }}" max="{{ now()->format('Y-m-d') }}" class="form-input" required @error('fecha') aria-invalid="true" aria-describedby="fecha-error" @enderror>
                        @error('fecha') <p id="fecha-error" class="form-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="sm:col-span-2">
                        <label for="concepto" class="form-label form-label-required">Concepto</label>
                        <input id="concepto" name="concepto" type="text" value="{{ old('concepto', $recordatorio?->concepto) }}" maxlength="160" class="form-input" placeholder="Ej. Electricidad de junio" required @error('concepto') aria-invalid="true" aria-describedby="concepto-error" @enderror>
                        @error('concepto') <p id="concepto-error" class="form-error">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="monto" class="form-label form-label-required">Monto</label>
                        <input id="monto" name="monto" type="number" step="0.01" min="0.01" max="999999.99" value="{{ old('monto', $recordatorio?->monto_estimado) }}" class="form-input tabular-nums" placeholder="0.00" required @error('monto') aria-invalid="true" aria-describedby="monto-error" @enderror>
                        @error('monto') <p id="monto-error" class="form-error">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="metodo_pago" class="form-label form-label-required">Método</label>
                        <select id="metodo_pago" name="metodo_pago" class="form-select" required @error('metodo_pago') aria-invalid="true" aria-describedby="metodo_pago-error" @enderror>
                            <option value="">Seleccionar</option>
                            @foreach($metodosPago as $metodo)
                                <option value="{{ $metodo->value }}" @selected(old('metodo_pago', $recordatorio?->metodo_pago?->value) === $metodo->value)>{{ $metodo->label() }}</option>
                            @endforeach
                        </select>
                        @error('metodo_pago') <p id="metodo_pago-error" class="form-error">{{ $message }}</p> @enderror
                    </div>
                </div>
            </section>

            <section class="gastos-section" aria-labelledby="gasto-trazabilidad">
                <h3 id="gasto-trazabilidad" class="finance-section-title finance-icon-label"><x-finanzas.icon name="file" />Soporte</h3>

                <div class="mt-4 space-y-5">
                    @if($recordatorio)
                        <div>
                            <p class="form-label">Proveedor</p>
                            <p class="finance-soft-field">{{ $recordatorio->proveedor?->nombre ?? 'Sin proveedor' }}</p>
                            @error('proveedor_id') <p id="proveedor_id-error" class="form-error">{{ $message }}</p> @enderror
                        </div>
                    @else
                        <x-proveedor-combobox
                            :selected="$proveedorSeleccionado"
                            contexto="gasto_create"
                            draft-key="gasto-create-draft"
                            label="Proveedor"
                            hint="Opcional. Ayuda a comparar gastos."
                        />
                    @endif

                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <div>
                            <label for="numero_documento" class="form-label">Documento</label>
                            <input id="numero_documento" name="numero_documento" type="text" value="{{ old('numero_documento') }}" maxlength="100" class="form-input" placeholder="Factura o recibo" @error('numero_documento') aria-invalid="true" aria-describedby="numero_documento-error" @enderror>
                            @error('numero_documento') <p id="numero_documento-error" class="form-error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="comprobante" class="form-label">Comprobante</label>
                            <input id="comprobante" name="comprobante" type="file" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png" class="form-input file:mr-3 file:rounded-md file:border-0 file:bg-bakery-warm file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-bakery-brown" @error('comprobante') aria-invalid="true" aria-describedby="comprobante-error comprobante-hint" @else aria-describedby="comprobante-hint" @enderror>
                            <p id="comprobante-hint" class="mt-1 text-xs text-bakery-muted">PDF, JPG o PNG. Máximo 5 MB.</p>
                            @error('comprobante') <p id="comprobante-error" class="form-error">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div>
                        <label for="nota" class="form-label">Nota</label>
                        <textarea id="nota" name="nota" rows="3" maxlength="500" class="form-textarea" placeholder="Detalle breve" @error('nota') aria-invalid="true" aria-describedby="nota-error" @enderror>{{ old('nota') }}</textarea>
                        @error('nota') <p id="nota-error" class="form-error">{{ $message }}</p> @enderror
                    </div>
                </div>
            </section>

            <div class="flex flex-col-reverse gap-3 border-t border-bakery-amber/15 pt-5 sm:flex-row sm:justify-end">
                <a href="{{ route('finanzas.gastos.index') }}" class="btn-ghost text-center">Cancelar</a>
                <button type="submit" class="btn-primary"><x-finanzas.icon name="check" />Registrar gasto</button>
            </div>
        </form>
    </div>
</main>
</x-app-layout>
