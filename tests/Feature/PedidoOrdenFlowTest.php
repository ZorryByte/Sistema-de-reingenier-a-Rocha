<?php

namespace Tests\Feature;

use App\Actions\CrearPedido;
use App\Enums\PedidoEstado;
use App\Models\Cliente;
use App\Models\KardexInventario;
use App\Models\Pedido;
use App\Models\PresentacionProducto;
use App\Models\Producto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class PedidoOrdenFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $caja;

    private PresentacionProducto $presentacion;

    protected function setUp(): void
    {
        parent::setUp();

        $this->caja = User::factory()->create([
            'rol' => 'caja',
            'activo' => true,
        ]);

        $producto = Producto::create([
            'nombre' => 'Croissant',
            'activo' => true,
        ]);

        $this->presentacion = PresentacionProducto::create([
            'producto_id' => $producto->id,
            'nombre' => 'Unidad',
            'cantidad_unidades' => 1,
            'precio' => 12.50,
            'stock_actual' => 10,
            'activo' => true,
        ]);
    }

    public function test_pedido_y_orden_se_crean_desde_la_misma_ruta(): void
    {
        $fecha = now()->addDay()->format('Y-m-d H:i');
        $cliente = Cliente::create(['nombre' => 'Ana López', 'telefono' => '+50322220000']);

        $this->actingAs($this->caja)->post(route('pedidos.store'), [
            'tipo' => 'pedido',
            'fecha_entrega_estimada' => $fecha,
            'cliente_id' => $cliente->id,
            'items' => [[
                'presentacion_id' => $this->presentacion->id,
                'cantidad' => 1,
            ]],
        ])->assertRedirect();

        $this->actingAs($this->caja)->post(route('pedidos.store'), [
            'tipo' => 'orden',
            'items' => [[
                'presentacion_id' => $this->presentacion->id,
                'cantidad' => 2,
            ]],
        ])->assertRedirect();

        $this->assertDatabaseHas('pedidos', ['tipo' => 'pedido', 'cliente_id' => $cliente->id]);
        $this->assertDatabaseHas('pedidos', [
            'tipo' => 'orden',
            'fecha_entrega_estimada' => null,
        ]);
    }

    public function test_pedido_requiere_fecha_pero_orden_no(): void
    {
        $items = [[
            'presentacion_id' => $this->presentacion->id,
            'cantidad' => 1,
        ]];

        $this->actingAs($this->caja)->post(route('pedidos.store'), [
            'tipo' => 'pedido',
            'items' => $items,
        ])->assertSessionHasErrors('fecha_entrega_estimada');

        $this->actingAs($this->caja)->post(route('pedidos.store'), [
            'tipo' => 'orden',
            'items' => $items,
        ])->assertSessionHasNoErrors();
    }

    public function test_listado_filtra_por_tipo_y_estado_en_la_misma_ruta(): void
    {
        Pedido::factory()->create(['user_id' => $this->caja->id]);
        $orden = Pedido::factory()->orden()->create(['user_id' => $this->caja->id]);

        $this->actingAs($this->caja)
            ->get(route('pedidos.index', ['tipo' => 'orden']))
            ->assertOk()
            ->assertSee("#{$orden->id}")
            ->assertDontSee('Pedido programado');

        $this->actingAs($this->caja)
            ->get(route('pedidos.index', ['tipo' => 'orden', 'estado' => PedidoEstado::EnPreparacion->value]))
            ->assertOk()
            ->assertSeeText('Comandas');
    }

    public function test_actualizacion_conserva_tipo_y_reemplaza_reserva_atomicamente(): void
    {
        $orden = app(CrearPedido::class)->execute([
            'tipo' => 'orden',
            'items' => [[
                'presentacion_id' => $this->presentacion->id,
                'cantidad' => 1,
            ]],
        ], $this->caja->id);

        $this->actingAs($this->caja)->put(route('pedidos.update', $orden), [
            'notas' => 'Sin azucar',
            'items' => [[
                'presentacion_id' => $this->presentacion->id,
                'cantidad' => 3,
            ]],
        ])->assertRedirect(route('pedidos.show', $orden));

        $orden->refresh();
        $this->assertTrue($orden->esOrden());
        $this->assertNull($orden->fecha_entrega_estimada);
        $this->assertSame('3.00', $orden->detalles()->first()->cantidad);
        $this->assertSame(1, KardexInventario::where('pedido_id', $orden->id)
            ->where('origen_movimiento', 'reserva')->whereNull('reverted_at')->count());
        $this->assertEquals(3, KardexInventario::where('pedido_id', $orden->id)
            ->where('origen_movimiento', 'reserva')->whereNull('reverted_at')->value('cantidad'));
    }

    public function test_actualizacion_sin_stock_revierte_detalles_y_reserva_original(): void
    {
        $this->presentacion->update(['stock_actual' => 2]);
        $cliente = Cliente::create(['nombre' => 'Ana López', 'telefono' => '+50322220000']);
        $pedido = app(CrearPedido::class)->execute([
            'tipo' => 'pedido',
            'cliente_id' => $cliente->id,
            'fecha_entrega_estimada' => now()->addDay(),
            'items' => [[
                'presentacion_id' => $this->presentacion->id,
                'cantidad' => 1,
            ]],
        ], $this->caja->id);

        $this->actingAs($this->caja)->from(route('pedidos.edit', $pedido))
            ->put(route('pedidos.update', $pedido), [
                'fecha_entrega_estimada' => now()->addDays(2)->format('Y-m-d H:i'),
                'items' => [[
                    'presentacion_id' => $this->presentacion->id,
                    'cantidad' => 3,
                ]],
            ])->assertRedirect(route('pedidos.edit', $pedido))
            ->assertSessionHas('error');

        $this->assertEquals(1, $pedido->detalles()->first()->cantidad);
        $this->assertSame(1, KardexInventario::where('pedido_id', $pedido->id)
            ->where('origen_movimiento', 'reserva')->whereNull('reverted_at')->count());
    }

    public function test_respuesta_parcial_refleja_cambios_de_otro_usuario(): void
    {
        $otroUsuario = User::factory()->create(['rol' => 'caja', 'activo' => true]);
        $orden = Pedido::factory()->orden()->create([
            'user_id' => $otroUsuario->id,
            'notas' => 'Creada por otro usuario',
        ]);

        $this->actingAs($this->caja)
            ->getJson(route('pedidos.index', ['tipo' => 'orden']))
            ->assertOk()
            ->assertJsonStructure(['html', 'synced_at', 'active_count'])
            ->assertJsonPath('interval_ms', 10000)
            ->assertJson(fn ($json) => $json->whereType('html', 'string')->etc());

        $response = $this->actingAs($this->caja)
            ->getJson(route('pedidos.index', ['tipo' => 'orden']));

        $this->assertStringContainsString("#{$orden->id}", $response->json('html'));
        $this->assertStringContainsString('Creada por otro usuario', $response->json('html'));
    }

    public function test_ruta_no_publica_eliminacion_fisica_ni_una_ruta_de_ordenes(): void
    {
        $this->assertFalse(Route::has('pedidos.destroy'));
        $this->assertFalse(Route::has('ordenes.index'));
    }

    public function test_listado_incluye_sondeo_de_diez_segundos_sin_recarga_completa(): void
    {
        $response = $this->actingAs($this->caja)->get(route('pedidos.index'));

        $response->assertOk()
            ->assertSee('window.setInterval(() => this.refresh(), 10000)', false)
            ->assertSee("'Accept': 'application/json'", false)
            ->assertDontSee('window.location.reload', false);
    }

    public function test_usuario_inactivo_no_puede_gestionar_pedidos(): void
    {
        $inactivo = User::factory()->create(['rol' => 'caja', 'activo' => false]);

        $this->actingAs($inactivo)
            ->get(route('pedidos.index'))
            ->assertForbidden();
    }

    public function test_no_acepta_presentaciones_duplicadas_en_un_mismo_registro(): void
    {
        $this->actingAs($this->caja)->post(route('pedidos.store'), [
            'tipo' => 'orden',
            'items' => [
                ['presentacion_id' => $this->presentacion->id, 'cantidad' => 1],
                ['presentacion_id' => $this->presentacion->id, 'cantidad' => 1],
            ],
        ])->assertSessionHasErrors('items.1.presentacion_id');
    }

    public function test_historial_registra_actor_y_transiciones_de_una_orden(): void
    {
        $orden = app(CrearPedido::class)->execute([
            'tipo' => 'orden',
            'items' => [[
                'presentacion_id' => $this->presentacion->id,
                'cantidad' => 1,
            ]],
        ], $this->caja->id);

        $this->actingAs($this->caja)
            ->post(route('pedidos.marcarLista', $orden))
            ->assertRedirect(route('pedidos.show', $orden));

        $this->actingAs($this->caja)
            ->patch(route('pedidos.cancelar', $orden))
            ->assertRedirect(route('pedidos.index'));

        $this->assertDatabaseHas('pedido_historial', [
            'pedido_id' => $orden->id,
            'user_id' => $this->caja->id,
            'evento' => 'creado',
        ]);
        $this->assertDatabaseHas('pedido_historial', [
            'pedido_id' => $orden->id,
            'evento' => 'orden_lista',
            'estado_nuevo' => PedidoEstado::OrdenLista->value,
        ]);
        $this->assertDatabaseHas('pedido_historial', [
            'pedido_id' => $orden->id,
            'evento' => 'cancelado',
            'estado_nuevo' => PedidoEstado::Cancelado->value,
        ]);
    }
}
