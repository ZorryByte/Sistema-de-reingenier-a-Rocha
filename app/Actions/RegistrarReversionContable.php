<?php

namespace App\Actions;

use App\Models\AsientoContable;
use App\Models\Gasto;

class RegistrarReversionContable
{
    public function execute(AsientoContable $original, Gasto $gasto, int $userId): AsientoContable
    {
        $original->loadMissing('detalles');

        return (new RegistrarAsientoContable)->execute([
            'origen' => 'anulacion_gasto',
            'referencia' => $gasto,
            'user_id' => $userId,
            'fecha_contable' => now(),
            'asiento_reversado_id' => $original->id,
            'descripcion' => "Anulación Gasto #{$gasto->id}",
            'detalles' => $original->detalles->map(fn ($detalle) => [
                'cuenta_id' => $detalle->cuenta_contable_id,
                'debe' => (float) $detalle->haber,
                'haber' => (float) $detalle->debe,
                'descripcion' => 'Reversión de gasto',
            ])->all(),
        ]);
    }
}
