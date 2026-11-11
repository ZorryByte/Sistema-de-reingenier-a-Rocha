<?php

namespace Tests\Feature;

use App\Models\PresentacionProducto;
use App\Models\Producto;
use App\Models\Receta;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductoCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create();
    }

    public function test_admin_can_access_productos_index(): void
    {
        $response = $this->actingAs($this->admin)->get(route('productos.index'));

        $response->assertStatus(200);
        $response->assertSee('Productos');
    }

    public function test_caja_cannot_access_productos_index(): void
    {
        $caja = User::factory()->create(['rol' => 'caja']);

        $response = $this->actingAs($caja)->get(route('productos.index'));

        $response->assertStatus(403);
    }

    public function test_admin_can_see_empty_productos_list(): void
    {
        $response = $this->actingAs($this->admin)->get(route('productos.index'));

        $response->assertStatus(200);
    }

    public function test_admin_can_see_productos_list(): void
    {
        $producto = Producto::create([
            'nombre' => 'Pan Dulce',
            'tipo_unidad' => 'unidad',
            'activo' => true,
        ]);

        PresentacionProducto::create([
            'producto_id' => $producto->id,
            'nombre' => 'Individual',
            'cantidad_unidades' => 1,
            'precio' => 0.50,
            'activo' => true,
        ]);

        $response = $this->actingAs($this->admin)->get(route('productos.index'));

        $response->assertStatus(200);
        $response->assertSee('Pan Dulce');
    }

    public function test_admin_can_view_producto_create_form(): void
    {
        $response = $this->actingAs($this->admin)->get(route('productos.create'));

        $response->assertStatus(200);
        $response->assertSee('Nuevo producto');
    }

    public function test_admin_can_create_producto_with_presentacion(): void
    {
        $response = $this->actingAs($this->admin)->post(route('productos.store'), [
            'nombre' => 'Concha',
            'tipo_unidad' => 'unidad',
            'activo' => true,
            'presentaciones' => [
                [
                    'nombre' => 'Chica',
                    'cantidad_unidades' => 1,
                    'precio' => 0.35,
                ],
                [
                    'nombre' => 'Grande',
                    'cantidad_unidades' => 2,
                    'precio' => 0.60,
                ],
            ],
        ]);

        $response->assertRedirect(route('productos.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('productos', ['nombre' => 'Concha']);

        $producto = Producto::where('nombre', 'Concha')->first();
        $this->assertNotNull($producto);
        $this->assertCount(2, $producto->presentaciones);
    }

    public function test_admin_can_create_producto_with_existing_independent_recipe(): void
    {
        $receta = Receta::create([
            'nombre' => 'Base concha',
            'tiempo_preparacion' => 45,
            'lote_unidades' => 12,
            'activo' => true,
        ]);

        $response = $this->actingAs($this->admin)->post(route('productos.store'), [
            'nombre' => 'Concha',
            'tipo_unidad' => 'unidad',
            'activo' => true,
            'receta_id' => $receta->id,
            'presentaciones' => [
                ['nombre' => 'Chica', 'cantidad_unidades' => 1, 'precio' => 0.35],
            ],
        ]);

        $response->assertRedirect(route('productos.index'));

        $producto = Producto::where('nombre', 'Concha')->first();
        $receta->refresh();

        $this->assertNotNull($producto);
        $this->assertEquals($producto->id, $receta->producto_id);
        $this->assertNull($receta->presentacion_id);
    }

    public function test_validation_fails_without_presentaciones(): void
    {
        $response = $this->actingAs($this->admin)->post(route('productos.store'), [
            'nombre' => 'Test Product',
            'tipo_unidad' => 'unidad',
            'presentaciones' => [],
        ]);

        $response->assertSessionHasErrors('presentaciones');
    }

    public function test_validation_fails_without_nombre(): void
    {
        $response = $this->actingAs($this->admin)->post(route('productos.store'), [
            'tipo_unidad' => 'unidad',
            'presentaciones' => [
                ['nombre' => 'Test', 'cantidad_unidades' => 1, 'precio' => 1.00],
            ],
        ]);

        $response->assertSessionHasErrors('nombre');
    }

    public function test_admin_can_view_producto_edit_form(): void
    {
        $producto = Producto::create([
            'nombre' => 'Donita',
            'tipo_unidad' => 'unidad',
            'activo' => true,
        ]);

        PresentacionProducto::create([
            'producto_id' => $producto->id,
            'nombre' => 'Regular',
            'cantidad_unidades' => 1,
            'precio' => 1.00,
            'activo' => true,
        ]);

        $response = $this->actingAs($this->admin)->get(route('productos.edit', $producto));

        $response->assertStatus(200);
        $response->assertSee('Donita');
    }

    public function test_admin_can_update_producto(): void
    {
        $producto = Producto::create([
            'nombre' => 'Oreja',
            'tipo_unidad' => 'unidad',
            'activo' => true,
        ]);

        PresentacionProducto::create([
            'producto_id' => $producto->id,
            'nombre' => 'Mediana',
            'cantidad_unidades' => 1,
            'precio' => 1.50,
            'activo' => true,
        ]);

        $response = $this->actingAs($this->admin)->patch(route('productos.update', $producto), [
            'nombre' => 'Oreja Actualizada',
            'tipo_unidad' => 'unidad',
            'activo' => true,
            'presentaciones' => [
                ['id' => $producto->presentaciones()->first()->id, 'nombre' => 'Mediana', 'cantidad_unidades' => 1, 'precio' => 2.00],
            ],
        ]);

        $response->assertRedirect(route('productos.index'));
        $response->assertSessionHas('success');

        $producto->refresh();
        $this->assertEquals('Oreja Actualizada', $producto->nombre);
    }

    public function test_admin_can_add_presentacion_to_existing_product(): void
    {
        $producto = Producto::create([
            'nombre' => 'Cuerno',
            'tipo_unidad' => 'unidad',
            'activo' => true,
        ]);

        PresentacionProducto::create([
            'producto_id' => $producto->id,
            'nombre' => 'Normal',
            'cantidad_unidades' => 1,
            'precio' => 1.00,
            'activo' => true,
        ]);

        $response = $this->actingAs($this->admin)->patch(route('productos.update', $producto), [
            'nombre' => 'Cuerno',
            'tipo_unidad' => 'unidad',
            'activo' => true,
            'presentaciones' => [
                ['id' => $producto->presentaciones()->first()->id, 'nombre' => 'Normal', 'cantidad_unidades' => 1, 'precio' => 1.00],
                ['nombre' => ' jumbo ', 'cantidad_unidades' => 3, 'precio' => 2.50],
            ],
        ]);

        $response->assertRedirect(route('productos.index'));

        $this->assertCount(2, $producto->presentaciones()->get());
    }

    public function test_admin_can_attach_existing_independent_recipe_when_editing_product(): void
    {
        $producto = Producto::create([
            'nombre' => 'Cuerno',
            'tipo_unidad' => 'unidad',
            'activo' => true,
        ]);

        $presentacion = PresentacionProducto::create([
            'producto_id' => $producto->id,
            'nombre' => 'Normal',
            'cantidad_unidades' => 1,
            'precio' => 1.00,
            'activo' => true,
        ]);

        $receta = Receta::create([
            'nombre' => 'Base cuerno',
            'tiempo_preparacion' => 30,
            'lote_unidades' => 8,
            'activo' => true,
        ]);

        $response = $this->actingAs($this->admin)->patch(route('productos.update', $producto), [
            'nombre' => 'Cuerno',
            'tipo_unidad' => 'unidad',
            'activo' => true,
            'receta_id' => $receta->id,
            'presentaciones' => [
                ['id' => $presentacion->id, 'nombre' => 'Normal', 'cantidad_unidades' => 1, 'precio' => 1.00],
            ],
        ]);

        $response->assertRedirect(route('productos.index'));

        $receta->refresh();
        $this->assertEquals($producto->id, $receta->producto_id);
    }

    public function test_admin_can_deactivate_producto(): void
    {
        $producto = Producto::create([
            'nombre' => 'Churro',
            'tipo_unidad' => 'unidad',
            'activo' => true,
        ]);

        $response = $this->actingAs($this->admin)->delete(route('productos.destroy', $producto));

        $response->assertRedirect(route('productos.index'));
        $response->assertSessionHas('success');

        $producto->refresh();
        $this->assertFalse($producto->activo);
    }

    public function test_deactivated_product_hides_presentation(): void
    {
        $producto = Producto::create([
            'nombre' => 'Panocha',
            'tipo_unidad' => 'unidad',
            'activo' => true,
        ]);

        PresentacionProducto::create([
            'producto_id' => $producto->id,
            'nombre' => 'Regular',
            'cantidad_unidades' => 1,
            'precio' => 0.75,
            'activo' => true,
        ]);

        $this->actingAs($this->admin)->delete(route('productos.destroy', $producto));

        $producto->refresh();
        $this->assertFalse($producto->presentaciones()->first()->activo);
    }

    public function test_admin_can_view_producto_detail(): void
    {
        $producto = Producto::create([
            'nombre' => 'Biscol', // typo in original, keeping for consistency
            'tipo_unidad' => 'unidad',
            'activo' => true,
        ]);

        PresentacionProducto::create([
            'producto_id' => $producto->id,
            'nombre' => 'Familiar',
            'cantidad_unidades' => 5,
            'precio' => 3.50,
            'activo' => true,
        ]);

        $response = $this->actingAs($this->admin)->get(route('productos.show', $producto));

        $response->assertStatus(200);
        $response->assertSee('Biscol');
    }

    public function test_unauthenticated_user_cannot_access_productos(): void
    {
        $response = $this->get(route('productos.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_producto_activos_scope(): void
    {
        $activo = Producto::create(['nombre' => 'Activo', 'tipo_unidad' => 'unidad', 'activo' => true]);
        $inactivo = Producto::create(['nombre' => 'Inactivo', 'tipo_unidad' => 'unidad', 'activo' => false]);

        $activos = Producto::activos()->get();

        $this->assertTrue($activos->contains($activo));
        $this->assertFalse($activos->contains($inactivo));
    }

    public function test_presentacion_producto_activas_scope(): void
    {
        $producto = Producto::create(['nombre' => 'Test', 'tipo_unidad' => 'unidad', 'activo' => true]);

        PresentacionProducto::create([
            'producto_id' => $producto->id,
            'nombre' => 'Activa',
            'cantidad_unidades' => 1,
            'precio' => 1.00,
            'activo' => true,
        ]);

        PresentacionProducto::create([
            'producto_id' => $producto->id,
            'nombre' => 'Inactiva',
            'cantidad_unidades' => 1,
            'precio' => 1.00,
            'activo' => false,
        ]);

        $this->assertCount(1, $producto->presentacionesActivas);
        $this->assertCount(2, $producto->presentaciones);
    }
}
