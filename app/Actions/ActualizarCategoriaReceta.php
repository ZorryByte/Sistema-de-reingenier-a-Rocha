<?php

namespace App\Actions;

use App\Models\CategoriaReceta;
use App\Models\KardexInventario;
use Illuminate\Support\Facades\DB;

class ActualizarCategoriaReceta
{
    public function execute(CategoriaReceta $categoria, string $nombre): CategoriaReceta
    {
        return DB::transaction(function () use ($categoria, $nombre) {
            $nombreNormalizado = CategoriaReceta::normalizarNombre($nombre);
            $slug = CategoriaReceta::slugDesdeNombre($nombreNormalizado);

            if ($nombreNormalizado === '' || $slug === '') {
                throw new \InvalidArgumentException('El nombre de la categoria es obligatorio.');
            }

            if (CategoriaReceta::where('slug', $slug)->whereKeyNot($categoria->id)->exists()) {
                throw new \InvalidArgumentException('Ya existe una categoria de receta con ese nombre.');
            }

            $nombreAnterior = $categoria->nombre;

            $categoria->update([
                'nombre' => $nombreNormalizado,
                'slug' => $slug,
            ]);

            if ($nombreAnterior !== $categoria->nombre) {
                KardexInventario::create([
                    'tipo' => 'actualizacion',
                    'categoria' => 'categoria_receta',
                    'user_id' => auth()->id(),
                    'referencia_type' => CategoriaReceta::class,
                    'referencia_id' => $categoria->id,
                    'fecha' => now(),
                    'notas' => "Categoria de receta renombrada: \"{$nombreAnterior}\" a \"{$categoria->nombre}\".",
                ]);
            }

            return $categoria;
        });
    }
}
