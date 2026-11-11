<?php

namespace Tests\Feature;

use App\Models\Pedido;
use App\Models\PresentacionProducto;
use App\Models\Producto;
use App\Models\User;
use App\Models\Venta;
use Database\Seeders\CuentasContablesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DisponibilidadStockTest extends TestCase
{
    use RefreshDatabase;

    public function test_reservations_are_calculated_per_presentation_and_follow_order_changes(): void
    {
        $this->actingAs(User::factory()->create());
        $a = $this->presentation('A', 10);
        $b = $this->presentation('B', 10);

        $this->post(route('pedidos.store'), [
            'tipo' => 'orden', 'items' => [$this->item($a, 2), $this->item($b, 8)],
        ])->assertSessionHas('success');
        $order = Pedido::sole();

        $this->post(route('pedidos.store'), [
            'tipo' => 'orden', 'items' => [$this->item($a, 8)],
        ])->assertSessionHas('success');

        $this->put(route('pedidos.update', $order), [
            'items' => [$this->item($a, 2), $this->item($b, 3)],
        ])->assertSessionHas('success');

        $this->post(route('pedidos.store'), [
            'tipo' => 'orden', 'items' => [$this->item($b, 7)],
        ])->assertSessionHas('success');
        $this->patch(route('pedidos.cancelar', $order))->assertSessionHas('success');
        $this->post(route('pedidos.store'), [
            'tipo' => 'orden', 'items' => [$this->item($b, 3)],
        ])->assertSessionHas('success');
    }

    public function test_checkout_protects_other_orders_and_can_consume_its_own_reservation(): void
    {
        $this->seed(CuentasContablesSeeder::class);
        $this->actingAs(User::factory()->create());
        $a = $this->presentation('A', 5);
        $this->post(route('pedidos.store'), [
            'tipo' => 'orden', 'items' => [$this->item($a, 5)],
        ])->assertSessionHas('success');
        $order = Pedido::sole();

        $this->get(route('ventas.create'))
            ->assertViewHas('catalogo', fn ($catalogo) => $catalogo->firstWhere('id', $a->id)['stock'] == 0);
        $this->post(route('ventas.store'), [
            'items' => [$this->item($a, 1)],
            'pagos' => [['metodo' => 'efectivo', 'monto' => 1]],
        ])->assertSessionHas('error');

        $this->assertDatabaseCount('ventas', 0);
        $this->assertDatabaseCount('asientos_contables', 0);
        $this->assertDatabaseCount('kardex_inventario', 1);
        $this->assertSame('5.0000', $a->fresh()->stock_actual);

        $a->update(['activo' => false]);
        $this->post(route('pedidos.entregar', $order), [
            'pagos' => [['metodo' => 'efectivo', 'monto' => 5]],
        ])->assertSessionHas('success');
        $this->assertSame($order->id, Venta::sole()->pedido_id);
        $this->assertSame('0.0000', $a->fresh()->stock_actual);
    }

    public function test_new_orders_and_sales_reject_inactive_presentations(): void
    {
        $this->seed(CuentasContablesSeeder::class);
        $this->actingAs(User::factory()->create());
        $a = $this->presentation('A', 5);
        $a->update(['activo' => false]);

        $this->post(route('pedidos.store'), [
            'tipo' => 'orden', 'items' => [$this->item($a, 1)],
        ])->assertSessionHas('error', 'La presentación "A" está inactiva.');
        $this->post(route('ventas.store'), [
            'items' => [$this->item($a, 1)],
            'pagos' => [['metodo' => 'efectivo', 'monto' => 1]],
        ])->assertSessionHas('error', 'La presentación "A" está inactiva.');

        $this->assertDatabaseCount('pedidos', 0);
        $this->assertDatabaseCount('ventas', 0);
        $this->assertSame('5.0000', $a->fresh()->stock_actual);
    }

    private function presentation(string $name, int $stock): PresentacionProducto
    {
        return PresentacionProducto::create([
            'producto_id' => Producto::create(['nombre' => $name, 'activo' => true])->id,
            'nombre' => $name,
            'cantidad_unidades' => 1,
            'precio' => 1,
            'stock_actual' => $stock,
            'activo' => true,
        ]);
    }

    private function item(PresentacionProducto $presentation, int $quantity): array
    {
        return ['presentacion_id' => $presentation->id, 'cantidad' => $quantity];
    }
}
