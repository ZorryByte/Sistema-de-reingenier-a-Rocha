<?php

namespace Database\Seeders;

use App\Models\CategoriaGasto;
use App\Models\CuentaContable;
use Illuminate\Database\Seeder;

class CategoriasGastoSeeder extends Seeder
{
    public function run(): void
    {
        $categorias = [
            ['codigo' => 'electricidad', 'nombre' => 'Electricidad', 'tipo' => 'fijo', 'cuenta' => '5101'],
            ['codigo' => 'agua', 'nombre' => 'Agua', 'tipo' => 'fijo', 'cuenta' => '5102'],
            ['codigo' => 'gas', 'nombre' => 'Gas', 'tipo' => 'fijo', 'cuenta' => '5103'],
            ['codigo' => 'renta', 'nombre' => 'Renta del local', 'tipo' => 'fijo', 'cuenta' => '5104'],
            ['codigo' => 'transporte', 'nombre' => 'Transporte y entregas', 'tipo' => 'variable', 'cuenta' => '5105'],
            ['codigo' => 'mantenimiento', 'nombre' => 'Mantenimiento', 'tipo' => 'variable', 'cuenta' => '5106'],
            ['codigo' => 'limpieza', 'nombre' => 'Limpieza', 'tipo' => 'variable', 'cuenta' => '5107'],
            ['codigo' => 'internet', 'nombre' => 'Teléfono e internet', 'tipo' => 'fijo', 'cuenta' => '5108'],
            ['codigo' => 'nomina', 'nombre' => 'Nómina y salarios', 'tipo' => 'fijo', 'cuenta' => '5109'],
            ['codigo' => 'otros', 'nombre' => 'Otros gastos', 'tipo' => 'variable', 'cuenta' => '5199'],
        ];

        foreach ($categorias as $categoria) {
            CategoriaGasto::updateOrCreate(
                ['codigo' => $categoria['codigo']],
                [
                    'nombre' => $categoria['nombre'],
                    'tipo' => $categoria['tipo'],
                    'cuenta_contable_id' => CuentaContable::where('codigo', $categoria['cuenta'])->value('id'),
                    'activo' => true,
                ]
            );
        }
    }
}
