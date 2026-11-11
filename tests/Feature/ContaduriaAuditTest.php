<?php

namespace Tests\Feature;

use App\Actions\CancelarVenta;
use App\Actions\ProcesarCompra;
use App\Actions\ProcesarVenta;
use App\Actions\RegistrarAsientoContable;
use App\Actions\RegistrarGasto;
use App\Models\AsientoContable;
use App\Models\CategoriaGasto;
use App\Models\Compra;
use App\Models\CuentaContable;
use App\Models\Ingrediente;
use App\Models\PresentacionProducto;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\SerieDocumento;
use App\Models\User;
use App\Models\Venta;
use App\Support\Money;
use Database\Seeders\CategoriasGastoSeeder;
use Database\Seeders\CuentasContablesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class ContaduriaAuditTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CuentasContablesSeeder::class);
        $this->seed(CategoriasGastoSeeder::class);
    }

    public function test_procesar_compra_guarda_total_subtotales_y_actualiza_stock(): void
    {
        $user = User::factory()->admin()->create();
        $proveedor = Proveedor::create(['nombre' => 'Harinas del Centro']);
        $ingrediente = Ingrediente::create([
            'nombre' => 'Harina fuerte',
            'categoria' => 'harinas',
            'unidad_medida' => 'g',
            'stock_actual' => 10,
            'stock_minimo' => 2,
            'costo_unitario' => 1.50,
        ]);

        $compra = app(ProcesarCompra::class)->execute([
            'proveedor_id' => $proveedor->id,
            'fecha' => '2026-06-11',
            'detalles' => [
                [
                    'ingrediente_id' => $ingrediente->id,
                    'cantidad' => 5,
                    'precio_unitario' => 2,
                ],
            ],
        ], $user->id);

        $this->assertSame('10.00', $compra->fresh()->total);
        $this->assertSame('10.00', $compra->detalles()->first()->subtotal);
        $this->assertSame('15.0000', $ingrediente->fresh()->stock_actual);
        $this->assertSame('1.6667', $ingrediente->fresh()->costo_unitario);
    }

    public function test_creacion_de_compra_no_esta_activa_en_finanzas(): void
    {
        $this->assertFalse(Route::has('finanzas.compras.create'));
        $this->assertFalse(Route::has('finanzas.compras.store'));
    }

    public function test_reporte_precios_proveedor_lista_ingredientes_disponibles(): void
    {
        $user = User::factory()->admin()->create();
        Ingrediente::create([
            'nombre' => 'Mantequilla',
            'categoria' => 'grasas',
            'unidad_medida' => 'g',
        ]);

        $response = $this->actingAs($user)->get(route('finanzas.reportes.precios-proveedor'));

        $response->assertOk();
        $response->assertSee('Mantequilla');
    }

    public function test_admin_puede_ver_panel_principal_de_finanzas(): void
    {
        $user = User::factory()->admin()->create();

        $response = $this->actingAs($user)->get(route('finanzas.index'));

        $response->assertOk();
        $response->assertSee('Finanzas');
        $response->assertSee('Libro contable');
        $response->assertSee('Valor de inventario');
        $response->assertSee('Gastos');
        $response->assertSee('Resultado antes de costo');
        $response->assertDontSee('Ingresos menos compras registradas');
        $response->assertSee('Tendencia de 6 meses');
        $response->assertSee('Rentabilidad');
        $response->assertSee('Proveedores');
        $response->assertSee('Tendencias e inventario');
        $response->assertSee('Cierre visual');
        $response->assertSee('Precios proveedor');
        $response->assertSee('Resultado, gastos y compras');
        $response->assertSee('Inventario');
        $response->assertSee('Lectura automatizada');
        $response->assertDontSee('Flujo operativo sano sano');
    }

    public function test_usuario_caja_no_puede_ver_panel_principal_de_finanzas(): void
    {
        $user = User::factory()->create(['rol' => 'caja']);

        $response = $this->actingAs($user)->get(route('finanzas.index'));

        $response->assertForbidden();
    }

    public function test_navegacion_principal_apunta_al_index_de_finanzas(): void
    {
        $user = User::factory()->admin()->create();

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee(route('finanzas.index'), false);
    }

    public function test_rutas_principales_de_finanzas_siguen_disponibles(): void
    {
        $user = User::factory()->admin()->create();

        $routes = [
            'finanzas.compras.index',
            'finanzas.gastos.index',
            'finanzas.proveedores.index',
            'finanzas.libro-contable.index',
            'finanzas.reportes.resultados',
            'finanzas.reportes.rentabilidad',
            'finanzas.reportes.precios-proveedor',
            'finanzas.reportes.gastos-categoria',
            'finanzas.reportes.tendencia-ventas',
            'finanzas.reportes.valor-inventario',
        ];

        foreach ($routes as $route) {
            $this->actingAs($user)->get(route($route))->assertOk();
        }
    }

    public function test_compra_no_puede_modificarse(): void
    {
        $user = User::factory()->admin()->create();
        $proveedor = Proveedor::create(['nombre' => 'Proveedor auditado']);

        $compra = new Compra([
            'proveedor_id' => $proveedor->id,
            'fecha' => now(),
            'notas' => 'Original',
        ]);
        $compra->user_id = $user->id;
        $compra->total = 25;
        $compra->save();

        $this->expectException(HttpException::class);

        $compra->update(['notas' => 'Cambio']);
    }

    public function test_gasto_no_puede_modificarse(): void
    {
        $user = User::factory()->admin()->create();
        $categoria = $this->crearCategoriaGasto();

        $gasto = app(RegistrarGasto::class)->execute([
            'categoria_id' => $categoria->id,
            'token_registro' => (string) Str::uuid(),
            'concepto' => 'Servicio mensual',
            'fecha' => now(),
            'monto' => 12,
            'metodo_pago' => 'efectivo',
        ], $user->id);

        $this->expectException(HttpException::class);

        $gasto->update(['monto' => 15]);
    }

    public function test_venta_pagada_crea_asiento_balanceado(): void
    {
        $user = User::factory()->admin()->create();
        $producto = Producto::create(['nombre' => 'Pan dulce', 'tipo_unidad' => 'unidad', 'activo' => true]);
        $presentacion = PresentacionProducto::create([
            'producto_id' => $producto->id,
            'nombre' => 'Unidad',
            'cantidad_unidades' => 1,
            'precio' => 2,
            'stock_actual' => 10,
            'activo' => true,
        ]);

        SerieDocumento::create([
            'serie' => '001',
            'descripcion' => 'Venta',
            'ultimo_numero' => 0,
            'year' => now()->year,
            'activo' => true,
        ]);

        $venta = app(ProcesarVenta::class)->execute([
            'items' => [['presentacion_id' => $presentacion->id, 'cantidad' => 2]],
            'pagos' => [['metodo' => 'efectivo', 'monto' => 4]],
        ], $user->id);

        $asiento = AsientoContable::where('referencia_type', Venta::class)
            ->where('referencia_id', $venta->id)
            ->firstOrFail();

        $this->assertSame('venta', $asiento->origen);
        $this->assertEquals(
            (float) $asiento->detalles()->sum('debe'),
            (float) $asiento->detalles()->sum('haber')
        );
    }

    public function test_gasto_crea_asiento_de_egreso(): void
    {
        $user = User::factory()->admin()->create();
        $categoria = $this->crearCategoriaGasto();

        $gasto = app(RegistrarGasto::class)->execute([
            'categoria_id' => $categoria->id,
            'token_registro' => (string) Str::uuid(),
            'concepto' => 'Servicio mensual',
            'fecha' => now(),
            'monto' => 12,
            'metodo_pago' => 'efectivo',
        ], $user->id);

        $this->assertDatabaseHas('asientos_contables', [
            'origen' => 'gasto',
            'referencia_type' => $gasto::class,
            'referencia_id' => $gasto->id,
        ]);
    }

    public function test_asiento_desbalanceado_falla(): void
    {
        $this->expectException(RuntimeException::class);

        app(RegistrarAsientoContable::class)->execute([
            'origen' => 'prueba',
            'detalles' => [
                ['codigo' => '1010', 'nombre' => 'Caja', 'tipo' => 'activo', 'debe' => 10, 'haber' => 0],
                ['codigo' => '4010', 'nombre' => 'Ventas', 'tipo' => 'ingreso', 'debe' => 0, 'haber' => 9],
            ],
        ]);
    }

    public function test_asiento_rechaza_menos_de_dos_detalles(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('al menos dos detalles');

        app(RegistrarAsientoContable::class)->execute([
            'origen' => 'prueba',
            'detalles' => [
                ['codigo' => '1010', 'debe' => 10, 'haber' => 0],
            ],
        ]);
    }

    public function test_asiento_rechaza_una_sola_cuenta_repetida(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('dos cuentas diferentes');

        app(RegistrarAsientoContable::class)->execute([
            'origen' => 'prueba',
            'detalles' => [
                ['codigo' => '1010', 'debe' => 10, 'haber' => 0],
                ['codigo' => '1010', 'debe' => 0, 'haber' => 10],
            ],
        ]);
    }

    public function test_asiento_rechaza_cuenta_inexistente_y_no_la_crea(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('no existe o está inactiva');

        try {
            app(RegistrarAsientoContable::class)->execute([
                'origen' => 'prueba',
                'detalles' => [
                    ['codigo' => '9999', 'debe' => 10, 'haber' => 0],
                    ['codigo' => '1010', 'debe' => 0, 'haber' => 10],
                ],
            ]);
        } finally {
            $this->assertDatabaseMissing('cuentas_contables', ['codigo' => '9999']);
        }
    }

    public function test_asiento_rechaza_cuenta_inactiva(): void
    {
        $cuenta = CuentaContable::create([
            'codigo' => '5998',
            'nombre' => 'Cuenta inactiva',
            'tipo' => 'gasto',
            'activo' => false,
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('no existe o está inactiva');

        app(RegistrarAsientoContable::class)->execute([
            'origen' => 'prueba',
            'detalles' => [
                ['cuenta_id' => $cuenta->id, 'debe' => 10, 'haber' => 0],
                ['codigo' => '1010', 'debe' => 0, 'haber' => 10],
            ],
        ]);
    }

    public function test_cancelacion_de_venta_crea_asiento_balanceado(): void
    {
        $user = User::factory()->admin()->create();
        $producto = Producto::create(['nombre' => 'Pan dulce', 'tipo_unidad' => 'unidad', 'activo' => true]);
        $presentacion = PresentacionProducto::create([
            'producto_id' => $producto->id,
            'nombre' => 'Unidad',
            'cantidad_unidades' => 1,
            'precio' => 2,
            'stock_actual' => 10,
            'activo' => true,
        ]);

        SerieDocumento::create([
            'serie' => '001',
            'descripcion' => 'Venta',
            'ultimo_numero' => 0,
            'year' => now()->year,
            'activo' => true,
        ]);

        $venta = app(ProcesarVenta::class)->execute([
            'items' => [['presentacion_id' => $presentacion->id, 'cantidad' => 2]],
            'pagos' => [['metodo' => 'efectivo', 'monto' => 4]],
        ], $user->id);

        app(CancelarVenta::class)->execute($venta, $user, true);

        $asiento = AsientoContable::where('origen', 'cancelacion_venta')->firstOrFail();
        $this->assertEquals(
            (float) $asiento->detalles()->sum('debe'),
            (float) $asiento->detalles()->sum('haber')
        );
    }

    public function test_mayor_cuadra_con_el_diario_y_aplica_la_reversion_en_su_periodo(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 31)->setTime(23, 59, 59));
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);
        $producto = Producto::create(['nombre' => 'Pan', 'tipo_unidad' => 'unidad', 'activo' => true]);
        $presentacion = PresentacionProducto::create([
            'producto_id' => $producto->id, 'nombre' => 'Unidad', 'cantidad_unidades' => 1,
            'precio' => 100, 'stock_actual' => 10, 'activo' => true,
        ]);
        SerieDocumento::create([
            'serie' => '001', 'descripcion' => 'Venta', 'ultimo_numero' => 0,
            'year' => now()->year, 'activo' => true,
        ]);
        $venta = app(ProcesarVenta::class)->execute([
            'items' => [['presentacion_id' => $presentacion->id, 'cantidad' => 1]],
            'pagos' => [['metodo' => 'efectivo', 'monto' => 100]],
        ], $admin->id);
        app(RegistrarGasto::class)->execute([
            'categoria_id' => $this->crearCategoriaGasto()->id,
            'token_registro' => (string) Str::uuid(),
            'concepto' => 'Servicio mensual', 'fecha' => now(),
            'monto' => 12.25, 'metodo_pago' => 'efectivo',
        ], $admin->id);

        $this->travelTo(now()->addSecond());
        app(CancelarVenta::class)->execute($venta, $admin);

        $periodo = ['desde' => '2026-10-01', 'hasta' => '2026-10-31'];
        $debe = $haber = 0;
        foreach (CuentaContable::all() as $cuenta) {
            $consulta = $this->get(route('finanzas.libro-mayor.index', $periodo + ['cuenta_id' => $cuenta->id]))
                ->assertOk();
            $debe += $consulta->viewData('mayor')['debe'];
            $haber += $consulta->viewData('mayor')['haber'];
        }

        $diario = AsientoContable::with('detalles')->where('estado', 'confirmado')
            ->where('fecha_contable', '>=', '2026-10-01 00:00:00')
            ->where('fecha_contable', '<', '2026-11-01 00:00:00')->get();
        $this->assertCount(2, $diario);
        $this->assertSame(11225, $debe);
        $this->assertSame($diario->flatMap->detalles->sum(fn ($detalle) => Money::cents($detalle->debe)), $debe);
        $this->assertSame($diario->flatMap->detalles->sum(fn ($detalle) => Money::cents($detalle->haber)), $haber);

        $caja = CuentaContable::where('codigo', '1010')->firstOrFail();
        $reversion = AsientoContable::where('origen', 'cancelacion_venta')->firstOrFail();
        $noviembre = $this->get(route('finanzas.libro-mayor.index', ['cuenta_id' => $caja->id]))
            ->assertOk()->assertSee('Acreedor')->assertSee(route('finanzas.libro-contable.show', $reversion));
        $this->assertSame(8775, $noviembre->viewData('mayor')['saldoInicial']);
        $this->assertSame(10000, $noviembre->viewData('mayor')['haber']);
        $this->assertSame(-1225, $noviembre->viewData('mayor')['saldoFinal']);
        $this->travelBack();
    }

    public function test_mayor_conserva_saldos_entre_paginas_y_en_periodos_vacios(): void
    {
        $admin = User::factory()->admin()->create();
        $caja = CuentaContable::where('codigo', '1010')->firstOrFail();
        $registrar = app(RegistrarAsientoContable::class);
        foreach (range(0, 25) as $numero) {
            $ultimo = $registrar->execute([
                'origen' => 'prueba',
                'fecha_contable' => $numero === 0 ? '2026-09-30 23:59:59' : '2026-10-01 00:00:00',
                'detalles' => [
                    ['codigo' => '1010', 'debe' => '0.01'],
                    ['codigo' => '1010', 'debe' => '0.02'],
                    ['codigo' => '4010', 'haber' => '0.03'],
                ],
            ]);
        }
        $caja->update(['activo' => false]);
        $borrador = AsientoContable::create([
            'origen' => 'prueba', 'estado' => 'borrador', 'fecha_contable' => '2026-10-01 00:00:00',
        ]);
        $borrador->detalles()->create(['cuenta_contable_id' => $caja->id, 'debe' => '99.99']);

        $filtros = ['cuenta_id' => $caja->id, 'desde' => '2026-10-01', 'hasta' => '2026-10-01'];
        $primera = $this->actingAs($admin)->get(route('finanzas.libro-mayor.index', $filtros))
            ->assertOk()->assertSee('(inactiva)')->assertHeader('Cache-Control', 'no-store, private');
        $this->assertCount(25, $primera->viewData('movimientos'));
        $this->assertSame(40, $primera->viewData('movimientos')->last()['saldo']);
        $segunda = $this->get($primera->viewData('movimientos')->nextPageUrl())->assertOk();
        $this->assertSame(42, $segunda->viewData('movimientos')->first()['saldo']);
        $this->assertSame($ultimo->id, $segunda->viewData('movimientos')->last()['detalle']->asiento_contable_id);
        $this->assertSame(78, $segunda->viewData('mayor')['saldoFinal']);

        $vacio = $this->get(route('finanzas.libro-mayor.index', [
            'cuenta_id' => $caja->id, 'desde' => '2026-10-02', 'hasta' => '2026-10-03',
        ]))->assertOk()->assertSee('Esta cuenta no tuvo movimientos en el período.');
        $this->assertSame(78, $vacio->viewData('mayor')['saldoInicial']);
        $this->assertSame(78, $vacio->viewData('mayor')['saldoFinal']);
    }

    public function test_mayor_reserva_la_consulta_a_administradores_y_valida_filtros(): void
    {
        $ruta = route('finanzas.libro-mayor.index');
        $this->get($ruta)->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create())->get($ruta)->assertForbidden();
        $this->actingAs(User::factory()->admin()->create())->get($ruta)->assertOk()
            ->assertSee('Selecciona una cuenta y un período');
        $this->get(route('finanzas.libro-mayor.index', [
            'cuenta_id' => 999999, 'desde' => '2026-10-10', 'hasta' => '2026-10-09',
        ]))->assertRedirect($ruta)->assertSessionHasErrors(['cuenta_id', 'hasta']);
        $this->get(route('finanzas.libro-mayor.index', ['desde' => ['2026-10-01']]))
            ->assertRedirect($ruta)->assertSessionHasErrors('desde');
    }

    private function crearCategoriaGasto(): CategoriaGasto
    {
        CuentaContable::firstOrCreate(
            ['codigo' => '1010'],
            ['nombre' => 'Caja', 'tipo' => 'activo', 'activo' => true]
        );
        $cuenta = CuentaContable::create([
            'codigo' => '5198',
            'nombre' => 'Servicios de prueba',
            'tipo' => 'gasto',
            'activo' => true,
        ]);

        return CategoriaGasto::create([
            'codigo' => 'servicios-prueba',
            'cuenta_contable_id' => $cuenta->id,
            'nombre' => 'Servicios',
            'tipo' => 'fijo',
            'activo' => true,
        ]);
    }
}
