<?php

namespace Tests\Feature;

use App\Models\CategoriaReceta;
use App\Models\Ingrediente;
use App\Models\KardexInventario;
use App\Models\PresentacionProducto;
use App\Models\Producto;
use App\Models\Receta;
use App\Models\RecetaDetalle;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecetaCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private CategoriaReceta $categoria;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create();
        $this->categoria = CategoriaReceta::create([
            'nombre' => 'Panes',
            'slug' => 'panes',
        ]);
    }

    public function test_admin_can_access_recetas_index(): void
    {
        $response = $this->actingAs($this->admin)->get(route('recetas.index'));

        $response->assertStatus(200);
        $response->assertSee('Recetas');
    }

    public function test_caja_cannot_access_recetas_index(): void
    {
        $caja = User::factory()->create(['rol' => 'caja']);

        $response = $this->actingAs($caja)->get(route('recetas.index'));

        $response->assertStatus(403);
    }

    public function test_admin_can_see_empty_recetas_list(): void
    {
        $response = $this->actingAs($this->admin)->get(route('recetas.index'));

        $response->assertStatus(200);
    }

    public function test_admin_can_see_recetas_list(): void
    {
        $producto = Producto::create([
            'nombre' => 'Concha',
            'tipo_unidad' => 'unidad',
            'activo' => true,
        ]);

        Receta::create([
            'producto_id' => $producto->id,
            'nombre' => 'Receta Base Concha',
            'descripcion' => 'Receta default',
            'activo' => true,
        ]);

        $response = $this->actingAs($this->admin)->get(route('recetas.index'));

        $response->assertStatus(200);
        $response->assertSee('Receta Base Concha');
    }

    public function test_admin_can_view_receta_create_form(): void
    {
        $response = $this->actingAs($this->admin)->get(route('recetas.create'));

        $response->assertStatus(200);
        $response->assertSee('Nueva receta');
    }

    public function test_admin_can_create_receta_with_ingredientes(): void
    {
        $producto = Producto::create([
            'nombre' => 'Concha',
            'tipo_unidad' => 'unidad',
            'activo' => true,
        ]);

        $ing1 = Ingrediente::create([
            'nombre' => 'Harina',
            'unidad_medida' => 'g',
            'stock_actual' => 100,
            'stock_minimo' => 20,
            'costo_unitario' => 0.50,
        ]);

        $ing2 = Ingrediente::create([
            'nombre' => 'Azucar',
            'unidad_medida' => 'g',
            'stock_actual' => 50,
            'stock_minimo' => 10,
            'costo_unitario' => 0.75,
        ]);

        $response = $this->actingAs($this->admin)->post(route('recetas.store'), [
            'producto_id' => $producto->id,
            'nombre' => 'Receta Concha Simple',
            'categoria_receta_id' => $this->categoria->id,
            'descripcion' => 'Concha basica',
            'tiempo_preparacion' => 30,
            'lote_unidades' => 12,
            'ingredientes' => [
                ['ingrediente_id' => $ing1->id, 'cantidad' => 0.5],
                ['ingrediente_id' => $ing2->id, 'cantidad' => 0.3],
            ],
        ]);

        $response->assertRedirect(route('recetas.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('recetas', [
            'producto_id' => $producto->id,
            'nombre' => 'Receta Concha Simple',
        ]);

        $receta = Receta::where('nombre', 'Receta Concha Simple')->first();
        $this->assertNotNull($receta);
        $this->assertCount(2, $receta->detalles);
    }

    public function test_admin_can_create_receta_for_specific_presentacion(): void
    {
        $producto = Producto::create([
            'nombre' => 'Concha',
            'tipo_unidad' => 'unidad',
            'activo' => true,
        ]);

        $presentacion = PresentacionProducto::create([
            'producto_id' => $producto->id,
            'nombre' => 'Grande',
            'cantidad_unidades' => 2,
            'precio' => 0.60,
            'activo' => true,
        ]);

        $response = $this->actingAs($this->admin)->get(route('recetas.create').'?producto_id='.$producto->id);

        $response->assertStatus(200);
        $response->assertSee('Grande');
    }

    public function test_validation_fails_without_ingredientes(): void
    {
        $producto = Producto::create([
            'nombre' => 'Concha',
            'tipo_unidad' => 'unidad',
            'activo' => true,
        ]);

        $response = $this->actingAs($this->admin)->post(route('recetas.store'), [
            'producto_id' => $producto->id,
            'nombre' => 'Receta Sin Ingredientes',
            'categoria_receta_id' => $this->categoria->id,
            'descripcion' => 'Test',
            'ingredientes' => [],
        ]);

        $response->assertSessionHasErrors('ingredientes');
    }

    public function test_validation_fails_without_nombre(): void
    {
        $producto = Producto::create([
            'nombre' => 'Concha',
            'tipo_unidad' => 'unidad',
            'activo' => true,
        ]);

        $response = $this->actingAs($this->admin)->post(route('recetas.store'), [
            'producto_id' => $producto->id,
            'categoria_receta_id' => $this->categoria->id,
            'descripcion' => 'Test',
            'ingredientes' => [
                ['ingrediente_id' => 1, 'cantidad' => 0.5],
            ],
        ]);

        $response->assertSessionHasErrors('nombre');
    }

    public function test_admin_can_view_receta_edit_form(): void
    {
        $producto = Producto::create([
            'nombre' => 'Oreja',
            'tipo_unidad' => 'unidad',
            'activo' => true,
        ]);

        $receta = Receta::create([
            'producto_id' => $producto->id,
            'nombre' => 'Receta Oreja',
            'descripcion' => 'Test',
            'activo' => true,
        ]);

        $response = $this->actingAs($this->admin)->get(route('recetas.edit', $receta));

        $response->assertStatus(200);
        $response->assertSee('Receta Oreja');
    }

    public function test_admin_can_update_receta(): void
    {
        $producto = Producto::create([
            'nombre' => 'Cuerno',
            'tipo_unidad' => 'unidad',
            'activo' => true,
        ]);

        $ing = Ingrediente::create([
            'nombre' => 'Harina',
            'unidad_medida' => 'g',
            'stock_actual' => 100,
            'stock_minimo' => 20,
            'costo_unitario' => 0.50,
        ]);

        $receta = Receta::create([
            'producto_id' => $producto->id,
            'nombre' => 'Receta Cuerno Original',
            'descripcion' => 'Test',
            'activo' => true,
        ]);

        RecetaDetalle::create([
            'receta_id' => $receta->id,
            'ingrediente_id' => $ing->id,
            'cantidad' => 0.5,
        ]);

        $detalle = $receta->detalles()->first();

        $response = $this->actingAs($this->admin)->patch(route('recetas.update', $receta), [
            'nombre' => 'Receta Cuerno Actualizada',
            'categoria_receta_id' => $this->categoria->id,
            'descripcion' => 'Nueva descripcion',
            'activo' => true,
            'tiempo_preparacion' => 30,
            'lote_unidades' => 12,
            'ingredientes' => [
                ['ingrediente_id' => $ing->id, 'cantidad' => 0.6],
            ],
        ]);

        $response->assertRedirect(route('recetas.index'));
        $response->assertSessionHas('success');

        $receta->refresh();
        $this->assertEquals('Receta Cuerno Actualizada', $receta->nombre);
    }

    public function test_admin_can_deactivate_receta(): void
    {
        $producto = Producto::create([
            'nombre' => 'Churro',
            'tipo_unidad' => 'unidad',
            'activo' => true,
        ]);

        $receta = Receta::create([
            'producto_id' => $producto->id,
            'nombre' => 'Receta Churro',
            'activo' => true,
        ]);

        $response = $this->actingAs($this->admin)->delete(route('recetas.destroy', $receta));

        $response->assertRedirect(route('recetas.index'));
        $response->assertSessionHas('success');

        $receta->refresh();
        $this->assertFalse($receta->activo);
    }

    public function test_admin_can_view_receta_detail(): void
    {
        $producto = Producto::create([
            'nombre' => 'Donita',
            'tipo_unidad' => 'unidad',
            'activo' => true,
        ]);

        $ing = Ingrediente::create([
            'nombre' => 'Harina',
            'unidad_medida' => 'g',
            'stock_actual' => 100,
            'stock_minimo' => 20,
            'costo_unitario' => 0.50,
        ]);

        $receta = Receta::create([
            'producto_id' => $producto->id,
            'nombre' => 'Receta Donita',
            'descripcion' => 'Receta detallada',
            'activo' => true,
        ]);

        RecetaDetalle::create([
            'receta_id' => $receta->id,
            'ingrediente_id' => $ing->id,
            'cantidad' => 0.4,
        ]);

        $response = $this->actingAs($this->admin)->get(route('recetas.show', $receta));

        $response->assertStatus(200);
        $response->assertSee('Receta Donita');
        $response->assertSee('Harina');
    }

    public function test_unauthenticated_user_cannot_access_recetas(): void
    {
        $response = $this->get(route('recetas.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_receta_activas_scope(): void
    {
        $producto = Producto::create([
            'nombre' => 'Test',
            'tipo_unidad' => 'unidad',
            'activo' => true,
        ]);

        Receta::create([
            'producto_id' => $producto->id,
            'nombre' => 'Activa',
            'activo' => true,
        ]);

        Receta::create([
            'producto_id' => $producto->id,
            'nombre' => 'Inactiva',
            'activo' => false,
        ]);

        $activas = Receta::activas()->get();

        $this->assertTrue($activas->contains('nombre', 'Activa'));
        $this->assertFalse($activas->contains('nombre', 'Inactiva'));
    }

    public function test_receta_default_scope(): void
    {
        $producto = Producto::create([
            'nombre' => 'Test',
            'tipo_unidad' => 'unidad',
            'activo' => true,
        ]);

        $presentacion = PresentacionProducto::create([
            'producto_id' => $producto->id,
            'nombre' => 'Grande',
            'cantidad_unidades' => 2,
            'precio' => 0.60,
            'activo' => true,
        ]);

        Receta::create([
            'producto_id' => $producto->id,
            'presentacion_id' => null,
            'nombre' => 'Default',
            'activo' => true,
        ]);

        Receta::create([
            'producto_id' => $producto->id,
            'presentacion_id' => $presentacion->id,
            'nombre' => 'Por Presentacion',
            'activo' => true,
        ]);

        $defaults = Receta::default()->get();

        $this->assertTrue($defaults->contains('nombre', 'Default'));
        $this->assertFalse($defaults->contains('nombre', 'Por Presentacion'));
    }

    public function test_admin_can_create_receta_sin_producto(): void
    {
        $ing = Ingrediente::create([
            'nombre' => 'Harina',
            'unidad_medida' => 'g',
            'stock_actual' => 100,
            'stock_minimo' => 20,
            'costo_unitario' => 0.50,
        ]);

        $response = $this->actingAs($this->admin)->post(route('recetas.store'), [
            'nombre' => 'Receta Independiente',
            'categoria_receta_id' => $this->categoria->id,
            'descripcion' => 'Sin producto asociado',
            'tiempo_preparacion' => 30,
            'lote_unidades' => 12,
            'ingredientes' => [
                ['ingrediente_id' => $ing->id, 'cantidad' => 0.5],
            ],
        ]);

        $response->assertRedirect(route('recetas.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('recetas', [
            'producto_id' => null,
            'nombre' => 'Receta Independiente',
        ]);

        $receta = Receta::where('nombre', 'Receta Independiente')->first();
        $this->assertNull($receta->producto_id);
        $this->assertTrue($receta->esIndependiente());
    }

    public function test_validacion_presentacion_sin_producto_rechazada(): void
    {
        $producto = Producto::create([
            'nombre' => 'Concha',
            'tipo_unidad' => 'unidad',
            'activo' => true,
        ]);

        $presentacion = PresentacionProducto::create([
            'producto_id' => $producto->id,
            'nombre' => 'Grande',
            'cantidad_unidades' => 2,
            'precio' => 0.60,
            'activo' => true,
        ]);

        $ing = Ingrediente::create([
            'nombre' => 'Harina',
            'unidad_medida' => 'g',
            'stock_actual' => 100,
            'stock_minimo' => 20,
            'costo_unitario' => 0.50,
        ]);

        $response = $this->actingAs($this->admin)->post(route('recetas.store'), [
            'producto_id' => null,
            'presentacion_id' => $presentacion->id,
            'nombre' => 'Receta Invalida',
            'categoria_receta_id' => $this->categoria->id,
            'tiempo_preparacion' => 30,
            'lote_unidades' => 12,
            'ingredientes' => [
                ['ingrediente_id' => $ing->id, 'cantidad' => 0.5],
            ],
        ]);

        $response->assertSessionHasErrors('presentacion_id');
    }

    public function test_activar_receta_con_conflicto_muestra_error(): void
    {
        $producto = Producto::create([
            'nombre' => 'Concha',
            'tipo_unidad' => 'unidad',
            'activo' => true,
        ]);

        $recetaActiva = Receta::create([
            'producto_id' => $producto->id,
            'nombre' => 'Receta Activa',
            'activo' => true,
        ]);

        $recetaInactiva = Receta::create([
            'producto_id' => $producto->id,
            'nombre' => 'Receta Inactiva',
            'activo' => false,
        ]);

        $response = $this->actingAs($this->admin)->patch(route('recetas.activar', $recetaInactiva));

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertStringContainsString('ya tiene una receta default activa', session('error'));

        $recetaInactiva->refresh();
        $this->assertFalse($recetaInactiva->activo);
    }

    public function test_actualizar_receta_para_asociar_producto(): void
    {
        $producto = Producto::create([
            'nombre' => 'Concha',
            'tipo_unidad' => 'unidad',
            'activo' => true,
        ]);

        $ing = Ingrediente::create([
            'nombre' => 'Harina',
            'unidad_medida' => 'g',
            'stock_actual' => 100,
            'stock_minimo' => 20,
            'costo_unitario' => 0.50,
        ]);

        $receta = Receta::create([
            'producto_id' => null,
            'nombre' => 'Receta Independiente',
            'activo' => true,
        ]);

        RecetaDetalle::create([
            'receta_id' => $receta->id,
            'ingrediente_id' => $ing->id,
            'cantidad' => 0.5,
        ]);

        $response = $this->actingAs($this->admin)->patch(route('recetas.update', $receta), [
            'nombre' => 'Receta Independiente',
            'categoria_receta_id' => $this->categoria->id,
            'producto_id' => $producto->id,
            'tiempo_preparacion' => 30,
            'lote_unidades' => 12,
            'activo' => true,
            'ingredientes' => [
                ['ingrediente_id' => $ing->id, 'cantidad' => 0.5],
            ],
        ]);

        $response->assertRedirect(route('recetas.index'));

        $receta->refresh();
        $this->assertEquals($producto->id, $receta->producto_id);
        $this->assertFalse($receta->esIndependiente());
    }

    public function test_actualizar_receta_para_desasociar_producto(): void
    {
        $producto = Producto::create([
            'nombre' => 'Concha',
            'tipo_unidad' => 'unidad',
            'activo' => true,
        ]);

        $ing = Ingrediente::create([
            'nombre' => 'Harina',
            'unidad_medida' => 'g',
            'stock_actual' => 100,
            'stock_minimo' => 20,
            'costo_unitario' => 0.50,
        ]);

        $receta = Receta::create([
            'producto_id' => $producto->id,
            'nombre' => 'Receta Con Producto',
            'activo' => true,
        ]);

        RecetaDetalle::create([
            'receta_id' => $receta->id,
            'ingrediente_id' => $ing->id,
            'cantidad' => 0.5,
        ]);

        $response = $this->actingAs($this->admin)->patch(route('recetas.update', $receta), [
            'nombre' => 'Receta Con Producto',
            'categoria_receta_id' => $this->categoria->id,
            'producto_id' => '',
            'tiempo_preparacion' => 30,
            'lote_unidades' => 12,
            'activo' => true,
            'ingredientes' => [
                ['ingrediente_id' => $ing->id, 'cantidad' => 0.5],
            ],
        ]);

        $response->assertRedirect(route('recetas.index'));

        $receta->refresh();
        $this->assertNull($receta->producto_id);
        $this->assertTrue($receta->esIndependiente());
    }

    public function test_listado_ordena_con_producto_primero(): void
    {
        $producto = Producto::create([
            'nombre' => 'Concha',
            'tipo_unidad' => 'unidad',
            'activo' => true,
        ]);

        Receta::create([
            'producto_id' => $producto->id,
            'nombre' => 'Receta Con Producto',
            'activo' => true,
        ]);

        Receta::create([
            'producto_id' => null,
            'nombre' => 'Receta Independiente',
            'activo' => true,
        ]);

        $response = $this->actingAs($this->admin)->get(route('recetas.index'));

        $response->assertStatus(200);

        $content = $response->getContent();
        $posConProducto = strpos($content, 'Receta Con Producto');
        $posIndependiente = strpos($content, 'Receta Independiente');

        $this->assertNotFalse($posConProducto);
        $this->assertNotFalse($posIndependiente);
        $this->assertLessThan($posIndependiente, $posConProducto);
    }

    public function test_receta_sin_producto_scope(): void
    {
        $producto = Producto::create([
            'nombre' => 'Test',
            'tipo_unidad' => 'unidad',
            'activo' => true,
        ]);

        Receta::create([
            'producto_id' => $producto->id,
            'nombre' => 'Con Producto',
            'activo' => true,
        ]);

        Receta::create([
            'producto_id' => null,
            'nombre' => 'Sin Producto',
            'activo' => true,
        ]);

        $sinProducto = Receta::sinProducto()->get();

        $this->assertTrue($sinProducto->contains('nombre', 'Sin Producto'));
        $this->assertFalse($sinProducto->contains('nombre', 'Con Producto'));
    }

    public function test_receta_con_producto_scope(): void
    {
        $producto = Producto::create([
            'nombre' => 'Test',
            'tipo_unidad' => 'unidad',
            'activo' => true,
        ]);

        Receta::create([
            'producto_id' => $producto->id,
            'nombre' => 'Con Producto',
            'activo' => true,
        ]);

        Receta::create([
            'producto_id' => null,
            'nombre' => 'Sin Producto',
            'activo' => true,
        ]);

        $conProducto = Receta::conProducto()->get();

        $this->assertTrue($conProducto->contains('nombre', 'Con Producto'));
        $this->assertFalse($conProducto->contains('nombre', 'Sin Producto'));
    }

    public function test_validacion_rechaza_receta_sin_categoria(): void
    {
        $ing = Ingrediente::create([
            'nombre' => 'Harina',
            'unidad_medida' => 'g',
            'stock_actual' => 100,
            'stock_minimo' => 20,
            'costo_unitario' => 0.50,
        ]);

        $response = $this->actingAs($this->admin)->post(route('recetas.store'), [
            'nombre' => 'Receta Sin Categoria',
            'tiempo_preparacion' => 30,
            'lote_unidades' => 12,
            'ingredientes' => [
                ['ingrediente_id' => $ing->id, 'cantidad' => 0.5],
            ],
        ]);

        $response->assertSessionHasErrors('categoria_receta_id');
    }

    public function test_validacion_rechaza_categoria_inexistente(): void
    {
        $ing = Ingrediente::create([
            'nombre' => 'Harina',
            'unidad_medida' => 'g',
            'stock_actual' => 100,
            'stock_minimo' => 20,
            'costo_unitario' => 0.50,
        ]);

        $response = $this->actingAs($this->admin)->post(route('recetas.store'), [
            'nombre' => 'Receta Categoria Inexistente',
            'categoria_receta_id' => 999999,
            'tiempo_preparacion' => 30,
            'lote_unidades' => 12,
            'ingredientes' => [
                ['ingrediente_id' => $ing->id, 'cantidad' => 0.5],
            ],
        ]);

        $response->assertSessionHasErrors('categoria_receta_id');
    }

    public function test_listado_filtra_por_categoria(): void
    {
        $postres = CategoriaReceta::create([
            'nombre' => 'Postres',
            'slug' => 'postres',
        ]);

        Receta::create([
            'categoria_receta_id' => $this->categoria->id,
            'nombre' => 'Pan Base',
            'activo' => true,
        ]);

        Receta::create([
            'categoria_receta_id' => $postres->id,
            'nombre' => 'Flan Base',
            'activo' => true,
        ]);

        $response = $this->actingAs($this->admin)->get(route('recetas.index', [
            'categoria_receta_id' => $postres->id,
        ]));

        $response->assertStatus(200);
        $response->assertSee('Flan Base');
        $response->assertDontSee('Pan Base');
    }

    public function test_categorias_se_muestran_ordenadas_alfabeticamente(): void
    {
        CategoriaReceta::create(['nombre' => 'Almuerzos', 'slug' => 'almuerzos']);
        CategoriaReceta::create(['nombre' => 'Galletas', 'slug' => 'galletas']);

        $response = $this->actingAs($this->admin)->get(route('recetas.categorias.index'));

        $response->assertStatus(200);

        $content = $response->getContent();
        $posAlmuerzos = strpos($content, 'Almuerzos');
        $posGalletas = strpos($content, 'Galletas');
        $posPanes = strpos($content, 'Panes');

        $this->assertNotFalse($posAlmuerzos);
        $this->assertNotFalse($posGalletas);
        $this->assertNotFalse($posPanes);
        $this->assertLessThan($posGalletas, $posAlmuerzos);
        $this->assertLessThan($posPanes, $posGalletas);
    }

    public function test_no_permite_categoria_duplicada_por_slug_normalizado(): void
    {
        $response = $this->actingAs($this->admin)->post(route('recetas.categorias.store'), [
            'nombre' => '  PANES  ',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');

        $this->assertSame(1, CategoriaReceta::where('slug', 'panes')->count());
    }

    public function test_admin_puede_eliminar_categoria_sin_recetas(): void
    {
        $categoria = CategoriaReceta::create([
            'nombre' => 'Postres',
            'slug' => 'postres',
        ]);

        $response = $this->actingAs($this->admin)->delete(route('recetas.categorias.destroy', $categoria));

        $response->assertRedirect(route('recetas.categorias.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('categorias_receta', [
            'id' => $categoria->id,
        ]);
    }

    public function test_admin_puede_eliminar_categoria_y_deja_recetas_sin_categoria(): void
    {
        $receta = Receta::create([
            'categoria_receta_id' => $this->categoria->id,
            'nombre' => 'Pan Base',
            'activo' => true,
        ]);

        $response = $this->actingAs($this->admin)->delete(route('recetas.categorias.destroy', $this->categoria));

        $response->assertRedirect(route('recetas.categorias.index'));
        $response->assertSessionHas('success');

        $receta->refresh();

        $this->assertDatabaseMissing('categorias_receta', [
            'id' => $this->categoria->id,
        ]);
        $this->assertNull($receta->categoria_receta_id);
    }

    public function test_eliminar_categoria_registra_trazabilidad(): void
    {
        Receta::create([
            'categoria_receta_id' => $this->categoria->id,
            'nombre' => 'Pan Base',
            'activo' => true,
        ]);

        $this->actingAs($this->admin)->delete(route('recetas.categorias.destroy', $this->categoria));

        $this->assertDatabaseHas('kardex_inventario', [
            'tipo' => 'actualizacion',
            'categoria' => 'categoria_receta',
            'referencia_type' => CategoriaReceta::class,
            'referencia_id' => $this->categoria->id,
        ]);

        $movimiento = KardexInventario::where('categoria', 'categoria_receta')
            ->where('referencia_id', $this->categoria->id)
            ->first();

        $this->assertStringContainsString('Recetas sin categoria: 1', $movimiento->notas);
    }

    public function test_listado_filtra_recetas_sin_categoria(): void
    {
        Receta::create([
            'categoria_receta_id' => $this->categoria->id,
            'nombre' => 'Pan Base',
            'activo' => true,
        ]);

        Receta::create([
            'categoria_receta_id' => null,
            'nombre' => 'Receta Pendiente',
            'activo' => true,
        ]);

        $response = $this->actingAs($this->admin)->get(route('recetas.index', [
            'categoria_receta_id' => 'sin_categoria',
        ]));

        $response->assertStatus(200);
        $response->assertSee('Receta Pendiente');
        $response->assertDontSee('Pan Base');
    }

    public function test_caja_no_puede_gestionar_categorias_de_recetas(): void
    {
        $caja = User::factory()->create(['rol' => 'caja']);

        $response = $this->actingAs($caja)->get(route('recetas.categorias.index'));

        $response->assertStatus(403);
    }
}
