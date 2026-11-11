<?php

namespace Database\Factories;

use App\Models\Pedido;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PedidoFactory extends Factory
{
    protected $model = Pedido::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'estado' => 'en preparacion',
            'tipo' => 'pedido',
            'fecha_entrega_estimada' => now()->addDays(3),
            'notas' => fake()->optional()->sentence(),
            'fecha' => now(),
        ];
    }

    public function enPreparacion(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado' => 'en preparacion',
        ]);
    }

    public function entregado(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado' => 'entregado',
        ]);
    }

    public function cancelado(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado' => 'cancelado',
        ]);
    }

    public function orden(): static
    {
        return $this->state(fn (array $attributes) => [
            'tipo' => 'orden',
            'fecha_entrega_estimada' => null,
        ]);
    }
}
