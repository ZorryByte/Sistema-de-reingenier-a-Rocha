<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            AdminUserSeeder::class,
            ConfiguracionEmpresaSeeder::class,
            CuentasContablesSeeder::class,
            CategoriasGastoSeeder::class,
            CategoriasRecetaSeeder::class,
        ]);

        User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'email' => 'test@example.com',
                'password' => 'Admin12345',
                'rol' => 'caja',
                'activo' => true,
            ]
        );
    }
}
