<?php

namespace App\Actions;

use App\Enums\GastoEstado;
use App\Models\AsientoContable;
use App\Models\Gasto;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AnularGasto
{
    public function execute(Gasto $gasto, string $motivo, int $userId): Gasto
    {
        return DB::transaction(function () use ($gasto, $motivo, $userId) {
            $gasto = Gasto::lockForUpdate()->findOrFail($gasto->id);

            if ($gasto->estado !== GastoEstado::Registrado) {
                throw ValidationException::withMessages(['gasto' => 'El gasto ya fue anulado.']);
            }

            $asiento = AsientoContable::where('origen', 'gasto')
                ->where('referencia_type', Gasto::class)
                ->where('referencia_id', $gasto->id)
                ->lockForUpdate()
                ->firstOrFail();

            (new RegistrarReversionContable)->execute($asiento, $gasto, $userId);

            Gasto::withoutEvents(function () use ($gasto, $motivo, $userId) {
                $gasto->forceFill([
                    'estado' => GastoEstado::Anulado->value,
                    'anulado_at' => now(),
                    'anulado_por' => $userId,
                    'motivo_anulacion' => $motivo,
                ])->save();
            });

            return $gasto->fresh(['asientos.detalles.cuenta', 'usuarioAnulacion']);
        });
    }
}
