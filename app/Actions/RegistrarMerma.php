<?php

namespace App\Actions;

use App\Models\Ingrediente;
use App\Models\KardexInventario;
use App\Support\UnitFormatter;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class RegistrarMerma
{
    public function execute(array $data, int $userId): KardexInventario
    {
        return DB::transaction(function () use ($data, $userId) {
            $ingrediente = Ingrediente::lockForUpdate()
                ->find($data['ingrediente_id']);

            if (! $ingrediente) {
                throw new RuntimeException('Ingrediente no encontrado.');
            }

            $cantidad = (float) $data['cantidad'];

            if ($cantidad <= 0) {
                throw new RuntimeException('La cantidad debe ser mayor a 0.');
            }

            if ($cantidad > (float) $ingrediente->stock_actual) {
                throw new RuntimeException(
                    "No se puede registrar merma de {$cantidad} ".UnitFormatter::short($ingrediente->unidad_medida).'. '.
                    "Stock disponible: {$ingrediente->stock_actual} ".UnitFormatter::short($ingrediente->unidad_medida).'.'
                );
            }

            $stockAnterior = (float) $ingrediente->stock_actual;
            $stockResultante = $stockAnterior - $cantidad;
            $costoActual = (float) $ingrediente->costo_unitario;

            $ingrediente->update([
                'stock_actual' => $stockResultante,
            ]);

            $movimiento = KardexInventario::create([
                'tipo' => 'merma',
                'categoria' => $data['categoria'] ?? null,
                'ingrediente_id' => $ingrediente->id,
                'cantidad' => $cantidad,
                'stock_anterior' => $stockAnterior,
                'stock_resultante' => $stockResultante,
                'costo_anterior' => $costoActual,
                'costo_resultante' => $costoActual,
                'user_id' => $userId,
                'origen_movimiento' => 'manual',
                'reversible' => true,
                'notas' => $data['notas'] ?? null,
                'fecha' => $this->fecha($data['fecha'] ?? null),
            ]);

            return $movimiento;
        });
    }

    private function fecha(?string $fecha): Carbon
    {
        return $fecha ? Carbon::parse($fecha) : now();
    }
}
