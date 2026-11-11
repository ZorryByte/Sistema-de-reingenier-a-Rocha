<?php

namespace Database\Seeders;

use App\Models\CategoriaReceta;
use Illuminate\Database\Seeder;

class CategoriasRecetaSeeder extends Seeder
{
    public function run(): void
    {
        $categorias = [
            'Almuerzos',
            'Bebidas',
            'Coberturas',
            'Cremas y rellenos',
            'Desayunos',
            'Ensaladas',
            'Galletas',
            'Panes',
            'Pasteles',
            'Postres',
            'Repostería',
            'Sin azúcar',
            'Sin gluten',
            'Snacks',
            'Temporada',
        ];

        foreach ($categorias as $nombre) {
            CategoriaReceta::firstOrCreate(
                ['slug' => CategoriaReceta::slugDesdeNombre($nombre)],
                [
                    'nombre' => CategoriaReceta::normalizarNombre($nombre),
                ]
            );
        }
    }
}
