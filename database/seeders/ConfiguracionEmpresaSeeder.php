<?php

namespace Database\Seeders;

use App\Models\ConfiguracionEmpresa;
use Illuminate\Database\Seeder;

class ConfiguracionEmpresaSeeder extends Seeder
{
    public function run(): void
    {
        $configs = [
            ['clave' => 'nombre', 'valor' => 'H Bakery'],
            ['clave' => 'direccion', 'valor' => 'San Salvador, El Salvador'],
            ['clave' => 'telefono', 'valor' => '2222-0000'],
        ];

        foreach ($configs as $config) {
            ConfiguracionEmpresa::create($config);
        }
    }
}
