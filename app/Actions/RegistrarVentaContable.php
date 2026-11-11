<?php

namespace App\Actions;

use App\Models\AsientoContable;
use App\Models\Venta;

class RegistrarVentaContable
{
    public function execute(Venta $venta): ?AsientoContable
    {
        if ($this->existe($venta, 'venta')) {
            return null;
        }

        $venta->loadMissing('pagos');
        $detalles = $this->lineasCobro($venta);

        $detalles[] = [
            'codigo' => '4010',
            'nombre' => 'Ventas',
            'tipo' => 'ingreso',
            'debe' => 0,
            'haber' => (float) $venta->total,
            'descripcion' => 'Ingreso por venta',
        ];

        return (new RegistrarAsientoContable)->execute([
            'origen' => 'venta',
            'referencia' => $venta,
            'user_id' => $venta->user_id,
            'fecha_contable' => $venta->created_at ?? now(),
            'descripcion' => "Venta #{$venta->id}",
            'detalles' => $detalles,
        ]);
    }

    private function existe(Venta $venta, string $origen): bool
    {
        return AsientoContable::where('origen', $origen)
            ->where('referencia_type', Venta::class)
            ->where('referencia_id', $venta->id)
            ->exists();
    }

    private function lineasCobro(Venta $venta): array
    {
        $pendiente = (float) $venta->total;
        $lineas = [];

        foreach ($venta->pagos as $pago) {
            if ($pendiente <= 0) {
                break;
            }

            $monto = min((float) $pago->monto, $pendiente);
            $pendiente = round($pendiente - $monto, 2);

            $lineas[] = [
                ...$this->cuentaCobro($pago->metodo),
                'debe' => $monto,
                'haber' => 0,
                'descripcion' => 'Cobro de venta',
            ];
        }

        return $lineas;
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
