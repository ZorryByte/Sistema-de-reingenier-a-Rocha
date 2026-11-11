<?php

namespace Database\Seeders;

use App\Models\CuentaContable;
use Illuminate\Database\Seeder;

class CuentasContablesSeeder extends Seeder
{
    public function run(): void
    {
        $cuentas = [
            ['codigo' => '1010', 'nombre' => 'Caja', 'tipo' => 'activo'],
            ['codigo' => '1020', 'nombre' => 'Bancos', 'tipo' => 'activo'],
            ['codigo' => '1090', 'nombre' => 'Otros medios de cobro', 'tipo' => 'activo'],
            ['codigo' => '4010', 'nombre' => 'Ventas', 'tipo' => 'ingreso'],
            ['codigo' => '5101', 'nombre' => 'Electricidad', 'tipo' => 'gasto'],
            ['codigo' => '5102', 'nombre' => 'Agua', 'tipo' => 'gasto'],
            ['codigo' => '5103', 'nombre' => 'Gas', 'tipo' => 'gasto'],
            ['codigo' => '5104', 'nombre' => 'Renta', 'tipo' => 'gasto'],
            ['codigo' => '5105', 'nombre' => 'Transporte', 'tipo' => 'gasto'],
            ['codigo' => '5106', 'nombre' => 'Mantenimiento', 'tipo' => 'gasto'],
            ['codigo' => '5107', 'nombre' => 'Limpieza', 'tipo' => 'gasto'],
            ['codigo' => '5108', 'nombre' => 'Teléfono e internet', 'tipo' => 'gasto'],
            ['codigo' => '5109', 'nombre' => 'Nómina', 'tipo' => 'gasto'],
            ['codigo' => '5199', 'nombre' => 'Otros gastos', 'tipo' => 'gasto'],
        ];

        foreach ($cuentas as $cuenta) {
            CuentaContable::updateOrCreate(
                ['codigo' => $cuenta['codigo']],
                [...$cuenta, 'activo' => true]
            );
        }
    }
}
