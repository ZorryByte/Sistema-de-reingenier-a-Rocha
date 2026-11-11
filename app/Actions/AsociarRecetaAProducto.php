<?php

namespace App\Actions;

use App\Models\KardexInventario;
use App\Models\Producto;
use App\Models\Receta;
use Illuminate\Support\Facades\DB;

class AsociarRecetaAProducto
{
    public function execute(?int $recetaId, Producto $producto): ?Receta
    {
        if (! $recetaId) {
            return null;
        }

        return DB::transaction(function () use ($recetaId, $producto) {
            if ($producto->recetaDefault()->lockForUpdate()->exists()) {
                throw new \InvalidArgumentException('Este producto ya tiene una receta general.');
            }

            $receta = Receta::whereKey($recetaId)
                ->where('activo', true)
                ->whereNull('producto_id')
                ->whereNull('presentacion_id')
                ->lockForUpdate()
                ->first();

            if (! $receta) {
                throw new \InvalidArgumentException('Selecciona una receta independiente activa.');
            }

            $receta->update(['producto_id' => $producto->id]);

            KardexInventario::create([
                'tipo' => 'actualizacion',
                'categoria' => 'receta',
                'user_id' => auth()->id(),
                'referencia_type' => Receta::class,
                'referencia_id' => $receta->id,
                'fecha' => now(),
                'notas' => "Receta \"{$receta->nombre}\" asociada al producto \"{$producto->nombre}\"",
            ]);

            return $receta;
        });
    }
}
