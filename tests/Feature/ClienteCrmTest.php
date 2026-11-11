<?php

namespace Tests\Feature;

use App\Enums\PedidoEstado;
use App\Models\Cliente;
use App\Models\Pedido;
use App\Models\PresentacionProducto;
use App\Models\Producto;
use App\Models\User;
use App\Models\Venta;
use App\Models\VentaDetalle;
use App\Services\HistorialCliente;
use Database\Seeders\CuentasContablesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClienteCrmTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $caja;

    private Cliente $cliente;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CuentasContablesSeeder::class);
        $this->admin = User::factory()->create(['rol' => 'admin', 'activo' => true]);
        $this->caja = User::factory()->create(['rol' => 'caja', 'activo' => true]);
        $this->cliente = Cliente::create(['nombre' => 'Ana López', 'telefono' => '+50322220000']);
    }

    public function test_caja_reutiliza_el_numero_normalizado_y_no_puede_administrar(): void
    {
        $this->get(route('clientes.buscar', ['q' => 'Ana']))->assertRedirect(route('login'));
        $this->actingAs($this->caja)->get(route('clientes.index'))->assertForbidden();
        $this->get(route('clientes.show', $this->cliente))->assertForbidden();
        $this->patch(route('clientes.archivar', $this->cliente))->assertForbidden();

        $this->postJson(route('clientes.alta-rapida'), ['nombre' => 'Otro nombre', 'telefono' => '2222-0000'])
            ->assertOk()->assertExactJson(['id' => $this->cliente->id, 'nombre' => 'Ana López', 'telefono' => '+503 2222-0000']);

        $this->postJson(route('clientes.alta-rapida'), ['nombre' => '  Eva   Pérez  ', 'telefono' => '+502 5555-1234'])
            ->assertCreated()->assertJsonPath('nombre', 'Eva Pérez')->assertJsonPath('telefono', '+50255551234');

        $this->postJson(route('clientes.alta-rapida'), ['nombre' => 'Eva Pérez', 'telefono' => '50255551234'])
            ->assertUnprocessable()->assertJsonValidationErrors('telefono');
        $this->getJson(route('clientes.buscar', ['q' => '2222-00']))
            ->assertOk()->assertJsonCount(1)->assertJsonPath('0.id', $this->cliente->id);
        $this->assertDatabaseCount('clientes', 2);
    }

    public function test_archivo_excluye_de_busqueda_y_conserva_el_contacto(): void
    {
        $this->actingAs($this->admin)->get(route('clientes.index'))->assertOk()->assertSee('Ana López');
        $this->get(route('clientes.edit', $this->cliente))->assertOk();
        $this->patch(route('clientes.archivar', $this->cliente))->assertRedirect();
        $this->get(route('clientes.index', ['estado' => 'archivados']))->assertOk()->assertSee('Ana López');
        $this->actingAs($this->caja)->getJson(route('clientes.buscar', ['q' => 'Ana']))->assertExactJson([]);
        $this->postJson(route('clientes.alta-rapida'), ['nombre' => 'Ana', 'telefono' => '22220000'])
            ->assertUnprocessable()->assertJsonValidationErrors('telefono');

        $this->actingAs($this->admin)->patch(route('clientes.reactivar', $this->cliente))->assertRedirect();
        $this->assertNull($this->cliente->refresh()->archivado_at);
        $this->get(route('clientes.show', $this->cliente))->assertOk()->assertSee('Sin compras');
    }

    public function test_asociacion_se_valida_y_la_entrega_hereda_al_cliente_archivado(): void
    {
        $presentacion = $this->presentacion();
        $datos = [
            'tipo' => 'pedido',
            'fecha_entrega_estimada' => now()->addDay()->toDateTimeString(),
            'items' => [['presentacion_id' => $presentacion->id, 'cantidad' => 1]],
        ];

        $this->actingAs($this->caja)->post(route('pedidos.store'), $datos)->assertSessionHasErrors('cliente_id');
        $this->post(route('pedidos.store'), $datos + ['cliente_id' => 999])->assertSessionHasErrors('cliente_id');

        $pedido = Pedido::factory()->create(['user_id' => $this->caja->id]);
        $this->put(route('pedidos.update', $pedido), $datos + ['cliente_id' => $this->cliente->id])->assertSessionHasNoErrors();
        $this->assertSame($this->cliente->id, $pedido->refresh()->cliente_id);
        $this->assertSame($this->cliente->id, $pedido->historial()->firstOrFail()->cambios['cliente_id_nuevo']);

        $this->actingAs($this->admin)->patch(route('clientes.archivar', $this->cliente))->assertRedirect();
        $this->actingAs($this->caja)->post(route('pedidos.store'), $datos + ['cliente_id' => $this->cliente->id])
            ->assertSessionHasErrors('cliente_id');

        $this->post(route('pedidos.entregar', $pedido), ['pagos' => [['metodo' => 'efectivo', 'monto' => 10]]])
            ->assertSessionHasNoErrors();

        $venta = $pedido->venta()->firstOrFail();
        $this->assertNull($venta->cliente_id);
        $this->assertSame($this->cliente->id, $venta->clienteAsociado()->id);
        $this->assertSame(PedidoEstado::Entregado, $pedido->refresh()->estado);
        $this->get(route('ventas.show', $venta))->assertOk()->assertSee('Ana López');
    }

    public function test_venta_directa_con_cliente_y_ficha_calculada_sin_cancelaciones(): void
    {
        $presentacion = $this->presentacion();
        $datos = [
            'cliente_id' => $this->cliente->id,
            'items' => [['presentacion_id' => $presentacion->id, 'cantidad' => 1.25, 'descuento' => 2.50]],
            'pagos' => [['metodo' => 'efectivo', 'monto' => 10]],
        ];

        $this->actingAs($this->caja)->post(route('ventas.store'), $datos)->assertSessionHasNoErrors();
        $this->assertDatabaseHas('ventas', ['cliente_id' => $this->cliente->id, 'total' => 10]);

        $pedido = Pedido::factory()->create(['user_id' => $this->caja->id, 'cliente_id' => $this->cliente->id]);
        $entrega = Venta::create(['pedido_id' => $pedido->id, 'user_id' => $this->caja->id, 'total' => 12, 'estado' => 'pagada']);
        $cancelada = Venta::create(['cliente_id' => $this->cliente->id, 'user_id' => $this->caja->id, 'total' => 75, 'estado' => 'cancelada']);

        foreach ([$entrega->id => 1.5, $cancelada->id => 7.5] as $id => $cantidad) {
            VentaDetalle::create([
                'venta_id' => $id, 'presentacion_id' => $presentacion->id,
                'cantidad' => $cantidad, 'precio_unitario' => 10, 'subtotal' => 15,
            ]);
        }

        $ficha = app(HistorialCliente::class)->consultar($this->cliente);
        $this->assertEquals(2, $ficha['compras']->cantidad);
        $this->assertEquals(22, $ficha['compras']->total);
        $this->assertEquals(2.75, $ficha['articulos']->sole()->unidades);
        $this->assertSame(3, $ficha['ventas']->total());
        $this->assertSame(1, $ficha['pedidos']->total());

        $this->actingAs($this->admin)->get(route('clientes.show', $this->cliente))
            ->assertOk()->assertSee('$22.00')->assertSee('Cancelada')->assertSee('2.75');
    }

    private function presentacion(): PresentacionProducto
    {
        $producto = Producto::create(['nombre' => 'Croissant', 'activo' => true]);

        return PresentacionProducto::create([
            'producto_id' => $producto->id, 'nombre' => 'Individual', 'cantidad_unidades' => 1,
            'precio' => 10, 'stock_actual' => 100, 'activo' => true,
        ]);
    }
}
