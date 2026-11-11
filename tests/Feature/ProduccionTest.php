<?php

namespace Tests\Feature;

use App\Actions\RegistrarProduccion;
use App\Models\Ingrediente;
use App\Models\KardexInventario;
use App\Models\OrdenProduccion;
use App\Models\PresentacionProducto;
use App\Models\Producto;
use App\Models\Receta;
use App\Models\RecetaDetalle;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProduccionTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create();
    }

    private function setupProductWithRecipe(int $loteUnidades = 12, float $ingredienteCantidad = 100, float $ingredienteStock = 5000): array
    {
        $producto = Producto::create(['nombre' => 'Pan Artesanal', 'activo' => true]);
        $presentacion = PresentacionProducto::create([
            'producto_id' => $producto->id,
            'nombre' => 'Unitario',
            'cantidad_unidades' => 1,
            'precio' => 1.00,
            'stock_actual' => 0,
            'activo' => true,
        ]);
        $harina = Ingrediente::create([
            'nombre' => 'Harina de trigo',
            'unidad_medida' => 'g',
            'stock_actual' => $ingredienteStock,
        ]);
        $receta = Receta::create([
            'producto_id' => $producto->id,
            'nombre' => 'Receta Pan',
            'lote_unidades' => $loteUnidades,
            'activo' => true,
            'tiempo_preparacion' => 30,
        ]);
        RecetaDetalle::create([
            'receta_id' => $receta->id,
            'ingrediente_id' => $harina->id,
            'cantidad' => $ingredienteCantidad,
        ]);

        return compact('producto', 'presentacion', 'harina', 'receta');
    }

    private function createProductionOrder(string $productoNombre, string $presentacionNombre, string $lote, ?User $user = null, ?string $createdAt = null, ?string $notas = null): OrdenProduccion
    {
        $producto = Producto::create(['nombre' => $productoNombre, 'activo' => true]);
        $presentacion = PresentacionProducto::create([
            'producto_id' => $producto->id,
            'nombre' => $presentacionNombre,
            'cantidad_unidades' => 1,
            'precio' => 1.00,
            'stock_actual' => 0,
            'activo' => true,
        ]);

        $orden = OrdenProduccion::create([
            'numero_lote' => $lote,
            'presentacion_id' => $presentacion->id,
            'cantidad' => 1,
            'user_id' => ($user ?? $this->admin)->id,
            'notas' => $notas,
        ]);

        if ($createdAt) {
            $orden->forceFill([
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ])->save();
        }

        return $orden->refresh();
    }

    public function test_index_search_finds_by_lot_number(): void
    {
        $this->createProductionOrder('Pan Artesanal', 'Unitario', 'LOTE-2026-06-01-001');
        $this->createProductionOrder('Pastel Chocolate', 'Entero', 'LOTE-2026-06-02-002');

        $response = $this->actingAs($this->admin)->get(route('produccion.index', ['buscar' => '06-01-001']));

        $response->assertOk();
        $response->assertSee('LOTE-2026-06-01-001');
        $response->assertDontSee('LOTE-2026-06-02-002');
    }

    public function test_index_search_finds_by_product_and_presentation(): void
    {
        $this->createProductionOrder('Pan Integral', 'Caja familiar', 'LOTE-2026-06-03-001');
        $this->createProductionOrder('Galleta Vainilla', 'Bolsa x12', 'LOTE-2026-06-03-002');

        $response = $this->actingAs($this->admin)->get(route('produccion.index', ['buscar' => 'Caja familiar']));

        $response->assertOk();
        $response->assertSee('Pan Integral');
        $response->assertSee('Caja familiar');
        $response->assertDontSee('LOTE-2026-06-03-002');
    }

    public function test_index_filters_by_date_range(): void
    {
        $this->createProductionOrder('Pan Diario', 'Unidad', 'LOTE-2026-06-01-001', createdAt: '2026-06-01 10:00:00');
        $this->createProductionOrder('Pastel Fiesta', 'Entero', 'LOTE-2026-06-05-001', createdAt: '2026-06-05 10:00:00');
        $this->createProductionOrder('Galleta', 'Caja', 'LOTE-2026-06-10-001', createdAt: '2026-06-10 10:00:00');

        $response = $this->actingAs($this->admin)->get(route('produccion.index', [
            'desde' => '2026-06-04',
            'hasta' => '2026-06-08',
        ]));

        $response->assertOk();
        $response->assertSee('LOTE-2026-06-05-001');
        $response->assertDontSee('LOTE-2026-06-01-001');
        $response->assertDontSee('LOTE-2026-06-10-001');
    }

    public function test_index_filters_by_product_presentation_and_user(): void
    {
        $pan = $this->createProductionOrder('Pan Dulce', 'Unidad', 'LOTE-2026-06-04-001');
        $caja = User::factory()->create(['name' => 'Caja Produccion']);
        $pastel = $this->createProductionOrder('Pastel', 'Entero', 'LOTE-2026-06-04-002', user: $caja);

        $response = $this->actingAs($this->admin)->get(route('produccion.index', [
            'producto_id' => $pan->presentacion->producto_id,
        ]));

        $response->assertOk();
        $response->assertSee('Pan Dulce');
        $response->assertDontSee('LOTE-2026-06-04-002');

        $response = $this->actingAs($this->admin)->get(route('produccion.index', [
            'presentacion_id' => $pastel->presentacion_id,
            'user_id' => $caja->id,
        ]));

        $response->assertOk();
        $response->assertSee('Pastel');
        $response->assertSee('Caja Produccion');
        $response->assertDontSee('LOTE-2026-06-04-001');
    }

    public function test_index_pagination_preserves_filters(): void
    {
        for ($i = 1; $i <= 21; $i++) {
            $this->createProductionOrder('Pan Paginado', 'Unidad', 'LOTE-2026-06-06-'.str_pad((string) $i, 3, '0', STR_PAD_LEFT));
        }

        $response = $this->actingAs($this->admin)->get(route('produccion.index', ['buscar' => 'Pan Paginado']));

        $response->assertOk();
        $response->assertSee('buscar=Pan%20Paginado', false);
    }

    public function test_index_fecha_alias_filters_exact_day(): void
    {
        $this->createProductionOrder('Pan Fecha', 'Unidad', 'LOTE-2026-06-07-001', createdAt: '2026-06-07 08:00:00');
        $this->createProductionOrder('Pan Fuera', 'Unidad', 'LOTE-2026-06-08-001', createdAt: '2026-06-08 08:00:00');

        $response = $this->actingAs($this->admin)->get(route('produccion.index', ['fecha' => '2026-06-07']));

        $response->assertOk();
        $response->assertSee('LOTE-2026-06-07-001');
        $response->assertDontSee('LOTE-2026-06-08-001');
    }

    public function test_create_view_shows_catalog_metadata(): void
    {
        $setup = $this->setupProductWithRecipe(loteUnidades: 12);
        $producto = Producto::create(['nombre' => 'Galleta Simple', 'activo' => true]);
        PresentacionProducto::create([
            'producto_id' => $producto->id,
            'nombre' => 'Caja x6',
            'cantidad_unidades' => 6,
            'precio' => 5.00,
            'stock_actual' => 1,
            'stock_minimo' => 2,
            'activo' => true,
        ]);

        $response = $this->actingAs($this->admin)->get(route('produccion.create', [
            'presentacion_id' => $setup['presentacion']->id,
        ]));

        $response->assertOk();
        $response->assertSee('Cat&aacute;logo de producci&oacute;n', false);
        $response->assertSee('Pan Artesanal');
        $response->assertSee('Con receta');
        $response->assertSee('Galleta Simple');
        $response->assertSee('Sin receta');
        $response->assertSee('Stock bajo');
    }

    public function test_produce_single_batch_consumes_recipe_ingredients(): void
    {
        $setup = $this->setupProductWithRecipe(loteUnidades: 12, ingredienteCantidad: 500, ingredienteStock: 5000);

        $stockAnterior = (float) $setup['harina']->stock_actual;
        $consumoEsperado = 500;

        $response = $this->actingAs($this->admin)->post('/produccion', [
            'presentacion_id' => $setup['presentacion']->id,
            'cantidad' => 1,
        ]);

        $response->assertRedirect(route('produccion.index'));
        $response->assertSessionHas('success');

        $setup['harina']->refresh();
        $this->assertEquals(
            $stockAnterior - $consumoEsperado,
            (float) $setup['harina']->stock_actual,
            'Single batch should consume receta_detalle.cantidad exactly (per-batch)'
        );

        $setup['presentacion']->refresh();
        $this->assertEquals(12, (float) $setup['presentacion']->stock_actual, 'Stock should be lote_unidades units');
    }

    public function test_produce_multiple_batches_scales_ingredients(): void
    {
        $setup = $this->setupProductWithRecipe(loteUnidades: 12, ingredienteCantidad: 500, ingredienteStock: 5000);

        $stockAnterior = (float) $setup['harina']->stock_actual;
        $cantidadLotes = 2;
        $consumoEsperado = 500 * $cantidadLotes;

        $response = $this->actingAs($this->admin)->post('/produccion', [
            'presentacion_id' => $setup['presentacion']->id,
            'cantidad' => $cantidadLotes,
        ]);

        $response->assertRedirect(route('produccion.index'));

        $setup['harina']->refresh();
        $this->assertEquals(
            $stockAnterior - $consumoEsperado,
            (float) $setup['harina']->stock_actual,
            'Two batches should consume exactly 2× the recipe amount'
        );

        $this->assertEquals(
            4000,
            (float) $setup['harina']->stock_actual,
            'Remaining stock should be 5000 - 1000 = 4000'
        );

        $setup['presentacion']->refresh();
        $this->assertEquals(24, (float) $setup['presentacion']->stock_actual, 'Stock should be 2 × 12 = 24 units');
    }

    public function test_produce_multiple_batches_adds_correct_stock(): void
    {
        $setup = $this->setupProductWithRecipe(loteUnidades: 12, ingredienteCantidad: 500, ingredienteStock: 5000);

        $stockAnterior = (float) $setup['presentacion']->stock_actual;
        $cantidadLotes = 2;
        $stockEsperado = 12 * $cantidadLotes;

        $response = $this->actingAs($this->admin)->post('/produccion', [
            'presentacion_id' => $setup['presentacion']->id,
            'cantidad' => $cantidadLotes,
        ]);

        $response->assertRedirect(route('produccion.index'));

        $setup['presentacion']->refresh();
        $this->assertEquals(
            $stockAnterior + $stockEsperado,
            (float) $setup['presentacion']->stock_actual,
            'Two batches with lote_unidades=12 should add 24 units to presentation stock'
        );

        $this->assertEquals(24, (float) $setup['presentacion']->stock_actual);
    }

    public function test_produce_fails_with_insufficient_stock(): void
    {
        $setup = $this->setupProductWithRecipe(loteUnidades: 12, ingredienteCantidad: 500, ingredienteStock: 400);

        $response = $this->actingAs($this->admin)->post('/produccion', [
            'presentacion_id' => $setup['presentacion']->id,
            'cantidad' => 1,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');

        $errorMessage = session('error');
        $this->assertStringContainsString('Stock insuficiente', $errorMessage);
        $this->assertStringContainsString($setup['harina']->nombre, $errorMessage);

        $setup['harina']->refresh();
        $this->assertEquals(400, (float) $setup['harina']->stock_actual, 'Stock should remain unchanged');

        $this->assertEquals(0, OrdenProduccion::count(), 'No production order should be created');
    }

    public function test_produce_without_recipe_only_adds_stock(): void
    {
        $producto = Producto::create(['nombre' => 'Galleta', 'activo' => true]);
        $presentacion = PresentacionProducto::create([
            'producto_id' => $producto->id,
            'nombre' => 'Caja x6',
            'cantidad_unidades' => 6,
            'precio' => 5.00,
            'stock_actual' => 10,
            'activo' => true,
        ]);

        $stockAnterior = (float) $presentacion->stock_actual;
        $cantidadLotes = 2;
        $stockEsperado = 6 * $cantidadLotes;

        $response = $this->actingAs($this->admin)->post('/produccion', [
            'presentacion_id' => $presentacion->id,
            'cantidad' => $cantidadLotes,
        ]);

        $response->assertRedirect(route('produccion.index'));
        $response->assertSessionHas('success');

        $presentacion->refresh();
        $this->assertEquals(
            $stockAnterior + $stockEsperado,
            (float) $presentacion->stock_actual,
            'Without recipe, stock should increase by cantidad × cantidad_unidades'
        );

        $this->assertEquals(22, (float) $presentacion->stock_actual);

        $this->assertEquals(
            0,
            KardexInventario::where('tipo', 'salida')->where('origen_movimiento', 'produccion')->count(),
            'No ingredient consumption movements should be created'
        );

        $this->assertEquals(
            1,
            KardexInventario::where('tipo', 'entrada')->where('origen_movimiento', 'produccion')->count(),
            'Only one entry movement should be created'
        );
    }

    public function test_produce_validates_positive_quantity(): void
    {
        $setup = $this->setupProductWithRecipe();

        $response = $this->actingAs($this->admin)->post('/produccion', [
            'presentacion_id' => $setup['presentacion']->id,
            'cantidad' => 0,
        ]);

        $response->assertSessionHasErrors('cantidad');

        $response = $this->actingAs($this->admin)->post('/produccion', [
            'presentacion_id' => $setup['presentacion']->id,
            'cantidad' => -1,
        ]);

        $response->assertSessionHasErrors('cantidad');

        $this->assertEquals(0, OrdenProduccion::count(), 'No production orders should be created');
    }

    public function test_produce_creates_movimiento_inventario(): void
    {
        $setup = $this->setupProductWithRecipe(loteUnidades: 12, ingredienteCantidad: 500, ingredienteStock: 5000);

        $this->actingAs($this->admin)->post('/produccion', [
            'presentacion_id' => $setup['presentacion']->id,
            'cantidad' => 1,
        ]);

        $salida = KardexInventario::where('tipo', 'salida')
            ->where('ingrediente_id', $setup['harina']->id)
            ->where('origen_movimiento', 'produccion')
            ->first();

        $this->assertNotNull($salida, 'Salida movimiento should exist');
        $this->assertEquals(500, (float) $salida->cantidad);
        $this->assertEquals(5000, (float) $salida->stock_anterior);
        $this->assertEquals(4500, (float) $salida->stock_resultante);
        $this->assertTrue($salida->reversible);
        $this->assertEquals($this->admin->id, $salida->user_id);

        $entrada = KardexInventario::where('tipo', 'entrada')
            ->where('referencia_type', PresentacionProducto::class)
            ->where('referencia_id', $setup['presentacion']->id)
            ->where('origen_movimiento', 'produccion')
            ->first();

        $this->assertNotNull($entrada, 'Entrada movimiento should exist');
        $this->assertEquals(12, (float) $entrada->cantidad);
        $this->assertEquals(0, (float) $entrada->stock_anterior);
        $this->assertEquals(12, (float) $entrada->stock_resultante);
        $this->assertTrue($entrada->reversible);
        $this->assertEquals($this->admin->id, $entrada->user_id);
    }

    public function test_produce_creates_orden_produccion(): void
    {
        $setup = $this->setupProductWithRecipe();
        $notas = 'Producción de prueba para testing';

        $this->actingAs($this->admin)->post('/produccion', [
            'presentacion_id' => $setup['presentacion']->id,
            'cantidad' => 2,
            'notas' => $notas,
        ]);

        $orden = OrdenProduccion::first();
        $this->assertNotNull($orden);
        $this->assertEquals($setup['presentacion']->id, $orden->presentacion_id);
        $this->assertEquals(2, $orden->cantidad);
        $this->assertEquals($this->admin->id, $orden->user_id);
        $this->assertEquals($notas, $orden->notas);
        $this->assertStringContainsString('LOTE-', $orden->numero_lote);
        $this->assertStringContainsString(now()->format('Y-m-d'), $orden->numero_lote);
    }

    public function test_preview_returns_correct_consumption(): void
    {
        $setup = $this->setupProductWithRecipe(loteUnidades: 12, ingredienteCantidad: 500, ingredienteStock: 5000);

        $response = $this->actingAs($this->admin)->get('/produccion/preview?presentacion_id='.$setup['presentacion']->id.'&cantidad=1');

        $response->assertOk();
        $response->assertJson([
            'sin_receta' => false,
            'stock_presentacion' => 0,
        ]);

        $json = $response->json();
        $this->assertCount(1, $json['consumos']);

        $consumo = $json['consumos'][0];
        $this->assertEquals($setup['harina']->id, $consumo['id']);
        $this->assertEquals($setup['harina']->nombre, $consumo['nombre']);
        $this->assertEquals('g', $consumo['unidad']);
        $this->assertEquals(500, $consumo['necesario']);
        $this->assertEquals(5000, $consumo['disponible']);
        $this->assertTrue($consumo['suficiente']);
        $this->assertEquals(12, $json['unidades_resultantes']);
    }

    public function test_preview_returns_insufficient_flag(): void
    {
        $setup = $this->setupProductWithRecipe(loteUnidades: 12, ingredienteCantidad: 500, ingredienteStock: 400);

        $response = $this->actingAs($this->admin)->get('/produccion/preview?presentacion_id='.$setup['presentacion']->id.'&cantidad=1');

        $response->assertOk();

        $json = $response->json();
        $consumo = $json['consumos'][0];
        $this->assertFalse($consumo['suficiente']);
        $this->assertEquals(500, $consumo['necesario']);
        $this->assertEquals(400, $consumo['disponible']);
    }

    public function test_preview_and_production_preserve_four_decimal_ingredient_quantities(): void
    {
        $setup = $this->setupProductWithRecipe(ingredienteCantidad: 0.0125, ingredienteStock: 0.0124);
        $data = ['presentacion_id' => $setup['presentacion']->id, 'cantidad' => 1];
        $preview = route('produccion.preview', $data);

        $this->actingAs($this->admin)->getJson($preview)->assertOk()
            ->assertJsonPath('consumos.0.necesario', 0.0125)
            ->assertJsonPath('consumos.0.disponible', 0.0124)
            ->assertJsonPath('consumos.0.suficiente', false);

        $this->post(route('produccion.store'), $data)->assertSessionHas('error');
        $this->assertSame('0.0124', $setup['harina']->fresh()->stock_actual);

        $setup['harina']->update(['stock_actual' => 0.0126]);
        $this->post(route('produccion.store'), $data)->assertSessionHas('success');

        $this->assertSame('0.0001', $setup['harina']->fresh()->stock_actual);
        $this->assertSame('0.0125', KardexInventario::where('ingrediente_id', $setup['harina']->id)->sole()->cantidad);
    }

    public function test_preview_without_recipe_returns_empty_consumos(): void
    {
        $producto = Producto::create(['nombre' => 'Galleta', 'activo' => true]);
        $presentacion = PresentacionProducto::create([
            'producto_id' => $producto->id,
            'nombre' => 'Caja',
            'cantidad_unidades' => 6,
            'precio' => 5.00,
            'stock_actual' => 10,
            'activo' => true,
        ]);

        $response = $this->actingAs($this->admin)->get('/produccion/preview?presentacion_id='.$presentacion->id.'&cantidad=1');

        $response->assertOk();
        $response->assertJson([
            'sin_receta' => true,
            'consumos' => [],
            'stock_presentacion' => 10,
        ]);
    }

    public function test_preview_validates_required_fields(): void
    {
        $response = $this->actingAs($this->admin)->get('/produccion/preview?cantidad=1');
        $response->assertSessionHasErrors('presentacion_id');

        $response = $this->actingAs($this->admin)->get('/produccion/preview?presentacion_id=1');
        $response->assertSessionHasErrors('cantidad');

        $response = $this->actingAs($this->admin)->get('/produccion/preview?presentacion_id=1&cantidad=0');
        $response->assertSessionHasErrors('cantidad');
    }

    public function test_produce_uses_bulk_locking(): void
    {
        $setup = $this->setupProductWithRecipe(loteUnidades: 12, ingredienteCantidad: 500, ingredienteStock: 5000);

        $action = new RegistrarProduccion;
        $reflection = new \ReflectionClass($action);

        $descontarMethod = $reflection->getMethod('descontarIngredientes');
        $descontarSource = file($reflection->getFileName());
        $methodStart = $descontarMethod->getStartLine() - 1;
        $methodEnd = $descontarMethod->getEndLine();
        $methodLines = array_slice($descontarSource, $methodStart, $methodEnd - $methodStart);
        $methodCode = implode('', $methodLines);

        $this->assertStringContainsString('lockForUpdate', $methodCode, 'descontarIngredientes should use lockForUpdate');
        $this->assertStringContainsString('whereIn', $methodCode, 'descontarIngredientes should use bulk locking with whereIn');

        $executeMethod = $reflection->getMethod('execute');
        $executeSource = file($reflection->getFileName());
        $executeStart = $executeMethod->getStartLine() - 1;
        $executeEnd = $executeMethod->getEndLine();
        $executeLines = array_slice($executeSource, $executeStart, $executeEnd - $executeStart);
        $executeCode = implode('', $executeLines);

        $this->assertStringContainsString('lockForUpdate', $executeCode, 'execute should lock presentacion with lockForUpdate');
        $this->assertStringContainsString('DB::transaction', $executeCode, 'execute should run inside a DB transaction');

        $this->actingAs($this->admin)->post('/produccion', [
            'presentacion_id' => $setup['presentacion']->id,
            'cantidad' => 1,
        ])->assertRedirect();
    }

    public function test_produce_with_multiple_ingredients(): void
    {
        $producto = Producto::create(['nombre' => 'Pastel', 'activo' => true]);
        $presentacion = PresentacionProducto::create([
            'producto_id' => $producto->id,
            'nombre' => 'Entero',
            'cantidad_unidades' => 1,
            'precio' => 10.00,
            'stock_actual' => 0,
            'activo' => true,
        ]);
        $harina = Ingrediente::create(['nombre' => 'Harina', 'unidad_medida' => 'g', 'stock_actual' => 10000]);
        $azucar = Ingrediente::create(['nombre' => 'Azúcar', 'unidad_medida' => 'g', 'stock_actual' => 5000]);
        $huevos = Ingrediente::create(['nombre' => 'Huevos', 'unidad_medida' => 'unidad', 'stock_actual' => 100]);

        $receta = Receta::create([
            'producto_id' => $producto->id,
            'nombre' => 'Receta Pastel',
            'lote_unidades' => 12,
            'activo' => true,
            'tiempo_preparacion' => 60,
        ]);

        RecetaDetalle::create(['receta_id' => $receta->id, 'ingrediente_id' => $harina->id, 'cantidad' => 500]);
        RecetaDetalle::create(['receta_id' => $receta->id, 'ingrediente_id' => $azucar->id, 'cantidad' => 300]);
        RecetaDetalle::create(['receta_id' => $receta->id, 'ingrediente_id' => $huevos->id, 'cantidad' => 4]);

        $this->actingAs($this->admin)->post('/produccion', [
            'presentacion_id' => $presentacion->id,
            'cantidad' => 2,
        ])->assertRedirect();

        $harina->refresh();
        $azucar->refresh();
        $huevos->refresh();

        $this->assertEquals(10000 - (500 * 2), (float) $harina->stock_actual);
        $this->assertEquals(5000 - (300 * 2), (float) $azucar->stock_actual);
        $this->assertEquals(100 - (4 * 2), (float) $huevos->stock_actual);

        $presentacion->refresh();
        $this->assertEquals(24, (float) $presentacion->stock_actual);

        $this->assertEquals(3, KardexInventario::where('tipo', 'salida')->where('origen_movimiento', 'produccion')->count());
        $this->assertEquals(1, KardexInventario::where('tipo', 'entrada')->where('origen_movimiento', 'produccion')->count());
    }

    public function test_produce_requires_authentication(): void
    {
        $setup = $this->setupProductWithRecipe();

        $response = $this->post('/produccion', [
            'presentacion_id' => $setup['presentacion']->id,
            'cantidad' => 1,
        ]);

        $response->assertRedirect(route('login'));
    }

    public function test_produce_requires_admin_role(): void
    {
        $setup = $this->setupProductWithRecipe();
        $caja = User::factory()->create(['rol' => 'caja']);

        $response = $this->actingAs($caja)->post('/produccion', [
            'presentacion_id' => $setup['presentacion']->id,
            'cantidad' => 1,
        ]);

        $response->assertForbidden();
    }
}
