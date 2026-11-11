<?php

namespace Tests\Feature;

use App\Actions\RegistrarGasto;
use App\Models\AsientoContable;
use App\Models\CategoriaGasto;
use App\Models\CuentaContable;
use App\Models\Gasto;
use App\Models\Proveedor;
use App\Models\RecordatorioGasto;
use App\Models\User;
use Database\Seeders\CategoriasGastoSeeder;
use Database\Seeders\CuentasContablesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class GastoFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CuentasContablesSeeder::class);
        $this->seed(CategoriasGastoSeeder::class);
        $this->admin = User::factory()->admin()->create();
    }

    public function test_admin_accede_y_caja_no_gestiona_gastos(): void
    {
        $caja = User::factory()->create(['rol' => 'caja']);

        $this->actingAs($this->admin)->get(route('finanzas.gastos.create'))->assertOk();
        $this->actingAs($caja)->get(route('finanzas.gastos.index'))->assertForbidden();
        $this->actingAs($caja)->post(route('finanzas.gastos.store'), $this->datosGasto())->assertForbidden();
        $this->app['auth']->logout();
        $this->get(route('finanzas.gastos.index'))->assertRedirect(route('login'));
    }

    public function test_gasto_crea_asiento_balanceado_con_cuenta_de_categoria(): void
    {
        $categoria = CategoriaGasto::where('codigo', 'electricidad')->firstOrFail();

        $response = $this->actingAs($this->admin)->post(route('finanzas.gastos.store'), $this->datosGasto([
            'categoria_id' => $categoria->id,
            'metodo_pago' => 'transferencia',
        ]));

        $gasto = Gasto::firstOrFail();
        $response->assertRedirect(route('finanzas.gastos.show', $gasto));
        $this->assertSame($this->admin->id, $gasto->user_id);
        $this->assertSame('registrado', $gasto->estado->value);

        $asiento = AsientoContable::where('origen', 'gasto')->firstOrFail();
        $this->assertEquals((float) $asiento->detalles()->sum('debe'), (float) $asiento->detalles()->sum('haber'));
        $this->assertDatabaseHas('asiento_detalles', [
            'asiento_contable_id' => $asiento->id,
            'cuenta_contable_id' => $categoria->cuenta_contable_id,
            'debe' => 125.50,
        ]);
        $this->assertDatabaseHas('asiento_detalles', [
            'asiento_contable_id' => $asiento->id,
            'cuenta_contable_id' => CuentaContable::where('codigo', '1020')->value('id'),
            'haber' => 125.50,
        ]);
    }

    public function test_campos_protegidos_no_se_pueden_manipular(): void
    {
        $otro = User::factory()->admin()->create();

        $this->actingAs($this->admin)->post(route('finanzas.gastos.store'), $this->datosGasto([
            'user_id' => $otro->id,
            'estado' => 'anulado',
            'anulado_por' => $otro->id,
            'motivo_anulacion' => 'Manipulado',
        ]))->assertRedirect();

        $gasto = Gasto::firstOrFail();
        $this->assertSame($this->admin->id, $gasto->user_id);
        $this->assertSame('registrado', $gasto->estado->value);
        $this->assertNull($gasto->anulado_por);
    }

    public function test_token_repetido_no_duplica_gasto(): void
    {
        $datos = $this->datosGasto();

        $this->actingAs($this->admin)->post(route('finanzas.gastos.store'), $datos)->assertRedirect();
        $gasto = Gasto::firstOrFail();

        $this->actingAs($this->admin)->post(route('finanzas.gastos.store'), $datos)
            ->assertRedirect(route('finanzas.gastos.show', $gasto));

        $this->assertSame(1, Gasto::count());
        $this->assertSame(1, AsientoContable::where('origen', 'gasto')->count());
    }

    public function test_anulacion_conserva_gasto_y_crea_reversion(): void
    {
        $gasto = app(RegistrarGasto::class)->execute($this->datosGasto(), $this->admin->id);

        $this->actingAs($this->admin)
            ->patch(route('finanzas.gastos.anular', $gasto), ['motivo' => 'Factura registrada dos veces'])
            ->assertRedirect(route('finanzas.gastos.show', $gasto));

        $gasto->refresh();
        $this->assertSame('anulado', $gasto->estado->value);
        $this->assertSame($this->admin->id, $gasto->anulado_por);
        $this->assertSame(2, $gasto->asientos()->count());

        $original = $gasto->asientos()->where('origen', 'gasto')->firstOrFail();
        $reversion = $gasto->asientos()->where('origen', 'anulacion_gasto')->firstOrFail();
        $this->assertSame($original->id, $reversion->asiento_reversado_id);
        $this->assertEquals((float) $original->detalles()->sum('debe'), (float) $reversion->detalles()->sum('haber'));
    }

    public function test_gasto_anulado_no_afecta_totales(): void
    {
        $gasto = app(RegistrarGasto::class)->execute($this->datosGasto(), $this->admin->id);
        $this->actingAs($this->admin)->patch(route('finanzas.gastos.anular', $gasto), [
            'motivo' => 'Comprobante incorrecto',
        ]);

        $this->actingAs($this->admin)
            ->get(route('finanzas.gastos.index'))
            ->assertOk()
            ->assertSee('$0.00')
            ->assertSee('Anulado');
    }

    public function test_panel_muestra_grafica_incluso_sin_gastos_y_sin_kpis(): void
    {
        $this->actingAs($this->admin)
            ->get(route('finanzas.gastos.index'))
            ->assertOk()
            ->assertSee('"total":0', false)
            ->assertSee('data-gastos-six-month-chart', false)
            ->assertDontSee('Sin gastos registrados en los últimos seis meses')
            ->assertDontSee('Total visible')
            ->assertDontSee('Anulados visibles');
    }

    public function test_panel_muestra_linea_de_seis_meses_sin_depender_de_filtros(): void
    {
        app(RegistrarGasto::class)->execute($this->datosGasto(['monto' => '42.00']), $this->admin->id);

        $this->actingAs($this->admin)
            ->get(route('finanzas.gastos.index', ['categoria_id' => CategoriaGasto::where('codigo', 'agua')->value('id')]))
            ->assertOk()
            ->assertSee('data-gastos-six-month-chart', false)
            ->assertSee('"total":42', false)
            ->assertSee('Promedio de 6 meses')
            ->assertSee('Incluye meses sin gastos');
    }

    public function test_gastos_anulados_no_entran_en_linea(): void
    {
        $gasto = app(RegistrarGasto::class)->execute($this->datosGasto(['monto' => '25.00']), $this->admin->id);
        $this->actingAs($this->admin)->patch(route('finanzas.gastos.anular', $gasto), [
            'motivo' => 'Comprobante incorrecto',
        ]);

        $this->actingAs($this->admin)
            ->get(route('finanzas.gastos.index'))
            ->assertOk()
            ->assertSee('data-gastos-six-month-chart', false)
            ->assertSee('"total":0', false)
            ->assertDontSee('Sin gastos registrados en los últimos seis meses');
    }

    public function test_recordatorio_no_contabiliza_y_avanza_al_registrar(): void
    {
        $recordatorio = $this->crearRecordatorio();
        $this->assertSame(0, Gasto::count());
        $this->assertSame(0, AsientoContable::count());

        $periodo = $recordatorio->proxima_fecha->toDateString();
        $this->actingAs($this->admin)->post(route('finanzas.gastos.store'), $this->datosGasto([
            'recordatorio_gasto_id' => $recordatorio->id,
            'periodo_recordatorio' => $periodo,
        ]))->assertRedirect();

        $this->assertDatabaseHas('gastos', [
            'recordatorio_gasto_id' => $recordatorio->id,
            'periodo_recordatorio' => $periodo,
        ]);
        $this->assertSame(now()->addMonthNoOverflow()->toDateString(), $recordatorio->fresh()->proxima_fecha->toDateString());
    }

    public function test_recordatorio_fuerza_categoria_proveedor_y_guarda_estimado(): void
    {
        $proveedor = Proveedor::create(['nombre' => 'Empresa eléctrica']);
        $recordatorio = $this->crearRecordatorio(['proveedor_id' => $proveedor->id, 'monto_estimado' => 120]);
        $categoriaManipulada = CategoriaGasto::where('codigo', 'agua')->firstOrFail();
        $proveedorManipulado = Proveedor::create(['nombre' => 'Proveedor manipulado']);
        $periodo = $recordatorio->proxima_fecha->toDateString();

        $this->actingAs($this->admin)->post(route('finanzas.gastos.store'), $this->datosGasto([
            'categoria_id' => $categoriaManipulada->id,
            'proveedor_id' => $proveedorManipulado->id,
            'recordatorio_gasto_id' => $recordatorio->id,
            'periodo_recordatorio' => $periodo,
            'monto' => '135.25',
        ]))->assertRedirect();

        $gasto = Gasto::firstOrFail();
        $this->assertSame($recordatorio->categoria_id, $gasto->categoria_id);
        $this->assertSame($proveedor->id, $gasto->proveedor_id);
        $this->assertSame('120.00', $gasto->monto_estimado_recordatorio);
        $this->assertSame('135.25', $gasto->monto);
    }

    public function test_editar_recordatorio_no_modifica_gasto_anterior(): void
    {
        $proveedor = Proveedor::create(['nombre' => 'Empresa eléctrica']);
        $recordatorio = $this->crearRecordatorio(['proveedor_id' => $proveedor->id, 'monto_estimado' => 120]);
        $periodo = $recordatorio->proxima_fecha->toDateString();

        app(RegistrarGasto::class)->execute($this->datosGasto([
            'recordatorio_gasto_id' => $recordatorio->id,
            'periodo_recordatorio' => $periodo,
        ]), $this->admin->id);

        $nuevaCategoria = CategoriaGasto::where('codigo', 'agua')->firstOrFail();
        $nuevoProveedor = Proveedor::create(['nombre' => 'Proveedor nuevo']);
        $recordatorio->update([
            'categoria_id' => $nuevaCategoria->id,
            'proveedor_id' => $nuevoProveedor->id,
            'monto_estimado' => 50,
        ]);

        $gasto = Gasto::firstOrFail();
        $this->assertNotSame($nuevaCategoria->id, $gasto->categoria_id);
        $this->assertSame($proveedor->id, $gasto->proveedor_id);
        $this->assertSame('120.00', $gasto->monto_estimado_recordatorio);
    }

    public function test_formulario_vencido_no_adelanta_otro_periodo(): void
    {
        $recordatorio = $this->crearRecordatorio();
        $periodo = $recordatorio->proxima_fecha->toDateString();

        app(RegistrarGasto::class)->execute($this->datosGasto([
            'recordatorio_gasto_id' => $recordatorio->id,
            'periodo_recordatorio' => $periodo,
        ]), $this->admin->id);

        $this->actingAs($this->admin)->post(route('finanzas.gastos.store'), $this->datosGasto([
            'recordatorio_gasto_id' => $recordatorio->id,
            'periodo_recordatorio' => $periodo,
        ]))->assertSessionHasErrors('recordatorio_gasto_id');

        $this->assertSame(1, Gasto::count());
    }

    public function test_archivo_duplicado_se_elimina_en_conflicto_de_recordatorio(): void
    {
        Storage::fake('local');
        $recordatorio = $this->crearRecordatorio();
        $periodo = $recordatorio->proxima_fecha->toDateString();

        app(RegistrarGasto::class)->execute($this->datosGasto([
            'recordatorio_gasto_id' => $recordatorio->id,
            'periodo_recordatorio' => $periodo,
        ]), $this->admin->id);

        $this->actingAs($this->admin)->post(route('finanzas.gastos.store'), $this->datosGasto([
            'recordatorio_gasto_id' => $recordatorio->id,
            'periodo_recordatorio' => $periodo,
            'comprobante' => UploadedFile::fake()->create('duplicado.pdf', 80, 'application/pdf'),
        ]))->assertSessionHasErrors('recordatorio_gasto_id');

        $this->assertSame(1, Gasto::count());
        $this->assertCount(0, Storage::disk('local')->allFiles('gastos'));
    }

    public function test_comprobante_es_privado_y_solo_admin_lo_descarga(): void
    {
        Storage::fake('local');
        $archivo = UploadedFile::fake()->create('factura.pdf', 100, 'application/pdf');

        $this->actingAs($this->admin)->post(route('finanzas.gastos.store'), $this->datosGasto([
            'comprobante' => $archivo,
        ]))->assertRedirect();

        $gasto = Gasto::firstOrFail();
        Storage::disk('local')->assertExists($gasto->comprobante_path);
        $this->actingAs($this->admin)->get(route('finanzas.gastos.comprobante', $gasto))->assertDownload('factura.pdf');

        $caja = User::factory()->create(['rol' => 'caja']);
        $this->actingAs($caja)->get(route('finanzas.gastos.comprobante', $gasto))->assertForbidden();
    }

    public function test_html_archivos_invalidos_y_filtros_maliciosos_son_rechazados(): void
    {
        $this->actingAs($this->admin)->post(route('finanzas.gastos.store'), $this->datosGasto([
            'concepto' => '<script>alert(1)</script>',
            'comprobante' => UploadedFile::fake()->create('ataque.exe', 10, 'application/x-msdownload'),
        ]))->assertSessionHasErrors(['concepto', 'comprobante']);

        $this->actingAs($this->admin)
            ->get(route('finanzas.gastos.index', ['categoria_id' => '1 OR 1=1', 'desde' => 'no-fecha']))
            ->assertSessionHasErrors(['categoria_id', 'desde']);

        $this->assertSame(0, Gasto::count());
    }

    public function test_catalogo_no_duplica_compras_de_inventario(): void
    {
        $this->assertDatabaseMissing('categoria_gastos', ['codigo' => 'ingredientes']);
        $this->assertDatabaseMissing('categoria_gastos', ['codigo' => 'empaques']);
        $this->assertFalse(Route::has('finanzas.gastos.destroy'));
        $this->assertFalse(Route::has('finanzas.gastos.edit'));
    }

    public function test_toast_aparece_en_recordatorios_tras_crear_y_toggle(): void
    {
        $this->followingRedirects()
            ->actingAs($this->admin)
            ->post(route('finanzas.gastos.recordatorios.store'), [
                'categoria_id' => CategoriaGasto::where('codigo', 'electricidad')->value('id'),
                'concepto' => 'Electricidad mensual',
                'monto_estimado' => '120.00',
                'metodo_pago' => 'transferencia',
                'unidad_frecuencia' => 'meses',
                'intervalo' => 1,
                'proxima_fecha' => now()->toDateString(),
            ])
            ->assertOk()
            ->assertSee('finance-toast', false)
            ->assertSee('Recordatorio creado correctamente.');

        $recordatorio = RecordatorioGasto::firstOrFail();

        $this->followingRedirects()
            ->actingAs($this->admin)
            ->patch(route('finanzas.gastos.recordatorios.toggle', $recordatorio))
            ->assertOk()
            ->assertSee('finance-toast', false)
            ->assertSee('Estado del recordatorio actualizado.');
    }

    private function datosGasto(array $cambios = []): array
    {
        return array_merge([
            'token_registro' => (string) Str::uuid(),
            'categoria_id' => CategoriaGasto::where('codigo', 'electricidad')->value('id'),
            'concepto' => 'Electricidad del local',
            'fecha' => now()->toDateString(),
            'monto' => '125.50',
            'metodo_pago' => 'efectivo',
        ], $cambios);
    }

    private function crearRecordatorio(array $cambios = []): RecordatorioGasto
    {
        $recordatorio = new RecordatorioGasto(array_merge([
            'categoria_id' => CategoriaGasto::where('codigo', 'electricidad')->value('id'),
            'concepto' => 'Electricidad mensual',
            'monto_estimado' => 120,
            'metodo_pago' => 'transferencia',
            'unidad_frecuencia' => 'meses',
            'intervalo' => 1,
            'proxima_fecha' => now()->toDateString(),
            'activo' => true,
        ], $cambios));
        $recordatorio->user_id = $this->admin->id;
        $recordatorio->save();

        return $recordatorio;
    }
}
