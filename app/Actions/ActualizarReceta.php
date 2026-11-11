<?php

namespace App\Actions;

use App\Models\KardexInventario;
use App\Models\Receta;
use Illuminate\Support\Facades\DB;

class ActualizarReceta
{
    public function execute(Receta $receta, string $nombre, ?string $descripcion, bool $activo, array $ingredientes, int $tiempoPreparacion, int $loteUnidades, ?int $productoId = null, ?int $presentacionId = null, bool $cambiarProducto = false, bool $cambiarPresentacion = false, ?int $categoriaRecetaId = null): Receta
    {
        return DB::transaction(function () use ($receta, $nombre, $descripcion, $activo, $ingredientes, $tiempoPreparacion, $loteUnidades, $productoId, $presentacionId, $cambiarProducto, $cambiarPresentacion, $categoriaRecetaId) {
            $duplicates = collect($ingredientes)
                ->pluck('ingrediente_id')
                ->filter()
                ->countBy()
                ->filter(fn ($count) => $count > 1)
                ->keys();

            if ($duplicates->isNotEmpty()) {
                throw new \InvalidArgumentException(
                    'La receta no puede tener el mismo ingrediente mas de una vez.'
                );
            }

            $productoIdFinal = $cambiarProducto ? $productoId : $receta->producto_id;
            $presentacionIdFinal = $cambiarPresentacion ? $presentacionId : $receta->presentacion_id;

            if (is_null($productoIdFinal) && ! is_null($presentacionIdFinal)) {
                throw new \InvalidArgumentException(
                    'No se puede asociar una presentacion sin un producto.'
                );
            }

            if (! is_null($productoIdFinal)) {
                if (is_null($presentacionIdFinal)) {
                    $existeDefault = Receta::where('producto_id', $productoIdFinal)
                        ->whereNull('presentacion_id')
                        ->where('activo', true)
                        ->where('id', '!=', $receta->id)
                        ->exists();
                    if ($activo && $existeDefault) {
                        throw new \InvalidArgumentException(
                            'El producto ya tiene una receta default activa.'
                        );
                    }
                } else {
                    $existePresentacion = Receta::where('presentacion_id', $presentacionIdFinal)
                        ->where('activo', true)
                        ->where('id', '!=', $receta->id)
                        ->exists();
                    if ($activo && $existePresentacion) {
                        throw new \InvalidArgumentException(
                            'La presentacion ya tiene una receta activa.'
                        );
                    }
                }
            }

            $cambios = [];

            if ($receta->nombre !== $nombre) {
                $cambios[] = "nombre: \"{$receta->nombre}\" a \"{$nombre}\"";
            }
            if ($receta->descripcion !== $descripcion) {
                $cambios[] = 'descripcion actualizada';
            }
            if ((bool) $receta->activo !== $activo) {
                $cambios[] = $activo ? 'activada' : 'desactivada';
            }
            if ($receta->tiempo_preparacion !== $tiempoPreparacion) {
                $cambios[] = 'tiempo de preparacion actualizado';
            }
            if ($receta->lote_unidades !== $loteUnidades) {
                $cambios[] = 'unds. por lote actualizado';
            }
            if ($receta->producto_id !== $productoIdFinal) {
                $cambios[] = $productoIdFinal ? "producto asociado: #{$productoIdFinal}" : 'producto desasociado';
            }
            if ($receta->presentacion_id !== $presentacionIdFinal) {
                $cambios[] = $presentacionIdFinal ? "presentacion: #{$presentacionIdFinal}" : 'presentacion removida';
            }
            if ($receta->categoria_receta_id !== $categoriaRecetaId) {
                $cambios[] = $categoriaRecetaId ? "categoria de receta: #{$categoriaRecetaId}" : 'categoria de receta removida';
            }

            $receta->update([
                'nombre' => $nombre,
                'descripcion' => $descripcion,
                'tiempo_preparacion' => $tiempoPreparacion,
                'lote_unidades' => $loteUnidades,
                'activo' => $activo,
                'producto_id' => $productoIdFinal,
                'presentacion_id' => $presentacionIdFinal,
                'categoria_receta_id' => $categoriaRecetaId,
            ]);

            $receta->detalles()->delete();

            foreach ($ingredientes as $ing) {
                $receta->detalles()->create([
                    'ingrediente_id' => $ing['ingrediente_id'],
                    'cantidad' => $ing['cantidad'],
                ]);
            }

            $receta->load('detalles.ingrediente');

            $nuevoCostoLote = $receta->costoPorLote();
            $nuevoCostoUnidad = $receta->costoPorUnidad();
            $cambios[] = "costo/lote: \${$nuevoCostoLote}, costo/und.: \${$nuevoCostoUnidad}";

            if (! empty($cambios)) {
                KardexInventario::create([
                    'tipo' => 'actualizacion',
                    'categoria' => 'receta',
                    'user_id' => auth()->id(),
                    'referencia_type' => Receta::class,
                    'referencia_id' => $receta->id,
                    'fecha' => now(),
                    'notas' => implode(', ', $cambios),
                ]);
            }

            return $receta->load('detalles.ingrediente');
        });
    }
}
