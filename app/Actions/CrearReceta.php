<?php

namespace App\Actions;

use App\Models\KardexInventario;
use App\Models\Receta;
use App\Models\RecetaDetalle;
use Illuminate\Support\Facades\DB;

class CrearReceta
{
    public function execute(?int $productoId, string $nombre, ?string $descripcion, array $ingredientes, ?int $presentacionId, int $tiempoPreparacion, int $loteUnidades, ?int $categoriaRecetaId): Receta
    {
        return DB::transaction(function () use ($productoId, $nombre, $descripcion, $ingredientes, $presentacionId, $tiempoPreparacion, $loteUnidades, $categoriaRecetaId) {
            $duplicates = collect($ingredientes)
                ->pluck('ingrediente_id')
                ->filter()
                ->countBy()
                ->filter(fn ($count) => $count > 1)
                ->keys();

            if ($duplicates->isNotEmpty()) {
                throw new \InvalidArgumentException(
                    'La receta no puede tener el mismo ingrediente más de una vez.'
                );
            }

            if (is_null($productoId) && ! is_null($presentacionId)) {
                throw new \InvalidArgumentException(
                    'No se puede asociar una presentación sin un producto.'
                );
            }

            if (! is_null($productoId)) {
                if (is_null($presentacionId)) {
                    $existeDefault = Receta::where('producto_id', $productoId)
                        ->whereNull('presentacion_id')
                        ->where('activo', true)
                        ->exists();
                    if ($existeDefault) {
                        throw new \InvalidArgumentException(
                            'El producto ya tiene una receta default activa.'
                        );
                    }
                } else {
                    $existePresentacion = Receta::where('presentacion_id', $presentacionId)
                        ->where('activo', true)
                        ->exists();
                    if ($existePresentacion) {
                        throw new \InvalidArgumentException(
                            'La presentación ya tiene una receta activa.'
                        );
                    }
                }
            }

            $receta = Receta::create([
                'producto_id' => $productoId,
                'presentacion_id' => $presentacionId,
                'categoria_receta_id' => $categoriaRecetaId,
                'nombre' => $nombre,
                'descripcion' => $descripcion,
                'tiempo_preparacion' => $tiempoPreparacion,
                'lote_unidades' => $loteUnidades,
                'activo' => true,
            ]);

            foreach ($ingredientes as $ing) {
                RecetaDetalle::create([
                    'receta_id' => $receta->id,
                    'ingrediente_id' => $ing['ingrediente_id'],
                    'cantidad' => $ing['cantidad'],
                ]);
            }

            $receta->load('detalles.ingrediente');

            $costoLote = $receta->costoPorLote();
            $costoUnidad = $receta->costoPorUnidad();

            $productoLabel = $productoId ? '' : ' (sin producto)';

            KardexInventario::create([
                'tipo' => 'creacion',
                'categoria' => 'receta',
                'user_id' => auth()->id(),
                'referencia_type' => Receta::class,
                'referencia_id' => $receta->id,
                'fecha' => now(),
                'notas' => "Receta \"{$nombre}\" creada{$productoLabel}. Categoria: #{$categoriaRecetaId}. Costo/lote: \${$costoLote}, Costo/und.: \${$costoUnidad}",
            ]);

            return $receta;
        });
    }
}
