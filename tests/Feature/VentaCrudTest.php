<?php

namespace Tests\Feature;

use App\Enums\PedidoEstado;
use App\Enums\VentaEstado;
use App\Models\Ingrediente;
use App\Models\Pedido;
use App\Models\PresentacionProducto;
use App\Models\Producto;
use App\Models\Receta;
use App\Models\RecetaDetalle;
use App\Models\SerieDocumento;
use App\Models\User;
use App\Models\Venta;
use App\Models\VentaDetalle;
use Database\Seeders\CuentasContablesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VentaCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $caja;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CuentasContablesSeeder::class);
        $this->caja = User::factory()->create(['rol' => 'caja']);
        $this->admin = User::factory()->admin()->create();
    }

    public function test_caja_can_access_ventas_index(): void
    {
        $response = $this->actingAs($this->caja)->get(route('ventas.index'));

        $response->assertStatus(200);
        $response->assertSee('Ventas');
    }

    public function test_admin_can_access_ventas_index(): void
    {
        $response = $this->actingAs($this->admin)->get(route('ventas.index'));

        $response->assertStatus(200);
    }

    public function test_unauthenticated_user_cannot_access_ventas(): void
    {
        $response = $this->get(route('ventas.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_caja_can_view_ventas_create_form(): void
    {
        $producto = Producto::create(['nombre' => 'Concha', 'tipo_unidad' => 'unidad', 'activo' => true]);

        PresentacionProducto::create([
            'producto_id' => $producto->id,
            'nombre' => 'Chica',
            'cantidad_unidades' => 1,
            'precio' => 0.35,
            'activo' => true,
        ]);

        $response = $this->actingAs($this->caja)->get(route('ventas.create'));

        $response->assertStatus(200);
        $response->assertSee('Concha');
    }

    public function test_caja_can_register_simple_venta(): void
    {
        $producto = Producto::create(['nombre' => 'Concha', 'tipo_unidad' => 'unidad', 'activo' => true]);
        $presentacion = PresentacionProducto::create([
            'producto_id' => $producto->id,
            'nombre' => 'Chica',
            'cantidad_unidades' => 1,
            'precio' => 0.35,
            'stock_actual' => 100,
            'activo' => true,
        ]);

        SerieDocumento::create([
            'serie' => '001',
            'descripcion' => 'Tiquete de venta',
            'ultimo_numero' => 0,
            'year' => now()->year,
            'activo' => true,
        ]);

        $response = $this->actingAs($this->caja)->post(route('ventas.store'), [
            'items' => [
                ['presentacion_id' => $presentacion->id, 'cantidad' => 2],
            ],
            'pagos' => [
                ['metodo' => 'efectivo', 'monto' => 1.00],
            ],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('ventas', ['total' => 0.70]);
        $this->assertDatabaseHas('venta_detalles', [
            'presentacion_id' => $presentacion->id,
            'cantidad' => 2,
            'precio_unitario' => 0.35,
        ]);
    }

    public function test_venta_calculates_subtotal_with_descuento(): void
    {
        $producto = Producto::create(['nombre' => 'Concha', 'tipo_unidad' => 'unidad', 'activo' => true]);
        $presentacion = PresentacionProducto::create([
            'producto_id' => $producto->id,
            'nombre' => 'Chica',
            'cantidad_unidades' => 1,
            'precio' => 0.35,
            'stock_actual' => 100,
            'activo' => true,
        ]);

        $this->actingAs($this->caja)->post(route('ventas.store'), [
            'items' => [
                ['presentacion_id' => $presentacion->id, 'cantidad' => 2, 'descuento' => 0.10],
            ],
            'pagos' => [
                ['metodo' => 'efectivo', 'monto' => 0.60],
            ],
        ]);

        $detalle = VentaDetalle::first();
        $this->assertEquals(0.60, $detalle->subtotal);
    }

    public function test_venta_records_multiple_payments(): void
    {
        $producto = Producto::create(['nombre' => 'Concha', 'tipo_unidad' => 'unidad', 'activo' => true]);
        $presentacion = PresentacionProducto::create([
            'producto_id' => $producto->id,
            'nombre' => 'Chica',
            'cantidad_unidades' => 1,
            'precio' => 1.00,
            'stock_actual' => 100,
            'activo' => true,
        ]);

        $this->actingAs($this->caja)->post(route('ventas.store'), [
            'items' => [
                ['presentacion_id' => $presentacion->id, 'cantidad' => 1],
            ],
            'pagos' => [
                ['metodo' => 'efectivo', 'monto' => 0.50],
                ['metodo' => 'tarjeta', 'monto' => 0.50],
            ],
        ]);

        $this->assertDatabaseHas('venta_pagos', ['metodo' => 'efectivo', 'monto' => 0.50]);
        $this->assertDatabaseHas('venta_pagos', ['metodo' => 'tarjeta', 'monto' => 0.50]);
    }

    public function test_venta_validation_fails_when_payments_less_than_total(): void
    {
        $response = $this->actingAs($this->caja)->post(route('ventas.store'), [
            'items' => [
                ['presentacion_id' => 9999, 'cantidad' => 1],
            ],
            'pagos' => [
                ['metodo' => 'efectivo', 'monto' => 0.01],
            ],
        ]);

        $response->assertSessionHasErrors();
    }

    public function test_venta_validation_fails_without_items(): void
    {
        $response = $this->actingAs($this->caja)->post(route('ventas.store'), [
            'items' => [],
            'pagos' => [
                ['metodo' => 'efectivo', 'monto' => 10.00],
            ],
        ]);

        $response->assertSessionHasErrors('items');
    }

    public function test_venta_show_displays_venta_details(): void
    {
        $producto = Producto::create(['nombre' => 'Concha', 'tipo_unidad' => 'unidad', 'activo' => true]);
        $presentacion = PresentacionProducto::create([
            'producto_id' => $producto->id,
            'nombre' => 'Chica',
            'cantidad_unidades' => 1,
            'precio' => 0.35,
            'stock_actual' => 100,
            'activo' => true,
        ]);

        $this->actingAs($this->caja)->post(route('ventas.store'), [
            'items' => [
                ['presentacion_id' => $presentacion->id, 'cantidad' => 2],
            ],
            'pagos' => [
                ['metodo' => 'efectivo', 'monto' => 1.00],
            ],
        ]);

        $venta = Venta::first();

        $response = $this->actingAs($this->caja)->get(route('ventas.show', $venta));

        $response->assertStatus(200);
        $response->assertSee('Concha');
    }

    public function test_venta_with_receta_no_descuenta_ingredientes(): void
    {
        $producto = Producto::create(['nombre' => 'Concha', 'tipo_unidad' => 'unidad', 'activo' => true]);
        $presentacion = PresentacionProducto::create([
            'producto_id' => $producto->id,
            'nombre' => 'Chica',
            'cantidad_unidades' => 1,
            'precio' => 0.35,
            'stock_actual' => 0,
            'activo' => true,
        ]);

        $ingrediente = Ingrediente::create([
            'nombre' => 'Harina',
            'unidad_medida' => 'g',
            'stock_actual' => 1000,
            'stock_minimo' => 200,
            'costo_unitario' => 0.50,
        ]);

        $receta = Receta::create([
            'producto_id' => $producto->id,
            'nombre' => 'Receta Concha',
            'activo' => true,
        ]);

        RecetaDetalle::create([
            'receta_id' => $receta->id,
            'ingrediente_id' => $ingrediente->id,
            'cantidad' => 500,
        ]);

        $this->actingAs($this->admin)->post('/produccion', [
            'presentacion_id' => $presentacion->id,
            'cantidad' => 2,
        ]);

        $presentacion->refresh();
        $this->assertEquals(2, (float) $presentacion->stock_actual);

        $ingrediente->refresh();
        $this->assertEquals(0.0, (float) $ingrediente->stock_actual);

        $this->assertDatabaseHas('kardex_inventario', [
            'tipo' => 'salida',
            'ingrediente_id' => $ingrediente->id,
            'cantidad' => 1000.0,
            'origen_movimiento' => 'produccion',
        ]);

        $this->assertDatabaseHas('kardex_inventario', [
            'tipo' => 'entrada',
            'referencia_type' => PresentacionProducto::class,
            'referencia_id' => $presentacion->id,
            'cantidad' => 2,
            'origen_movimiento' => 'produccion',
        ]);
    }

    public function test_venta_solo_descuenta_presentacion(): void
    {
        $producto = Producto::create(['nombre' => 'Concha', 'tipo_unidad' => 'unidad', 'activo' => true]);
        $presentacion = PresentacionProducto::create([
            'producto_id' => $producto->id,
            'nombre' => 'Chica',
            'cantidad_unidades' => 1,
            'precio' => 0.35,
            'stock_actual' => 10,
            'activo' => true,
        ]);

        $this->actingAs($this->caja)->post(route('ventas.store'), [
            'items' => [
                ['presentacion_id' => $presentacion->id, 'cantidad' => 5],
            ],
            'pagos' => [
                ['metodo' => 'efectivo', 'monto' => 2.00],
            ],
        ]);

        $presentacion->refresh();
        $this->assertEquals(5, $presentacion->stock_actual);

        $this->assertDatabaseHas('kardex_inventario', [
            'tipo' => 'salida',
            'referencia_type' => PresentacionProducto::class,
            'referencia_id' => $presentacion->id,
            'cantidad' => 5,
            'origen_movimiento' => 'venta',
        ]);
    }

    public function test_venta_linked_to_pedido_marks_pedido_entregado(): void
    {
        $pedido = Pedido::create([
            'user_id' => $this->admin->id,
            'estado' => 'en preparacion',
            'tipo' => 'pedido',
            'fecha' => now(),
        ]);

        $producto = Producto::create(['nombre' => 'Concha', 'tipo_unidad' => 'unidad', 'activo' => true]);
        $presentacion = PresentacionProducto::create([
            'producto_id' => $producto->id,
            'nombre' => 'Chica',
            'cantidad_unidades' => 1,
            'precio' => 0.35,
            'stock_actual' => 100,
            'activo' => true,
        ]);

        $this->actingAs($this->caja)->post(route('ventas.store'), [
            'pedido_id' => $pedido->id,
            'items' => [
                ['presentacion_id' => $presentacion->id, 'cantidad' => 1],
            ],
            'pagos' => [
                ['metodo' => 'efectivo', 'monto' => 0.35],
            ],
        ]);

        $pedido->refresh();
        $this->assertEquals(PedidoEstado::Entregado->value, $pedido->estado->value);
    }

    public function test_venta_generates_numero_factura(): void
    {
        SerieDocumento::create([
            'serie' => '001',
            'descripcion' => 'Tiquete de venta',
            'ultimo_numero' => 0,
            'year' => now()->year,
            'activo' => true,
        ]);

        $producto = Producto::create(['nombre' => 'Concha', 'tipo_unidad' => 'unidad', 'activo' => true]);
        $presentacion = PresentacionProducto::create([
            'producto_id' => $producto->id,
            'nombre' => 'Chica',
            'cantidad_unidades' => 1,
            'precio' => 0.35,
            'stock_actual' => 100,
            'activo' => true,
        ]);

        $this->actingAs($this->caja)->post(route('ventas.store'), [
            'items' => [
                ['presentacion_id' => $presentacion->id, 'cantidad' => 1],
            ],
            'pagos' => [
                ['metodo' => 'efectivo', 'monto' => 0.35],
            ],
        ]);

        $this->assertDatabaseHas('ventas', ['numero_factura' => 'TIQU-001-00000001']);
    }

    public function test_venta_estado_pagada_after_creation(): void
    {
        $producto = Producto::create(['nombre' => 'Concha', 'tipo_unidad' => 'unidad', 'activo' => true]);
        $presentacion = PresentacionProducto::create([
            'producto_id' => $producto->id,
            'nombre' => 'Chica',
            'cantidad_unidades' => 1,
            'precio' => 0.35,
            'stock_actual' => 100,
            'activo' => true,
        ]);

        $this->actingAs($this->caja)->post(route('ventas.store'), [
            'items' => [
                ['presentacion_id' => $presentacion->id, 'cantidad' => 1],
            ],
            'pagos' => [
                ['metodo' => 'efectivo', 'monto' => 0.35],
            ],
        ]);

        $venta = Venta::first();
        $this->assertEquals(VentaEstado::Pagada->value, $venta->estado->value);
    }

    public function test_venta_index_lists_all_ventas(): void
    {
        $producto = Producto::create(['nombre' => 'Concha', 'tipo_unidad' => 'unidad', 'activo' => true]);
        $presentacion = PresentacionProducto::create([
            'producto_id' => $producto->id,
            'nombre' => 'Chica',
            'cantidad_unidades' => 1,
            'precio' => 0.35,
            'stock_actual' => 100,
            'activo' => true,
        ]);

        $this->actingAs($this->caja)->post(route('ventas.store'), [
            'items' => [
                ['presentacion_id' => $presentacion->id, 'cantidad' => 1],
            ],
            'pagos' => [
                ['metodo' => 'efectivo', 'monto' => 0.35],
            ],
        ]);

        $response = $this->actingAs($this->caja)->get(route('ventas.index'));

        $response->assertStatus(200);
    }
}
