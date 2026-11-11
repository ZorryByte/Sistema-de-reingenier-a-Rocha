<?php

namespace App\Actions;

use App\Models\Ingrediente;
use App\Models\KardexInventario;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class RegistrarEntradaInventario
{
    public function execute(array $data, int $userId): KardexInventario
    {
        return DB::transaction(function () use ($data, $userId) {
            $ingrediente = Ingrediente::lockForUpdate()->findOrFail($data['entidad_id']);

            $cantidad = (float) $data['cantidad'];
            $stockAnterior = (float) $ingrediente->stock_actual;
            $stockResultante = $stockAnterior + $cantidad;
            $costoAnterior = (float) $ingrediente->costo_unitario;
            $precioUnitarioEntrada = $this->precioUnitarioEntrada($data, $cantidad);
            $costoResultante = $this->costoResultante(
                $data['modo_costo'],
                $stockAnterior,
                $costoAnterior,
                $precioUnitarioEntrada,
                $cantidad
            );

            $ingrediente->update([
                'stock_actual' => $stockResultante,
                'costo_unitario' => $costoResultante,
            ]);

            return KardexInventario::create([
                'tipo' => 'entrada',
                'categoria' => $data['categoria'] ?? null,
                'ingrediente_id' => $ingrediente->id,
                'cantidad' => $cantidad,
                'stock_anterior' => $stockAnterior,
                'stock_resultante' => $stockResultante,
                'costo_anterior' => $costoAnterior,
                'costo_resultante' => $costoResultante,
                'user_id' => $userId,
                'origen_movimiento' => 'ajuste',
                'reversible' => true,
                'notas' => $data['notas'] ?? null,
                'fecha' => $this->fecha($data['fecha'] ?? null),
            ]);
        });
    }

    private function precioUnitarioEntrada(array $data, float $cantidad): float
    {
        if (isset($data['precio_unitario']) && $data['precio_unitario'] !== '') {
            return (float) $data['precio_unitario'];
        }

        $costoTotal = (float) ($data['costo_total'] ?? 0);

        return $cantidad > 0 ? $costoTotal / $cantidad : 0;
    }

    private function costoResultante(string $modo, float $stockAnterior, float $costoAnterior, float $precioUnitarioEntrada, float $cantidad): float
    {
        $stockNuevo = $stockAnterior + $cantidad;
        $valorActual = $stockAnterior * $costoAnterior;

        if ($modo === 'precio_actual') {
            return $stockNuevo > 0 ? $valorActual / $stockNuevo : 0;
        }

        $valorEntrada = $cantidad * $precioUnitarioEntrada;

        return $stockNuevo > 0 ? ($valorActual + $valorEntrada) / $stockNuevo : 0;
    }

    private function fecha(?string $fecha): Carbon
    {
        return $fecha ? Carbon::parse($fecha) : now();
    }
}
