@php
    $cuentaSeleccionada = old('cuenta_id', $cuenta?->id);
    $importe = fn (int $centavos) => \App\Support\DisplayNumber::money(\App\Support\Money::decimalFromCents($centavos));
    $naturaleza = fn (int $saldo) => match (true) {
        $saldo > 0 => 'Deudor',
        $saldo < 0 => 'Acreedor',
        default => 'Cero',
    };
@endphp

<x-app-layout>
    <div class="finance-page">
        <div class="finance-stack">
            <header class="finance-header">
                <div>
                    <h2 class="finance-title">Libro mayor</h2>
                    <p class="finance-description">Consulta los movimientos y saldos de una cuenta contable.</p>
                </div>
                <div class="finance-actions">
                    <a href="{{ route('finanzas.libro-contable.index') }}" class="btn-secondary">Libro diario</a>
                </div>
            </header>

            <form method="GET" action="{{ route('finanzas.libro-mayor.index') }}" class="finance-filter">
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    <div>
                        <label for="cuenta_id" class="form-label">Cuenta contable</label>
                        <select id="cuenta_id" name="cuenta_id" class="form-select" required @error('cuenta_id') aria-invalid="true" aria-describedby="cuenta_id-error" @enderror>
                            <option value="">Selecciona una cuenta</option>
                            @foreach ($cuentas as $opcion)
                                <option value="{{ $opcion->id }}" @selected(is_scalar($cuentaSeleccionada) && $opcion->id == $cuentaSeleccionada)>
                                    {{ $opcion->codigo }} · {{ $opcion->nombre }}{{ $opcion->activo ? '' : ' (inactiva)' }}
                                </option>
                            @endforeach
                        </select>
                        @error('cuenta_id') <p id="cuenta_id-error" class="form-error">{{ $message }}</p> @enderror
                    </div>
                    @foreach (['desde' => 'Desde', 'hasta' => 'Hasta'] as $campo => $etiqueta)
                        <div>
                            <label for="{{ $campo }}" class="form-label">{{ $etiqueta }}</label>
                            <input id="{{ $campo }}" name="{{ $campo }}" type="date" class="form-input" value="{{ is_string(old($campo)) ? old($campo) : $filtros[$campo] }}" required @error($campo) aria-invalid="true" aria-describedby="{{ $campo }}-error" @enderror>
                            @error($campo) <p id="{{ $campo }}-error" class="form-error">{{ $message }}</p> @enderror
                        </div>
                    @endforeach
                    <div class="flex items-end">
                        <button type="submit" class="btn-primary">Consultar</button>
                    </div>
                </div>
                @error('page') <p class="form-error">La página solicitada no es válida.</p> @enderror
            </form>

            @if ($mayor)
                <dl class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4" aria-label="Saldos y totales del período">
                    @foreach (['saldoInicial' => 'Saldo inicial', 'debe' => 'Debe del período', 'haber' => 'Haber del período', 'saldoFinal' => 'Saldo final'] as $clave => $etiqueta)
                        <div class="finance-panel finance-panel-pad">
                            <dt class="finance-section-note">{{ $etiqueta }}</dt>
                            <dd class="mt-1 text-2xl font-bold tabular-nums text-bakery-dark">
                                {{ $importe(abs($mayor[$clave])) }}
                                @if ($clave === 'saldoInicial' || $clave === 'saldoFinal')
                                    <span class="text-sm font-medium text-bakery-muted">{{ $naturaleza($mayor[$clave]) }}</span>
                                @endif
                            </dd>
                        </div>
                    @endforeach
                </dl>

                <section class="finance-table-wrap" aria-labelledby="movimientos-mayor">
                    <div class="px-4 py-4">
                        <h3 id="movimientos-mayor" class="finance-section-title">{{ $cuenta->codigo }} · {{ $cuenta->nombre }}</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm" aria-labelledby="movimientos-mayor">
                            <thead class="bg-bakery-warm/70 text-left text-xs font-semibold text-bakery-muted">
                                <tr>
                                    <th scope="col" class="px-4 py-3">Fecha contable</th>
                                    <th scope="col" class="px-4 py-3">Descripción</th>
                                    <th scope="col" class="px-4 py-3">Asiento</th>
                                    <th scope="col" class="px-4 py-3 text-right">Debe</th>
                                    <th scope="col" class="px-4 py-3 text-right">Haber</th>
                                    <th scope="col" class="px-4 py-3 text-right">Saldo acumulado</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-bakery-amber/10">
                                @forelse ($movimientos as ['detalle' => $detalle, 'saldo' => $saldo])
                                    <tr class="hover:bg-bakery-warm/55">
                                        <td class="whitespace-nowrap px-4 py-3 tabular-nums">{{ $detalle->fecha_contable->format('d/m/Y H:i') }}</td>
                                        <td class="px-4 py-3">{{ $detalle->descripcion_asiento ?? $detalle->descripcion ?? '—' }}</td>
                                        <td class="px-4 py-3">
                                            <a class="font-semibold text-bakery-brown underline" href="{{ route('finanzas.libro-contable.show', $detalle->asiento_contable_id) }}">
                                                <span class="sr-only">Ver asiento </span>#{{ $detalle->asiento_contable_id }}
                                            </a>
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-3 text-right tabular-nums">{{ \App\Support\DisplayNumber::money($detalle->debe) }}</td>
                                        <td class="whitespace-nowrap px-4 py-3 text-right tabular-nums">{{ \App\Support\DisplayNumber::money($detalle->haber) }}</td>
                                        <td class="whitespace-nowrap px-4 py-3 text-right font-semibold tabular-nums">
                                            {{ $importe(abs($saldo)) }} <span class="font-normal text-bakery-muted">{{ $naturaleza($saldo) }}</span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-4 py-12 text-center text-bakery-muted">
                                            {{ $movimientos->total() ? 'Esta página no contiene movimientos.' : 'Esta cuenta no tuvo movimientos en el período.' }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    {{ $movimientos->links('vendor.pagination.bakery') }}
                </section>
            @else
                <p class="finance-panel finance-panel-pad text-bakery-muted">Selecciona una cuenta y un período para consultar el mayor.</p>
            @endif
        </div>
    </div>
</x-app-layout>
