<?php

namespace App\Actions;

use App\Models\KardexInventario;
use App\Models\Receta;
use Illuminate\Support\Facades\DB;

class ActivarReceta
{
    /**
     * Activar receta con registro de movimiento y validación de conflictos.
     */
    public function execute(Receta $receta): void
    {
        DB::transaction(function () use ($receta) {
            if (is_null($receta->producto_id)) {
                KardexInventario::create([
                    'tipo' => 'actualizacion',
                    'categoria' => 'receta',
                    'user_id' => auth()->id(),
                    'referencia_type' => Receta::class,
                    'referencia_id' => $receta->id,
                    'fecha' => now(),
                    'notas' => "Receta \"{$receta->nombre}\" activada",
                ]);

                $receta->update(['activo' => true]);

                return;
            }

            if (is_null($receta->presentacion_id)) {
                $existeDefault = Receta::where('producto_id', $receta->producto_id)
                    ->whereNull('presentacion_id')
                    ->where('activo', true)
                    ->where('id', '!=', $receta->id)
                    ->exists();

                if ($existeDefault) {
                    throw new \InvalidArgumentException(
                        'El producto ya tiene una receta default activa.'
                    );
                }
            } else {
                $existePresentacion = Receta::where('presentacion_id', $receta->presentacion_id)
                    ->where('activo', true)
                    ->where('id', '!=', $receta->id)
                    ->exists();

                if ($existePresentacion) {
                    throw new \InvalidArgumentException(
                        'La presentación ya tiene una receta activa.'
                    );
                }
            }

            $receta->update(['activo' => true]);

            KardexInventario::create([
                'tipo' => 'actualizacion',
                'categoria' => 'receta',
                'user_id' => auth()->id(),
                'referencia_type' => Receta::class,
                'referencia_id' => $receta->id,
                'fecha' => now(),
                'notas' => "Receta \"{$receta->nombre}\" activada",
            ]);
        });
    }
}
