<?php

namespace Tests\Feature;

use App\Models\Ingrediente;
use App\Models\KardexInventario;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KardexInventarioTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['rol' => 'admin']);
    }

    public function test_index_requires_admin(): void
    {
        $caja = User::factory()->create(['rol' => 'caja']);
        $this->actingAs($caja)->get(route('control-inventario.index'))->assertForbidden();
    }

    public function test_index_displays_movements(): void
    {
        $ing = Ingrediente::create([
            'nombre' => 'Harina', 'unidad_medida' => 'g',
            'stock_actual' => 1000, 'stock_minimo' => 100, 'costo_unitario' => 0.01,
        ]);

        KardexInventario::create([
            'tipo' => 'entrada', 'ingrediente_id' => $ing->id,
            'cantidad' => 500, 'stock_anterior' => 500, 'stock_resultante' => 1000,
            'user_id' => $this->admin->id, 'origen_movimiento' => 'manual',
        ]);

        $this->actingAs($this->admin)
            ->get(route('control-inventario.index'))
            ->assertOk()
            ->assertSee('Harina');
    }

    public function test_index_is_audit_only_without_create_button(): void
    {
        $this->actingAs($this->admin)
            ->get(route('control-inventario.index'))
            ->assertOk()
            ->assertSee('Control de inventario')
            ->assertDontSee('Nuevo movimiento');
    }

    public function test_index_filters_by_tipo(): void
    {
        $ing = Ingrediente::create([
            'nombre' => 'Azucar', 'unidad_medida' => 'g',
            'stock_actual' => 500, 'stock_minimo' => 50, 'costo_unitario' => 0.02,
        ]);

        KardexInventario::create([
            'tipo' => 'entrada', 'ingrediente_id' => $ing->id,
            'cantidad' => 500, 'stock_anterior' => 0, 'stock_resultante' => 500,
            'user_id' => $this->admin->id, 'origen_movimiento' => 'manual',
        ]);

        KardexInventario::create([
            'tipo' => 'merma', 'ingrediente_id' => $ing->id,
            'cantidad' => 10, 'stock_anterior' => 500, 'stock_resultante' => 490,
            'user_id' => $this->admin->id, 'origen_movimiento' => 'manual',
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('control-inventario.index', ['tipo' => 'merma']));

        $response->assertOk();
        $this->assertCount(1, $response->viewData('movimientos'));
    }

    public function test_store_creates_entrada_movement(): void
    {
        $ing = Ingrediente::create([
            'nombre' => 'Mantequilla', 'unidad_medida' => 'g',
            'stock_actual' => 0, 'stock_minimo' => 100, 'costo_unitario' => 0.05,
        ]);

        $this->actingAs($this->admin)
            ->post(route('ingredientes.control-inventario.store'), [
                'tipo' => 'entrada',
                'ingrediente_id' => $ing->id,
                'modo_costo' => 'nuevo_precio',
                'categoria' => 'inventario_inicial',
                'cantidad' => 500,
                'precio_unitario' => 0.05,
                'fecha' => now()->format('Y-m-d'),
            ])
            ->assertRedirect(route('ingredientes.index'));

        $ing->refresh();
        $this->assertEquals(500, (float) $ing->stock_actual);

        $mov = KardexInventario::latest('id')->first();
        $this->assertEquals('entrada', $mov->tipo);
        $this->assertEquals(500, (float) $mov->stock_resultante);
        $this->assertEquals('ajuste', $mov->origen_movimiento);
        $this->assertEquals(0.05, (float) $mov->costo_resultante);
    }

    public function test_store_entrada_with_current_price_recalculates_unit_cost_from_same_total_price(): void
    {
        $ing = Ingrediente::create([
            'nombre' => 'Crema',
            'unidad_medida' => 'ml',
            'stock_actual' => 100,
            'stock_minimo' => 10,
            'costo_unitario' => 1.00,
        ]);

        $this->actingAs($this->admin)
            ->post(route('ingredientes.control-inventario.store'), [
                'tipo' => 'entrada',
                'ingrediente_id' => $ing->id,
                'modo_costo' => 'precio_actual',
                'categoria' => 'ajuste_conteo',
                'cantidad' => 100,
                'fecha' => now()->format('Y-m-d'),
            ])
            ->assertRedirect(route('ingredientes.index'));

        $ing->refresh();
        $this->assertEquals(200, (float) $ing->stock_actual);
        $this->assertEquals(0.50, (float) $ing->costo_unitario);

        $mov = KardexInventario::latest('id')->first();
        $this->assertEquals(1.00, (float) $mov->costo_anterior);
        $this->assertEquals(0.50, (float) $mov->costo_resultante);
    }

    public function test_store_entrada_with_new_price_recalculates_average_cost(): void
    {
        $ing = Ingrediente::create([
            'nombre' => 'Chocolate', 'unidad_medida' => 'g',
            'stock_actual' => 100, 'stock_minimo' => 10, 'costo_unitario' => 2.00,
        ]);

        $this->actingAs($this->admin)
            ->post(route('ingredientes.control-inventario.store'), [
                'tipo' => 'entrada',
                'ingrediente_id' => $ing->id,
                'modo_costo' => 'nuevo_precio',
                'categoria' => 'ajuste_conteo',
                'cantidad' => 100,
                'precio_unitario' => 3,
                'fecha' => now()->format('Y-m-d'),
            ])
            ->assertRedirect(route('ingredientes.index'));

        $ing->refresh();
        $this->assertEquals(200, (float) $ing->stock_actual);
        $this->assertEquals(2.50, (float) $ing->costo_unitario);

        $mov = KardexInventario::latest('id')->first();
        $this->assertEquals(2.00, (float) $mov->costo_anterior);
        $this->assertEquals(2.50, (float) $mov->costo_resultante);
    }

    public function test_store_entrada_requires_cost_mode(): void
    {
        $ing = Ingrediente::create([
            'nombre' => 'Nuez', 'unidad_medida' => 'g',
            'stock_actual' => 10, 'stock_minimo' => 5, 'costo_unitario' => 1.00,
        ]);

        $this->actingAs($this->admin)
            ->post(route('ingredientes.control-inventario.store'), [
                'tipo' => 'entrada',
                'ingrediente_id' => $ing->id,
                'categoria' => 'inventario_inicial',
                'cantidad' => 10,
                'fecha' => now()->format('Y-m-d'),
            ])
            ->assertSessionHasErrors('modo_costo');
    }

    public function test_ingredient_movement_create_form_is_available(): void
    {
        $this->actingAs($this->admin)
            ->get(route('ingredientes.control-inventario.create'))
            ->assertOk()
            ->assertSee('Inventario de ingredientes')
            ->assertSee('Registrar entrada o merma');
    }

    public function test_ingredient_movement_create_form_can_preselect_ingredient(): void
    {
        $ing = Ingrediente::create([
            'nombre' => 'Harina integral', 'unidad_medida' => 'g',
            'stock_actual' => 1000, 'stock_minimo' => 100, 'costo_unitario' => 0.01,
        ]);

        $this->actingAs($this->admin)
            ->get(route('ingredientes.control-inventario.create', ['ingrediente_id' => $ing->id]))
            ->assertOk()
            ->assertSee('Harina integral')
            ->assertSee('value="'.$ing->id.'"', false)
            ->assertSee('selected', false);
    }

    public function test_store_entrada_rejects_cost_when_price_stays_current(): void
    {
        $ing = Ingrediente::create([
            'nombre' => 'Miel', 'unidad_medida' => 'ml',
            'stock_actual' => 10, 'stock_minimo' => 5, 'costo_unitario' => 1.00,
        ]);

        $this->actingAs($this->admin)
            ->post(route('ingredientes.control-inventario.store'), [
                'tipo' => 'entrada',
                'ingrediente_id' => $ing->id,
                'modo_costo' => 'precio_actual',
                'categoria' => 'ajuste_conteo',
                'cantidad' => 10,
                'precio_unitario' => 9.9,
                'fecha' => now()->format('Y-m-d'),
            ])
            ->assertSessionHasErrors('precio_unitario');
    }

    public function test_store_merma_blocks_if_insufficient_stock(): void
    {
        $ing = Ingrediente::create([
            'nombre' => 'Leche', 'unidad_medida' => 'ml',
            'stock_actual' => 50, 'stock_minimo' => 10, 'costo_unitario' => 0.01,
        ]);

        $this->actingAs($this->admin)
            ->post(route('ingredientes.control-inventario.store'), [
                'tipo' => 'merma',
                'ingrediente_id' => $ing->id,
                'cantidad' => 100,
                'fecha' => now()->format('Y-m-d'),
            ])
            ->assertSessionHas('error');
    }

    public function test_store_merma_records_cost_trace_and_allows_empty_date(): void
    {
        $ing = Ingrediente::create([
            'nombre' => 'Canela', 'unidad_medida' => 'g',
            'stock_actual' => 100, 'stock_minimo' => 10, 'costo_unitario' => 1.25,
        ]);

        $this->actingAs($this->admin)
            ->post(route('ingredientes.control-inventario.store'), [
                'tipo' => 'merma',
                'ingrediente_id' => $ing->id,
                'cantidad' => 20,
            ])
            ->assertRedirect(route('ingredientes.index'));

        $ing->refresh();
        $this->assertEquals(80, (float) $ing->stock_actual);
        $this->assertEquals(1.25, (float) $ing->costo_unitario);

        $mov = KardexInventario::latest('id')->first();
        $this->assertEquals('merma', $mov->tipo);
        $this->assertEquals(100, (float) $mov->stock_anterior);
        $this->assertEquals(80, (float) $mov->stock_resultante);
        $this->assertEquals(1.25, (float) $mov->costo_anterior);
        $this->assertEquals(1.25, (float) $mov->costo_resultante);
        $this->assertNotNull($mov->fecha);
    }

    public function test_store_rejects_unknown_ingredient_entity(): void
    {
        $this->actingAs($this->admin)
            ->post(route('ingredientes.control-inventario.store'), [
                'tipo' => 'merma',
                'ingrediente_id' => 999999,
                'cantidad' => 1,
            ])
            ->assertSessionHasErrors('ingrediente_id');
    }

    public function test_show_displays_movement_detail(): void
    {
        $ing = Ingrediente::create([
            'nombre' => 'Huevo', 'unidad_medida' => 'unidad',
            'stock_actual' => 100, 'stock_minimo' => 10, 'costo_unitario' => 5,
        ]);

        $mov = KardexInventario::create([
            'tipo' => 'entrada', 'ingrediente_id' => $ing->id,
            'cantidad' => 100, 'stock_anterior' => 0, 'stock_resultante' => 100,
            'user_id' => $this->admin->id, 'origen_movimiento' => 'manual',
        ]);

        $this->actingAs($this->admin)
            ->get(route('control-inventario.show', $mov))
            ->assertOk()
            ->assertSee('Huevo')
            ->assertSee('Entrada');
    }

    public function test_revertir_marks_movement_as_reverted(): void
    {
        $ing = Ingrediente::create([
            'nombre' => 'Sal', 'unidad_medida' => 'g',
            'stock_actual' => 900, 'stock_minimo' => 100, 'costo_unitario' => 0.005,
        ]);

        $mov = KardexInventario::create([
            'tipo' => 'merma', 'ingrediente_id' => $ing->id,
            'cantidad' => 100, 'stock_anterior' => 1000, 'stock_resultante' => 900,
            'user_id' => $this->admin->id, 'origen_movimiento' => 'manual',
            'reversible' => true,
        ]);

        $this->actingAs($this->admin)
            ->post(route('control-inventario.revertir', $mov), [
                'restaurar_stock' => true,
            ])
            ->assertRedirect(route('control-inventario.index'));

        $mov->refresh();
        $this->assertNotNull($mov->reverted_at);

        $ing->refresh();
        $this->assertEquals(1000, (float) $ing->stock_actual);
    }

    public function test_revertir_without_restoring_stock(): void
    {
        $ing = Ingrediente::create([
            'nombre' => 'Vainilla', 'unidad_medida' => 'ml',
            'stock_actual' => 900, 'stock_minimo' => 100, 'costo_unitario' => 0.5,
        ]);

        $mov = KardexInventario::create([
            'tipo' => 'merma', 'ingrediente_id' => $ing->id,
            'cantidad' => 100, 'stock_anterior' => 1000, 'stock_resultante' => 900,
            'user_id' => $this->admin->id, 'origen_movimiento' => 'manual',
            'reversible' => true,
        ]);

        $this->actingAs($this->admin)
            ->post(route('control-inventario.revertir', $mov), [
                'restaurar_stock' => false,
            ])
            ->assertRedirect(route('control-inventario.index'));

        $mov->refresh();
        $this->assertNotNull($mov->reverted_at);

        $ing->refresh();
        $this->assertEquals(900, (float) $ing->stock_actual);
    }

    public function test_revertir_entrada_restores_stock_and_cost(): void
    {
        $ing = Ingrediente::create([
            'nombre' => 'Almendra', 'unidad_medida' => 'g',
            'stock_actual' => 200, 'stock_minimo' => 10, 'costo_unitario' => 2.50,
        ]);

        $mov = KardexInventario::create([
            'tipo' => 'entrada',
            'ingrediente_id' => $ing->id,
            'cantidad' => 100,
            'stock_anterior' => 100,
            'stock_resultante' => 200,
            'costo_anterior' => 2.00,
            'costo_resultante' => 2.50,
            'user_id' => $this->admin->id,
            'origen_movimiento' => 'manual',
            'reversible' => true,
        ]);

        $this->actingAs($this->admin)
            ->post(route('control-inventario.revertir', $mov), [
                'restaurar_stock' => true,
            ])
            ->assertRedirect(route('control-inventario.index'));

        $mov->refresh();
        $ing->refresh();

        $this->assertNotNull($mov->reverted_at);
        $this->assertEquals(100, (float) $ing->stock_actual);
        $this->assertEquals(2.00, (float) $ing->costo_unitario);

        $ajuste = KardexInventario::latest('id')->first();
        $this->assertEquals('ajuste', $ajuste->tipo);
        $this->assertEquals(200, (float) $ajuste->stock_anterior);
        $this->assertEquals(100, (float) $ajuste->stock_resultante);
        $this->assertEquals(2.50, (float) $ajuste->costo_anterior);
        $this->assertEquals(2.00, (float) $ajuste->costo_resultante);
    }

    public function test_historial_shows_ingredient_movements(): void
    {
        $ing = Ingrediente::create([
            'nombre' => 'Cacao', 'unidad_medida' => 'g',
            'stock_actual' => 500, 'stock_minimo' => 50, 'costo_unitario' => 0.1,
        ]);

        KardexInventario::create([
            'tipo' => 'entrada', 'ingrediente_id' => $ing->id,
            'cantidad' => 500, 'stock_anterior' => 0, 'stock_resultante' => 500,
            'user_id' => $this->admin->id, 'origen_movimiento' => 'manual',
        ]);

        $this->actingAs($this->admin)
            ->get(route('ingredientes.historial', $ing))
            ->assertOk()
            ->assertSee('Cacao');
    }

    public function test_scope_activos_excludes_reverted(): void
    {
        $ing = Ingrediente::create([
            'nombre' => 'Test', 'unidad_medida' => 'g',
            'stock_actual' => 100, 'stock_minimo' => 10, 'costo_unitario' => 0.01,
        ]);

        KardexInventario::create([
            'tipo' => 'entrada', 'ingrediente_id' => $ing->id,
            'cantidad' => 100, 'stock_anterior' => 0, 'stock_resultante' => 100,
            'user_id' => $this->admin->id, 'origen_movimiento' => 'manual',
        ]);

        $reverted = KardexInventario::create([
            'tipo' => 'merma', 'ingrediente_id' => $ing->id,
            'cantidad' => 10, 'stock_anterior' => 100, 'stock_resultante' => 90,
            'user_id' => $this->admin->id, 'origen_movimiento' => 'manual',
            'reverted_at' => now(),
        ]);

        $activos = KardexInventario::activos()->get();
        $this->assertCount(1, $activos);
        $this->assertNotEquals($reverted->id, $activos->first()->id);
    }
}
