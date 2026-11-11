<?php

namespace App\Actions;

use App\Models\KardexInventario;
use App\Models\Receta;
use Illuminate\Support\Facades\DB;

class DesactivarReceta
{
    /**
     * Desactivar receta con registro de movimiento.
     */
    public function execute(Receta $receta): void
    {
        DB::transaction(function () use ($receta) {
            $receta->update(['activo' => false]);

            KardexInventario::create([
                'tipo' => 'actualizacion',
                'categoria' => 'receta',
                'user_id' => auth()->id(),
                'referencia_type' => Receta::class,
                'referencia_id' => $receta->id,
                'fecha' => now(),
                'notas' => "Receta \"{$receta->nombre}\" desactivada",
            ]);
        });
    }
}
