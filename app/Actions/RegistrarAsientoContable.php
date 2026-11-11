<?php

namespace App\Actions;

use App\Models\AsientoContable;
use App\Models\CuentaContable;
use App\Support\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class RegistrarAsientoContable
{
    public function execute(array $data): AsientoContable
    {
        $detalles = collect($data['detalles'] ?? [])
            ->map(fn ($detalle) => $this->normalizarDetalle($detalle))
            ->values();

        if ($detalles->count() < 2) {
            throw new RuntimeException('El asiento contable requiere al menos dos detalles.');
        }

        if ($detalles->pluck('cuenta')->pluck('id')->unique()->count() < 2) {
            throw new RuntimeException('El asiento contable requiere al menos dos cuentas diferentes.');
        }

        $totalDebe = $detalles->sum('debe_centavos');
        $totalHaber = $detalles->sum('haber_centavos');

        if ($totalDebe <= 0 || $totalDebe !== $totalHaber) {
            throw new RuntimeException('El asiento contable debe estar balanceado.');
        }

        return DB::transaction(function () use ($data, $detalles) {
            $referencia = $data['referencia'] ?? null;

            $asiento = AsientoContable::create([
                'fecha_contable' => $data['fecha_contable'] ?? now(),
                'origen' => $data['origen'],
                'estado' => 'confirmado',
                'referencia_type' => $referencia instanceof Model ? $referencia::class : ($data['referencia_type'] ?? null),
                'referencia_id' => $referencia instanceof Model ? $referencia->getKey() : ($data['referencia_id'] ?? null),
                'user_id' => $data['user_id'] ?? null,
                'asiento_reversado_id' => $data['asiento_reversado_id'] ?? null,
                'descripcion' => $data['descripcion'] ?? null,
            ]);

            foreach ($detalles as $detalle) {
                $asiento->detalles()->create([
                    'cuenta_contable_id' => $detalle['cuenta']->id,
                    'debe' => Money::decimalFromCents($detalle['debe_centavos']),
                    'haber' => Money::decimalFromCents($detalle['haber_centavos']),
                    'descripcion' => $detalle['descripcion'] ?? null,
                ]);
            }

            return $asiento->load('detalles.cuenta');
        });
    }

    private function normalizarDetalle(array $detalle): array
    {
        $cuenta = $this->cuenta($detalle);
        $debe = Money::cents($detalle['debe'] ?? 0);
        $haber = Money::cents($detalle['haber'] ?? 0);

        if ($debe < 0 || $haber < 0) {
            throw new RuntimeException('Los importes contables no pueden ser negativos.');
        }

        if (($debe > 0 && $haber > 0) || ($debe === 0 && $haber === 0)) {
            throw new RuntimeException('Cada detalle contable debe tener debe o haber, pero no ambos.');
        }

        return [
            ...$detalle,
            'cuenta' => $cuenta,
            'debe_centavos' => $debe,
            'haber_centavos' => $haber,
        ];
    }

    private function cuenta(array $detalle): CuentaContable
    {
        if (isset($detalle['cuenta_id'])) {
            $cuenta = CuentaContable::whereKey($detalle['cuenta_id'])->where('activo', true)->first();
        } elseif (isset($detalle['codigo'])) {
            $cuenta = CuentaContable::where('codigo', $detalle['codigo'])->where('activo', true)->first();
        } else {
            $cuenta = null;
        }

        if (! $cuenta) {
            throw new RuntimeException('La cuenta contable no existe o está inactiva.');
        }

        return $cuenta;
    }
}
