<?php

namespace Tests\Feature;

use App\Models\Compra;
use App\Models\Ingrediente;
use App\Models\Proveedor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ProveedorFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create();
    }

    public function test_admin_puede_abrir_alta_de_proveedor(): void
    {
        $this->actingAs($this->admin)
            ->get(route('finanzas.proveedores.create'))
            ->assertOk()
            ->assertSee('Agregar proveedor');
    }

    public function test_caja_no_puede_gestionar_proveedores(): void
    {
        $caja = User::factory()->create(['rol' => 'caja']);
        $proveedor = Proveedor::create(['nombre' => 'Proveedor seguro']);

        $this->actingAs($caja)->get(route('finanzas.proveedores.create'))->assertForbidden();
        $this->actingAs($caja)->getJson(route('finanzas.proveedores.search'))->assertForbidden();
        $this->actingAs($caja)->post(route('finanzas.proveedores.store'), [
            'nombre' => 'Intento',
        ])->assertForbidden();
        $this->actingAs($caja)->patch(route('finanzas.proveedores.update', $proveedor), [
            'nombre' => 'Intento',
        ])->assertForbidden();
    }

    public function test_invitado_es_redirigido_al_login(): void
    {
        $this->get(route('finanzas.proveedores.create'))->assertRedirect(route('login'));
        $this->post(route('finanzas.proveedores.store'), ['nombre' => 'Intento'])
            ->assertRedirect(route('login'));
    }

    public function test_busqueda_solo_expone_id_y_nombre(): void
    {
        $proveedor = Proveedor::create([
            'nombre' => 'Harinas Seguras',
            'telefono' => '5555-0000',
            'email' => 'privado@example.com',
            'notas' => 'Dato interno',
        ]);

        $this->actingAs($this->admin)
            ->getJson(route('finanzas.proveedores.search', ['q' => 'Harinas']))
            ->assertOk()
            ->assertExactJson([
                'data' => [[
                    'id' => $proveedor->id,
                    'nombre' => 'Harinas Seguras',
                ]],
            ]);
    }

    public function test_busqueda_limita_resultados(): void
    {
        foreach (range(1, 25) as $numero) {
            Proveedor::create(['nombre' => "Proveedor {$numero}"]);
        }

        $response = $this->actingAs($this->admin)
            ->getJson(route('finanzas.proveedores.search'))
            ->assertOk();

        $this->assertCount(20, $response->json('data'));
    }

    public function test_busqueda_no_es_afectada_por_inyeccion(): void
    {
        Proveedor::create(['nombre' => 'Proveedor visible']);

        $this->actingAs($this->admin)
            ->getJson(route('finanzas.proveedores.search', ['q' => "' OR 1=1 --"]))
            ->assertOk()
            ->assertExactJson(['data' => []]);
    }

    public function test_busqueda_trata_comodines_como_texto(): void
    {
        Proveedor::create(['nombre' => 'Proveedor 100% Real']);
        Proveedor::create(['nombre' => 'Proveedor comun']);

        $this->actingAs($this->admin)
            ->getJson(route('finanzas.proveedores.search', ['q' => '%']))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.nombre', 'Proveedor 100% Real');
    }

    public function test_alta_directa_crea_proveedor_normalizado(): void
    {
        $this->actingAs($this->admin)
            ->post(route('finanzas.proveedores.store'), [
                'nombre' => '  Harinas   del Centro  ',
                'email' => 'COMPRAS@EXAMPLE.COM',
                'contexto' => 'proveedores',
            ])
            ->assertRedirect(route('finanzas.proveedores.index'));

        $this->assertDatabaseHas('proveedores', [
            'nombre' => 'Harinas del Centro',
            'email' => 'compras@example.com',
        ]);
    }

    public function test_nombre_duplicado_reutiliza_proveedor(): void
    {
        $proveedor = Proveedor::create(['nombre' => 'Proveedor Central']);

        $response = $this->actingAs($this->admin)
            ->post(route('finanzas.proveedores.store'), [
                'nombre' => ' proveedor   central ',
                'contexto' => 'ingrediente_create',
            ]);

        $response->assertRedirect(route('ingredientes.create', [
            'proveedor_id' => $proveedor->id,
            'restaurar' => 1,
        ]));
        $this->assertSame(1, Proveedor::count());
    }

    public function test_duplicado_desde_finanzas_abre_existente(): void
    {
        $proveedor = Proveedor::create(['nombre' => 'Proveedor Existente']);

        $this->actingAs($this->admin)
            ->post(route('finanzas.proveedores.store'), [
                'nombre' => 'proveedor existente',
                'contexto' => 'proveedores',
            ])
            ->assertRedirect(route('finanzas.proveedores.show', $proveedor));

        $this->assertSame(1, Proveedor::count());
    }

    public function test_retorno_malicioso_es_rechazado(): void
    {
        $this->actingAs($this->admin)
            ->post(route('finanzas.proveedores.store'), [
                'nombre' => 'Proveedor externo',
                'contexto' => 'https://example.com',
            ])
            ->assertSessionHasErrors('contexto');

        $this->assertDatabaseMissing('proveedores', ['nombre' => 'Proveedor externo']);
    }

    public function test_nombre_con_compras_no_puede_cambiar(): void
    {
        $proveedor = Proveedor::create(['nombre' => 'Nombre Historico']);
        $compra = new Compra([
            'proveedor_id' => $proveedor->id,
            'fecha' => now(),
        ]);
        $compra->user_id = $this->admin->id;
        $compra->total = 20;
        $compra->save();

        $this->actingAs($this->admin)
            ->patch(route('finanzas.proveedores.update', $proveedor), [
                'nombre' => 'Nombre Nuevo',
                'telefono' => '5555-1111',
            ])
            ->assertSessionHasErrors('nombre');

        $this->assertDatabaseHas('proveedores', [
            'id' => $proveedor->id,
            'nombre' => 'Nombre Historico',
        ]);
    }

    public function test_contacto_si_puede_actualizarse(): void
    {
        $proveedor = Proveedor::create(['nombre' => 'Proveedor Estable']);

        $this->actingAs($this->admin)
            ->patch(route('finanzas.proveedores.update', $proveedor), [
                'nombre' => 'Proveedor Estable',
                'telefono' => '+502 5555-1111',
                'email' => 'contacto@example.com',
            ])
            ->assertRedirect(route('finanzas.proveedores.show', $proveedor));

        $this->assertDatabaseHas('proveedores', [
            'id' => $proveedor->id,
            'telefono' => '+502 5555-1111',
            'email' => 'contacto@example.com',
        ]);
    }

    public function test_html_en_notas_es_rechazado(): void
    {
        $this->actingAs($this->admin)
            ->post(route('finanzas.proveedores.store'), [
                'nombre' => 'Proveedor XSS',
                'notas' => '<script>alert(1)</script>',
            ])
            ->assertSessionHasErrors('notas');
    }

    public function test_campos_no_permitidos_no_se_asignan(): void
    {
        $this->actingAs($this->admin)
            ->post(route('finanzas.proveedores.store'), [
                'id' => 999,
                'nombre' => 'Proveedor Controlado',
                'deleted_at' => now(),
            ]);

        $proveedor = Proveedor::where('nombre', 'Proveedor Controlado')->firstOrFail();
        $this->assertNotSame(999, $proveedor->id);
        $this->assertFalse(Schema::hasColumn('proveedores', 'deleted_at'));
    }

    public function test_ingrediente_sin_proveedor_guarda_null(): void
    {
        $this->actingAs($this->admin)
            ->post(route('ingredientes.store'), [
                'nombre' => 'Ingrediente libre',
                'categoria' => 'otros',
                'proveedor_id' => '',
                'unidad_medida' => 'unidad',
                'stock_actual' => 0,
                'stock_minimo' => 0,
            ]);

        $this->assertDatabaseHas('ingredientes', [
            'nombre' => 'Ingrediente libre',
            'proveedor_id' => null,
        ]);
    }

    public function test_formulario_ingrediente_incluye_selector_y_alta(): void
    {
        $this->actingAs($this->admin)
            ->get(route('ingredientes.create'))
            ->assertOk()
            ->assertSee('Sin proveedor')
            ->assertSee('Agregar proveedor')
            ->assertSee('proveedorCombobox')
            ->assertSee('aria-activedescendant', false)
            ->assertSee('Selecciona un proveedor de la lista');

        $this->assertTrue(Route::has('finanzas.proveedores.search'));
    }

    public function test_directorio_muestra_cobertura_de_ingredientes(): void
    {
        $proveedor = Proveedor::create(['nombre' => 'Proveedor con cobertura']);
        Ingrediente::create([
            'nombre' => 'Harina asignada',
            'categoria' => 'harinas',
            'proveedor_id' => $proveedor->id,
            'unidad_medida' => 'g',
        ]);
        Ingrediente::create([
            'nombre' => 'Sal sin proveedor',
            'categoria' => 'otros',
            'unidad_medida' => 'g',
        ]);

        $this->actingAs($this->admin)
            ->get(route('finanzas.proveedores.index'))
            ->assertOk()
            ->assertSee('50.0%')
            ->assertSee('1 sin proveedor')
            ->assertSee('1 ingredientes - 0 compras');
    }

    public function test_no_existe_ruta_para_eliminar_proveedor(): void
    {
        $this->assertFalse(Route::has('finanzas.proveedores.destroy'));
    }
}
