<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Crea un usuario administrador por defecto si no existe.
 *
 * Se ejecuta con: php artisan db:seed --class=AdminUserSeeder
 * O como parte del seed completo: php artisan migrate --seed
 */
class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@hbakery.com'],
            [
                'name' => 'Administrador',
                'email' => 'admin@hbakery.com',
                'password' => 'admin123',
                'rol' => 'admin',
                'activo' => true,
            ]
        );
    }
}
