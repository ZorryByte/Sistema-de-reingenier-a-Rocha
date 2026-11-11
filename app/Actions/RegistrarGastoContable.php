<?php

namespace App\Actions;

use App\Models\AsientoContable;
use App\Models\CuentaContable;
use App\Models\Gasto;
use RuntimeException;

class RegistrarGastoContable
{
    public function execute(Gasto $gasto): AsientoContable
    {
        $gasto->loadMissing('categoria.cuenta');
        $cuentaGasto = $gasto->categoria?->cuenta;
        $cuentaPago = CuentaContable::where('codigo', $this->codigoPago($gasto->metodo_pago->value))
            ->where('activo', true)
            ->first();

        if (! $cuentaGasto?->activo || ! $cuentaPago) {
            throw new RuntimeException('Falta configurar una cuenta contable activa.');
        }

        return (new RegistrarAsientoContable)->execute([
            'origen' => 'gasto',
            'referencia' => $gasto,
            'user_id' => $gasto->user_id,
            'fecha_contable' => $gasto->fecha,
            'descripcion' => "Gasto #{$gasto->id}: {$gasto->concepto}",
            'detalles' => [
                [
                    'cuenta_id' => $cuentaGasto->id,
                    'debe' => (float) $gasto->monto,
                    'haber' => 0,
                    'descripcion' => $gasto->categoria->nombre,
                ],
                [
                    'cuenta_id' => $cuentaPago->id,
                    'debe' => 0,
                    'haber' => (float) $gasto->monto,
                    'descripcion' => 'Pago de gasto',
                ],
            ],
        ]);
    }

    private function codigoPago(string $metodo): string
    {
        return $metodo === 'efectivo' ? '1010' : '1020';
    }
}
