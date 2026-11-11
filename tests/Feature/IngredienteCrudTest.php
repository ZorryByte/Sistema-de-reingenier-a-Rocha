<?php

namespace Tests\Feature;

use App\Models\Ingrediente;
use App\Models\KardexInventario;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\Receta;
use App\Models\RecetaDetalle;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IngredienteCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create();
    }

    public function test_admin_can_access_ingredientes_index(): void
    {
        $response = $this->actingAs($this->admin)->get(route('ingredientes.index'));

        $response->assertStatus(200);
        $response->assertSee('Ingredientes');
    }

    public function test_caja_cannot_access_ingredientes_index(): void
    {
        $caja = User::factory()->create(['rol' => 'caja']);

        $response = $this->actingAs($caja)->get(route('ingredientes.index'));

        $response->assertStatus(403);
    }

    public function test_admin_can_see_ingredientes_list(): void
    {
        Ingrediente::create([
            'nombre' => 'Harina de Trigo',
            'unidad_medida' => 'g',
            'stock_actual' => 100,
            'stock_minimo' => 20,
            'costo_unitario' => 0.50,
        ]);

        $response = $this->actingAs($this->admin)->get(route('ingredientes.index'));

        $response->assertStatus(200);
        $response->assertSee('Harina de Trigo');
    }

    public function test_admin_can_view_ingrediente_create_form(): void
    {
        $response = $this->actingAs($this->admin)->get(route('ingredientes.create'));

        $response->assertStatus(200);
        $response->assertSee('Nuevo ingrediente');
    }

    public function test_admin_can_create_ingrediente_with_stock_initial(): void
    {
        $proveedor = Proveedor::create(['nombre' => 'Proveedor Central']);

        $response = $this->actingAs($this->admin)->post(route('ingredientes.store'), [
            'nombre' => 'Azucar',
            'categoria' => 'endulzantes',
            'proveedor_id' => $proveedor->id,
            'unidad_medida' => 'g',
            'stock_actual' => 50,
            'stock_minimo' => 10,
            'costo_unitario' => 0.75,
        ]);

        $response->assertRedirect(route('ingredientes.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('ingredientes', [
            'nombre' => 'Azucar',
            'proveedor_id' => $proveedor->id,
            'unidad_medida' => 'g',
            'stock_actual' => 50,
        ]);

        $this->assertDatabaseHas('kardex_inventario', [
            'tipo' => 'entrada',
            'cantidad' => 50,
            'notas' => 'Stock inicial al crear ingrediente.',
        ]);
    }

    public function test_admin_can_create_ingrediente_without_initial_stock(): void
    {
        $response = $this->actingAs($this->admin)->post(route('ingredientes.store'), [
            'nombre' => 'Sal',
            'categoria' => 'saborizantes',
            'unidad_medida' => 'g',
            'stock_actual' => 0,
            'stock_minimo' => 5,
            'costo_unitario' => 0.20,
        ]);

        $response->assertRedirect(route('ingredientes.index'));

        $this->assertDatabaseHas('ingredientes', ['nombre' => 'Sal']);
    }

    public function test_validation_fails_when_nombre_is_missing(): void
    {
        $response = $this->actingAs($this->admin)->post(route('ingredientes.store'), [
            'unidad_medida' => 'g',
            'stock_actual' => 10,
            'stock_minimo' => 5,
        ]);

        $response->assertSessionHasErrors('nombre');
    }

    public function test_validation_fails_when_unidad_medida_is_missing(): void
    {
        $response = $this->actingAs($this->admin)->post(route('ingredientes.store'), [
            'nombre' => 'Test Ingredient',
            'stock_actual' => 10,
        ]);

        $response->assertSessionHasErrors('unidad_medida');
    }

    public function test_admin_can_view_ingrediente_edit_form(): void
    {
        $ing = Ingrediente::create([
            'nombre' => 'Mantequilla',
            'unidad_medida' => 'g',
            'stock_actual' => 30,
            'stock_minimo' => 10,
            'costo_unitario' => 3.00,
        ]);

        $response = $this->actingAs($this->admin)->get(route('ingredientes.edit', $ing));

        $response->assertStatus(200);
        $response->assertSee('Editar ingrediente');
        $response->assertSee('Mantequilla');
    }

    public function test_admin_can_update_ingrediente(): void
    {
        $proveedor = Proveedor::create(['nombre' => 'Lacteos Unidos']);

        $ing = Ingrediente::create([
            'nombre' => 'Huevos',
            'categoria' => 'lacteos',
            'unidad_medida' => 'g',
            'stock_actual' => 100,
            'stock_minimo' => 20,
            'costo_unitario' => 0.50,
        ]);

        $response = $this->actingAs($this->admin)->patch(route('ingredientes.update', $ing), [
            'nombre' => 'Huevos Premium',
            'categoria' => 'lacteos',
            'proveedor_id' => $proveedor->id,
            'unidad_medida' => 'g',
            'stock_actual' => 150,
            'stock_minimo' => 25,
            'costo_unitario' => 0.60,
        ]);

        $response->assertRedirect(route('ingredientes.index'));
        $response->assertSessionHas('success');

        $ing->refresh();
        $this->assertEquals('Huevos Premium', $ing->nombre);
        $this->assertEquals(150, $ing->stock_actual);
        $this->assertEquals($proveedor->id, $ing->proveedor_id);
    }

    public function test_admin_can_update_stock_and_creates_movimiento_ajuste(): void
    {
        $ing = Ingrediente::create([
            'nombre' => 'Leche',
            'categoria' => 'lacteos',
            'unidad_medida' => 'g',
            'stock_actual' => 50,
            'stock_minimo' => 10,
            'costo_unitario' => 0.80,
        ]);

        $response = $this->actingAs($this->admin)->patch(route('ingredientes.update', $ing), [
            'nombre' => 'Leche',
            'categoria' => 'lacteos',
            'unidad_medida' => 'g',
            'stock_actual' => 75,
            'stock_minimo' => 10,
            'costo_unitario' => 0.80,
        ]);

        $response->assertRedirect(route('ingredientes.index'));

        $this->assertDatabaseHas('kardex_inventario', [
            'tipo' => 'ajuste',
            'ingrediente_id' => $ing->id,
        ]);
    }

    public function test_admin_can_delete_ingrediente_without_dependencies(): void
    {
        $ing = Ingrediente::create([
            'nombre' => 'Vainilla',
            'unidad_medida' => 'ml',
            'stock_actual' => 0,
            'stock_minimo' => 0,
        ]);

        $response = $this->actingAs($this->admin)->delete(route('ingredientes.destroy', $ing));

        $response->assertRedirect(route('ingredientes.index'));
        $response->assertSessionHas('success');

        $this->assertSoftDeleted('ingredientes', ['id' => $ing->id]);
    }

    public function test_admin_can_delete_ingrediente_with_multiple_recipes_and_deactivates_all(): void
    {
        $ing = Ingrediente::create([
            'nombre' => 'Azucar',
            'unidad_medida' => 'g',
            'stock_actual' => 0,
        ]);

        $producto1 = Producto::create(['nombre' => 'Pan', 'descripcion' => 'Test', 'precio' => 100]);
        $producto2 = Producto::create(['nombre' => 'Pastel', 'descripcion' => 'Test', 'precio' => 200]);

        $receta1 = Receta::create(['producto_id' => $producto1->id, 'nombre' => 'Receta Pan', 'activo' => true]);
        $receta2 = Receta::create(['producto_id' => $producto2->id, 'nombre' => 'Receta Pastel', 'activo' => true]);

        RecetaDetalle::create(['receta_id' => $receta1->id, 'ingrediente_id' => $ing->id, 'cantidad' => 50]);
        RecetaDetalle::create(['receta_id' => $receta2->id, 'ingrediente_id' => $ing->id, 'cantidad' => 100]);

        $response = $this->actingAs($this->admin)->delete(route('ingredientes.destroy', $ing));

        $response->assertRedirect(route('ingredientes.index'));
        $response->assertSessionHas('success');
        $this->assertSoftDeleted('ingredientes', ['id' => $ing->id]);

        $receta1->refresh();
        $receta2->refresh();
        $this->assertFalse($receta1->activo);
        $this->assertFalse($receta2->activo);
    }

    public function test_admin_can_soft_delete_ingrediente_with_movimientos(): void
    {
        $ing = Ingrediente::create([
            'nombre' => 'Azucar',
            'unidad_medida' => 'g',
            'stock_actual' => 50,
        ]);

        KardexInventario::create([
            'tipo' => 'entrada',
            'ingrediente_id' => $ing->id,
            'cantidad' => 50,
            'stock_anterior' => 0,
            'stock_resultante' => 50,
            'user_id' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->delete(route('ingredientes.destroy', $ing));

        $response->assertRedirect(route('ingredientes.index'));
        $response->assertSessionHas('success');
        $this->assertSoftDeleted('ingredientes', ['id' => $ing->id]);
    }

    public function test_caja_cannot_delete_ingrediente(): void
    {
        $caja = User::factory()->create(['rol' => 'caja']);
        $ing = Ingrediente::create([
            'nombre' => 'Azucar',
            'unidad_medida' => 'g',
            'stock_actual' => 0,
        ]);

        $response = $this->actingAs($caja)->delete(route('ingredientes.destroy', $ing));

        $response->assertStatus(403);
        $this->assertDatabaseHas('ingredientes', ['id' => $ing->id]);
    }

    public function test_unauthenticated_user_cannot_delete_ingrediente(): void
    {
        $ing = Ingrediente::create([
            'nombre' => 'Sal',
            'unidad_medida' => 'g',
            'stock_actual' => 0,
        ]);

        $response = $this->delete(route('ingredientes.destroy', $ing));

        $response->assertRedirect(route('login'));
        $this->assertDatabaseHas('ingredientes', ['id' => $ing->id]);
    }
}
