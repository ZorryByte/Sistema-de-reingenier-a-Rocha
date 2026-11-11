<?php

namespace App\Actions;

use App\Models\CategoriaReceta;
use App\Models\KardexInventario;
use Illuminate\Support\Facades\DB;

class CrearCategoriaReceta
{
    public function execute(string $nombre): CategoriaReceta
    {
        return DB::transaction(function () use ($nombre) {
            $nombreNormalizado = CategoriaReceta::normalizarNombre($nombre);
            $slug = CategoriaReceta::slugDesdeNombre($nombreNormalizado);

            if ($nombreNormalizado === '' || $slug === '') {
                throw new \InvalidArgumentException('El nombre de la categoria es obligatorio.');
            }

            if (CategoriaReceta::where('slug', $slug)->exists()) {
                throw new \InvalidArgumentException('Ya existe una categoria de receta con ese nombre.');
            }

            $categoria = CategoriaReceta::create([
                'nombre' => $nombreNormalizado,
                'slug' => $slug,
            ]);

            KardexInventario::create([
                'tipo' => 'creacion',
                'categoria' => 'categoria_receta',
                'user_id' => auth()->id(),
                'referencia_type' => CategoriaReceta::class,
                'referencia_id' => $categoria->id,
                'fecha' => now(),
                'notas' => "Categoria de receta \"{$categoria->nombre}\" creada.",
            ]);

            return $categoria;
        });
    }
}
