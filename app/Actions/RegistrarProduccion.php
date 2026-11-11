<?php

namespace App\Actions;

use App\Models\Ingrediente;
use App\Models\KardexInventario;
use App\Models\OrdenProduccion;
use App\Models\PresentacionProducto;
use App\Models\Receta;
use App\Support\UnitFormatter;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class RegistrarProduccion
{
    public function execute(array $data, int $userId): array
    {
        $numeroLote = OrdenProduccion::generarNumeroLote();

        return DB::transaction(function () use ($data, $userId, $numeroLote) {
            $presentacion = PresentacionProducto::lockForUpdate()
                ->findOrFail($data['presentacion_id']);

            $receta = $presentacion->recetaActiva
                ?? $presentacion->producto->recetaDefault;

            $this->descontarIngredientes($presentacion, $receta, $data['cantidad'], $numeroLote, $userId);
            $this->sumarStockPresentacion($presentacion, $receta, $data['cantidad'], $numeroLote, $userId);

            OrdenProduccion::create([
                'numero_lote' => $numeroLote,
                'presentacion_id' => $presentacion->id,
                'cantidad' => $data['cantidad'],
                'user_id' => $userId,
                'notas' => $data['notas'] ?? null,
            ]);

            return [
                'numero_lote' => $numeroLote,
                'presentacion' => $presentacion->nombre,
                'cantidad' => $data['cantidad'],
                'sin_receta' => ! $receta,
            ];
        });
    }

    public static function calcularConsumoIngredientes(PresentacionProducto $presentacion, int $cantidadLote): array
    {
        $receta = $presentacion->recetaActiva
            ?? $presentacion->producto->recetaDefault;

        if (! $receta) {
            return [];
        }

        $loteUnidades = $receta->lote_unidades ?? $presentacion->cantidad_unidades;

        if ($loteUnidades <= 0) {
            throw new RuntimeException('La receta no tiene unds. por lote definidas.');
        }

        return $receta->calcularConsumoIngredientes($cantidadLote);
    }

    private function descontarIngredientes(PresentacionProducto $presentacion, ?Receta $receta, int $cantidadLote, string $numeroLote, int $userId): void
    {
        if (! $receta) {
            return;
        }

        $loteUnidades = $receta->lote_unidades ?? $presentacion->cantidad_unidades;

        if ($loteUnidades <= 0) {
            throw new RuntimeException('La receta no tiene unds. por lote definidas.');
        }

        $ingredienteIds = $receta->detalles->pluck('ingrediente_id')->filter();
        $ingredientes = Ingrediente::whereIn('id', $ingredienteIds)->lockForUpdate()->get()->keyBy('id');

        foreach ($receta->detalles as $detalle) {
            $ingrediente = $ingredientes->get($detalle->ingrediente_id);

            if (! $ingrediente) {
                continue;
            }

            $necesario = $detalle->consumoParaLotes($cantidadLote);

            if ($ingrediente->stock_actual < $necesario) {
                throw new RuntimeException(
                    "Stock insuficiente de \"{$ingrediente->nombre}\": ".
                    "necesario {$necesario} ".UnitFormatter::short($ingrediente->unidad_medida).', '.
                    "disponible {$ingrediente->stock_actual} ".UnitFormatter::short($ingrediente->unidad_medida).'.'
                );
            }

            $stockAnterior = (float) $ingrediente->stock_actual;
            $ingrediente->decrement('stock_actual', $necesario);

            KardexInventario::create([
                'tipo' => 'salida',
                'ingrediente_id' => $ingrediente->id,
                'cantidad' => $necesario,
                'stock_anterior' => $stockAnterior,
                'stock_resultante' => $stockAnterior - $necesario,
                'user_id' => $userId,
                'origen_movimiento' => 'produccion',
                'reversible' => true,
                'notas' => "Producción lote {$numeroLote}: {$presentacion->nombre}",
            ]);
        }
    }

    private function sumarStockPresentacion(PresentacionProducto $presentacion, ?Receta $receta, int $cantidad, string $numeroLote, int $userId): void
    {
        $loteUnidades = $receta?->lote_unidades ?? $presentacion->cantidad_unidades;

        if ($loteUnidades <= 0) {
            throw new RuntimeException('No se puede determinar las unds. por lote.');
        }

        $unidades = round((float) bcmul($cantidad, (string) $loteUnidades, 4), 2);
        $stockAnterior = (float) $presentacion->stock_actual;
        $presentacion->increment('stock_actual', $unidades);

        KardexInventario::create([
            'tipo' => 'entrada',
            'referencia_type' => PresentacionProducto::class,
            'referencia_id' => $presentacion->id,
            'cantidad' => $unidades,
            'stock_anterior' => $stockAnterior,
            'stock_resultante' => $stockAnterior + $unidades,
            'user_id' => $userId,
            'origen_movimiento' => 'produccion',
            'reversible' => true,
            'notas' => "Producción lote {$numeroLote}: {$presentacion->nombre}",
        ]);
    }
}
