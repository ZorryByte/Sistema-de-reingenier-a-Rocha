<?php

namespace App\Actions;

use App\Models\CategoriaReceta;
use App\Models\KardexInventario;
use Illuminate\Support\Facades\DB;

class EliminarCategoriaReceta
{
    public function execute(CategoriaReceta $categoria): void
    {
        DB::transaction(function () use ($categoria) {
            $recetasAfectadas = $categoria->recetas()->count();
            $nombreCategoria = $categoria->nombre;
            $categoriaId = $categoria->id;

            $categoria->recetas()->update(['categoria_receta_id' => null]);

            KardexInventario::create([
                'tipo' => 'actualizacion',
                'categoria' => 'categoria_receta',
                'user_id' => auth()->id(),
                'referencia_type' => CategoriaReceta::class,
                'referencia_id' => $categoriaId,
                'fecha' => now(),
                'notas' => "Categoria de receta \"{$nombreCategoria}\" eliminada. Recetas sin categoria: {$recetasAfectadas}.",
            ]);

            $categoria->delete();
        });
    }
}
