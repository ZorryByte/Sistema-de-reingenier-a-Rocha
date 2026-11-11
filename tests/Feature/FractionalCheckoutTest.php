<?php

namespace Tests\Feature;

use App\Models\Ingrediente;
use App\Models\KardexInventario;
use App\Models\Pedido;
use App\Models\PresentacionProducto;
use App\Models\Producto;
use App\Models\Receta;
use App\Models\User;
use App\Models\Venta;
use App\Services\PdfDataBuilder;
use Database\Seeders\CuentasContablesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FractionalCheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_exact_fractional_payment_matches_receipt_and_inventory(): void
    {
        $this->seed(CuentasContablesSeeder::class);
        $presentation = $this->presentation('Por peso', 1);

        $this->actingAs(User::factory()->create())->post(route('ventas.store'), [
            'items' => [['presentacion_id' => $presentation->id, 'cantidad' => '0.3333']],
            'pagos' => [['metodo' => 'efectivo', 'monto' => '0.33']],
        ])->assertSessionHasNoErrors()->assertSessionHas('success');

        $sale = Venta::sole();
        $this->assertSame('0.33', $sale->total);
        $this->assertSame('0.3333', $sale->detalles()->sole()->cantidad);
        $this->assertSame('9.6667', $presentation->fresh()->stock_actual);
        $this->assertSame('0.3333', KardexInventario::where('venta_id', $sale->id)->sole()->cantidad);
        $this->assertStringContainsString('>0.3333<', view('pdf.factura', PdfDataBuilder::buildVentaData($sale))->render());
    }

    public function test_delivery_estimate_validation_and_sale_agree_at_half_a_cent(): void
    {
        $this->seed(CuentasContablesSeeder::class);
        $cashier = User::factory()->create();
        $order = Pedido::factory()->enPreparacion()->create(['user_id' => $cashier->id]);

        foreach (['Primera', 'Segunda'] as $name) {
            $order->detalles()->create([
                'presentacion_id' => $this->presentation($name, 0.67)->id,
                'cantidad' => '0.50',
            ]);
        }

        $this->actingAs($cashier)->get(route('pedidos.show', $order))
            ->assertOk()->assertSee('value="0.68"', false)->assertSee('$0.34')->assertSee('$0.68');

        $pdf = PdfDataBuilder::buildPedidoData($order);
        $this->assertEquals(0.68, $pdf['totalPedido']);
        $this->assertSame([34, 34], $pdf['items']->pluck('subtotal_cents')->all());

        $this->followingRedirects()->from(route('pedidos.show', $order))->post(route('pedidos.entregar', $order), [
            'pagos' => [['metodo' => 'tarjeta', 'monto' => '0.67', 'referencia' => 'REF-QA']],
        ])->assertOk()->assertSee('value="0.67"', false)->assertSee('REF-QA')
            ->assertSee('La suma de pagos es menor al total del pedido.');
        $this->assertDatabaseCount('ventas', 0);

        $this->post(route('pedidos.entregar', $order), [
            'pagos' => [
                ['metodo' => 'efectivo', 'monto' => '0.34'],
                ['metodo' => 'tarjeta', 'monto' => '0.34'],
            ],
        ])->assertSessionHasNoErrors()->assertSessionHas('success');

        $this->assertSame('0.68', Venta::sole()->total);
    }

    public function test_profitability_with_an_active_recipe_and_an_uncosted_presentation(): void
    {
        $priced = $this->presentation('Con receta', 2.01);
        $this->presentation('Sin receta', 2.01);
        $ingredient = Ingrediente::create(['nombre' => 'Harina QA', 'unidad_medida' => 'g', 'costo_unitario' => 3.02]);
        $recipe = Receta::create([
            'producto_id' => $priced->producto_id,
            'presentacion_id' => $priced->id,
            'nombre' => 'Receta QA',
            'lote_unidades' => 1,
            'activo' => true,
        ]);
        $recipe->detalles()->create(['ingrediente_id' => $ingredient->id, 'cantidad' => 1]);

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('finanzas.reportes.rentabilidad'))
            ->assertOk()->assertSee('Sin costo calculado')->assertSee('No calculable')
            ->assertSee('-$1.01')->assertSee('-50.2%');
    }

    private function presentation(string $name, float $price): PresentacionProducto
    {
        return PresentacionProducto::create([
            'producto_id' => Producto::create(['nombre' => $name.' QA', 'activo' => true])->id,
            'nombre' => $name,
            'cantidad_unidades' => 1,
            'precio' => $price,
            'stock_actual' => 10,
            'tipo_venta' => 'peso',
            'activo' => true,
        ]);
    }
}
