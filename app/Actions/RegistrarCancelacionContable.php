<?php

namespace App\Actions;

use App\Models\AsientoContable;
use App\Models\User;
use App\Models\Venta;

class RegistrarCancelacionContable
{
    public function execute(Venta $venta, User $user): ?AsientoContable
    {
        if (AsientoContable::where('origen', 'cancelacion_venta')
            ->where('referencia_type', Venta::class)
            ->where('referencia_id', $venta->id)
            ->exists()) {
            return null;
        }

        $venta->loadMissing('pagos');
        $asientoVenta = AsientoContable::where('origen', 'venta')
            ->where('referencia_type', Venta::class)
            ->where('referencia_id', $venta->id)
            ->first();

        $detalles = [[
            'codigo' => '4010',
            'nombre' => 'Ventas',
            'tipo' => 'ingreso',
            'debe' => (float) $venta->total,
            'haber' => 0,
            'descripcion' => 'Reversión de venta',
        ]];

        $pendiente = (float) $venta->total;
        foreach ($venta->pagos as $pago) {
            if ($pendiente <= 0) {
                break;
            }

            $monto = min((float) $pago->monto, $pendiente);
            $pendiente = round($pendiente - $monto, 2);

            $detalles[] = [
                ...$this->cuentaCobro($pago->metodo),
                'debe' => 0,
                'haber' => $monto,
                'descripcion' => 'Reversión de cobro',
            ];
        }

        return (new RegistrarAsientoContable)->execute([
            'origen' => 'cancelacion_venta',
            'referencia' => $venta,
            'user_id' => $user->id,
            'fecha_contable' => now(),
            'asiento_reversado_id' => $asientoVenta?->id,
            'descripcion' => "Cancelación Venta #{$venta->id}",
            'detalles' => $detalles,
        ]);
    }

    private function cuentaCobro(string $metodo): array
    {
        return match ($metodo) {
            'efectivo' => ['codigo' => '1010', 'nombre' => 'Caja', 'tipo' => 'activo'],
            'tarjeta', 'transferencia', 'cheque' => ['codigo' => '1020', 'nombre' => 'Bancos', 'tipo' => 'activo'],
            default => ['codigo' => '1090', 'nombre' => 'Otros medios de cobro', 'tipo' => 'activo'],
        };
    }
}
