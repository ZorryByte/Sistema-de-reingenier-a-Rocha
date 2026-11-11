<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Venta;
use Illuminate\Database\Eloquent\Factories\Factory;

class VentaFactory extends Factory
{
    protected $model = Venta::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'pedido_id' => null,
            'total' => fake()->randomFloat(2, 5, 200),
            'estado' => 'pagada',
            'numero_factura' => 'FAC-'.fake()->unique()->numerify('####'),
            'pdf_path' => null,
            'notas' => null,
            'created_at' => now(),
        ];
    }

    public function pagada(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado' => 'pagada',
        ]);
    }

    public function cancelada(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado' => 'cancelada',
        ]);
    }
}
