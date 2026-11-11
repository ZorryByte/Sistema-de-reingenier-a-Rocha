<?php

namespace Database\Seeders;

use App\Actions\AnularGasto;
use App\Actions\CancelarVenta;
use App\Actions\CrearPedido;
use App\Actions\LiberarReservaStock;
use App\Actions\MarcarOrdenLista;
use App\Actions\ProcesarCompra;
use App\Actions\ProcesarVenta;
use App\Actions\RegistrarEventoPedido;
use App\Actions\RegistrarGasto;
use App\Actions\RegistrarMerma;
use App\Actions\RegistrarProduccion;
use App\Enums\PedidoEstado;
use App\Models\CategoriaGasto;
use App\Models\CategoriaReceta;
use App\Models\Cliente;
use App\Models\ConfiguracionEmpresa;
use App\Models\CuentaContable;
use App\Models\Ingrediente;
use App\Models\KardexInventario;
use App\Models\PresentacionProducto;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\Receta;
use App\Models\RecordatorioGasto;
use App\Models\User;
use App\Support\SaleAmount;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class DemoSeeder extends Seeder
{
    private const PASSWORD = 'PanaderiaDemo2026!';

    public function run(): void
    {
        if (! app()->environment('local', 'testing')) {
            throw new RuntimeException('DemoSeeder está disponible en los entornos local y testing.');
        }

        DB::transaction(function () {
            $admin = User::firstOrCreate(['email' => 'admin@panaderia.example'], [
                'name' => 'Elena Rivera (Demo)',
                'password' => self::PASSWORD,
                'rol' => 'admin',
            ]);

            if (! $admin->wasRecentlyCreated) {
                $this->command?->info('La demostración ya fue cargada.');

                return;
            }

            $cajeros = [];
            foreach ([
                ['caja', 'Lucía Méndez (Demo)', true],
                ['turno', 'Diego López (Demo)', true],
                ['inactivo', 'Ana Torres (Demo)', false],
            ] as [$correo, $nombre, $activo]) {
                $usuario = User::create([
                    'email' => $correo.'@panaderia.example',
                    'name' => $nombre,
                    'password' => self::PASSWORD,
                    'rol' => 'caja',
                    'activo' => $activo,
                ]);
                if ($activo) {
                    $cajeros[] = $usuario;
                }
            }

            $this->prepararCatalogos();

            $clientes = [];
            foreach (['Sofía Ramírez', 'Carlos Aguilar', 'Mariana Flores', 'Café La Esquina'] as $indice => $nombre) {
                $clientes[] = Cliente::create([
                    'nombre' => $nombre.' (Demo)',
                    'telefono' => '+5030000900'.($indice + 1),
                ]);
            }
            Cliente::create([
                'nombre' => 'Distribuidora Las Palmas (Demo)',
                'telefono' => '+50300009005',
                'archivado_at' => now(),
            ]);

            $hoy = now();
            $presentaciones = Carbon::withTestNow($hoy->copy()->subDays(30), fn () => $this->prepararInventario($admin));
            $this->registrarVentas($hoy, $admin, $cajeros, $clientes, $presentaciones);
            $this->registrarGastos($hoy, $admin);
        });
    }

    private function prepararCatalogos(): void
    {
        $this->call(CategoriasRecetaSeeder::class);

        foreach (['nombre' => 'Panadería Demo', 'direccion' => 'San Salvador, El Salvador'] as $clave => $valor) {
            ConfiguracionEmpresa::firstOrCreate(['clave' => $clave], ['valor' => $valor]);
        }

        foreach ([
            ['1010', 'Caja', 'activo'],
            ['1020', 'Bancos', 'activo'],
            ['4010', 'Ventas', 'ingreso'],
            ['5101', 'Electricidad', 'gasto'],
            ['5105', 'Transporte', 'gasto'],
        ] as [$codigo, $nombre, $tipo]) {
            $cuenta = CuentaContable::firstOrCreate(['codigo' => $codigo], [
                'nombre' => $nombre,
                'tipo' => $tipo,
                'activo' => true,
            ]);

            if ($tipo === 'gasto') {
                $electricidad = $codigo === '5101';
                CategoriaGasto::firstOrCreate(['codigo' => $electricidad ? 'electricidad' : 'transporte'], [
                    'nombre' => $nombre,
                    'tipo' => $electricidad ? 'fijo' : 'variable',
                    'cuenta_contable_id' => $cuenta->id,
                    'activo' => true,
                ]);
            }
        }
    }

    private function prepararInventario(User $admin): array
    {
        $molino = Proveedor::create(['nombre' => 'Molino del Valle (Demo)', 'email' => 'molino@panaderia.example']);
        $distribuidora = Proveedor::create(['nombre' => 'Distribuidora La Cosecha (Demo)', 'email' => 'cosecha@panaderia.example']);

        $ingredientes = [];
        $detalles = [];
        foreach ([
            'harina' => ['Harina de trigo', 'harinas', 2000, 24000, 0.0015],
            'azucar' => ['Azúcar morena', 'endulzantes', 1000, 10000, 0.002],
            'mantequilla' => ['Mantequilla', 'grasas', 3500, 3700, 0.008],
        ] as $clave => [$nombre, $categoria, $minimo, $cantidad, $precio]) {
            $ingrediente = Ingrediente::create([
                'nombre' => $nombre.' (Demo)',
                'categoria' => $categoria,
                'proveedor_id' => $molino->id,
                'unidad_medida' => 'g',
                'stock_minimo' => $minimo,
            ]);
            $ingredientes[$clave] = $ingrediente->id;
            $detalles[] = [
                'ingrediente_id' => $ingrediente->id,
                'cantidad' => $cantidad,
                'precio_unitario' => $precio,
            ];
        }
        $compra = new ProcesarCompra;
        $compra->execute(['proveedor_id' => $molino->id, 'detalles' => $detalles], $admin->id);
        $compra->execute([
            'proveedor_id' => $distribuidora->id,
            'detalles' => [['ingrediente_id' => $ingredientes['harina'], 'cantidad' => 4000, 'precio_unitario' => 0.0018]],
        ], $admin->id);

        (new RegistrarMerma)->execute([
            'ingrediente_id' => $ingredientes['mantequilla'],
            'cantidad' => 150.5,
            'categoria' => 'manipulacion',
            'notas' => 'Pérdida durante el porcionado (Demo)',
        ], $admin->id);

        $presentaciones = [];
        foreach ([
            ['Pan de leche', 'Unidad', 'unidad', 2.5, 'panes', 24, 40, ['harina' => 800, 'azucar' => 80, 'mantequilla' => 100]],
            ['Galleta de mantequilla', 'Bolsa de 6', 'unidad', 6, 'galletas', 12, 30, ['harina' => 500, 'azucar' => 240, 'mantequilla' => 260]],
            ['Bizcocho de vainilla', 'Por libra', 'peso', 12, 'pasteles', 8, 55, ['harina' => 550, 'azucar' => 300, 'mantequilla' => 170]],
        ] as [$nombre, $formato, $tipo, $precio, $categoria, $rendimiento, $minutos, $receta]) {
            $producto = Producto::create(['nombre' => $nombre.' (Demo)', 'activo' => true]);
            $presentacion = PresentacionProducto::create([
                'producto_id' => $producto->id,
                'nombre' => $formato,
                'tipo_venta' => $tipo,
                'cantidad_unidades' => $formato === 'Bolsa de 6' ? 6 : 1,
                'precio' => $precio,
                'stock_minimo' => 5,
            ]);
            $ficha = Receta::create([
                'producto_id' => $producto->id,
                'presentacion_id' => $presentacion->id,
                'categoria_receta_id' => CategoriaReceta::where('slug', $categoria)->firstOrFail()->id,
                'nombre' => 'Receta de '.$nombre.' (Demo)',
                'lote_unidades' => $rendimiento,
                'tiempo_preparacion' => $minutos,
                'activo' => true,
            ]);
            foreach ($receta as $clave => $cantidad) {
                $ficha->detalles()->create(['ingrediente_id' => $ingredientes[$clave], 'cantidad' => $cantidad]);
            }
            (new RegistrarProduccion)->execute([
                'presentacion_id' => $presentacion->id,
                'cantidad' => 6,
                'notas' => 'Lote para la demostración',
            ], $admin->id);
            $presentaciones[] = $presentacion;
        }

        KardexInventario::where('user_id', $admin->id)->update(['fecha' => now()]);

        return $presentaciones;
    }

    private function registrarVentas(Carbon $hoy, User $admin, array $cajeros, array $clientes, array $presentaciones): void
    {
        $ventas = new ProcesarVenta;
        $pedidos = new CrearPedido;
        $metodos = ['efectivo', 'tarjeta', 'transferencia'];

        foreach ([28, 24, 19, 14, 10, 7, 5, 3, 2, 1, 0] as $indice => $dias) {
            Carbon::withTestNow($hoy->copy()->subDays($dias), function () use ($indice, $ventas, $pedidos, $metodos, $admin, $cajeros, $clientes, $presentaciones) {
                $cajero = $cajeros[$indice % count($cajeros)];
                $cliente = $clientes[$indice % count($clientes)];
                $presentacion = $presentaciones[$indice % count($presentaciones)];
                $cantidad = $presentacion->tipo_venta === 'peso' ? '1.5' : 2 + $indice % 4;
                $items = [['presentacion_id' => $presentacion->id, 'cantidad' => $cantidad]];
                $pago = [
                    'metodo' => $metodos[$indice % count($metodos)],
                    'monto' => SaleAmount::lineCents($presentacion->precio, $cantidad) / 100,
                ];
                $data = ['items' => $items, 'pagos' => [$pago], 'notas' => 'Venta de demostración'];
                $pedido = null;

                if ($indice % 2 === 0) {
                    $pedido = $pedidos->execute([
                        'tipo' => 'pedido',
                        'cliente_id' => $cliente->id,
                        'items' => $items,
                        'fecha_entrega_estimada' => now(),
                    ], $cajero->id);
                    $data['pedido_id'] = $pedido->id;
                } else {
                    $data['cliente_id'] = $cliente->id;
                }

                $venta = $ventas->execute($data, $cajero->id);
                if ($indice === 5) {
                    (new CancelarVenta)->execute($venta, $admin);
                }

                // Estas fechas tienen valores por defecto del motor de base de datos.
                $venta->forceFill(['created_at' => now()])->save();
                $venta->pagos()->update(['created_at' => now()]);
                $venta->movimientos()->update(['fecha' => now()]);
                if ($pedido) {
                    $pedido->movimientos()->update(['fecha' => now()]);
                }
            });
        }

        foreach (['pedido', 'orden', 'pedido'] as $indice => $tipo) {
            $pedido = $pedidos->execute([
                'tipo' => $tipo,
                'cliente_id' => $clientes[$indice]->id,
                'fecha_entrega_estimada' => $hoy->copy()->addDay(),
                'items' => [['presentacion_id' => $presentaciones[$indice]->id, 'cantidad' => 2]],
                'notas' => 'Pedido de demostración',
            ], $cajeros[0]->id);

            if ($tipo === 'orden') {
                (new MarcarOrdenLista)->execute($pedido, $cajeros[0]->id);
            }
            if ($indice === 2) {
                (new LiberarReservaStock)->execute($pedido, $cajeros[0]->id);
                $pedido->update(['estado' => PedidoEstado::Cancelado]);
                (new RegistrarEventoPedido)->execute($pedido, 'cancelado', $cajeros[0]->id, PedidoEstado::EnPreparacion, PedidoEstado::Cancelado);
            }
            $pedido->movimientos()->update(['fecha' => now()]);
        }
    }

    private function registrarGastos(Carbon $hoy, User $admin): void
    {
        $electricidad = CategoriaGasto::where('codigo', 'electricidad')->firstOrFail();
        $transporte = CategoriaGasto::where('codigo', 'transporte')->firstOrFail();
        $recordatorio = new RecordatorioGasto([
            'categoria_id' => $electricidad->id,
            'concepto' => 'Electricidad del local (Demo)',
            'monto_estimado' => 45,
            'metodo_pago' => 'transferencia',
            'unidad_frecuencia' => 'meses',
            'intervalo' => 1,
            'proxima_fecha' => $hoy->toDateString(),
            'activo' => true,
        ]);
        $recordatorio->user_id = $admin->id;
        $recordatorio->save();

        foreach ([[22, 8.75], [12, 11.50], [3, 6.25], [1, 14.50]] as [$dias, $monto]) {
            Carbon::withTestNow($hoy->copy()->subDays($dias), function () use ($dias, $monto, $transporte, $admin) {
                $gasto = (new RegistrarGasto)->execute([
                    'token_registro' => (string) Str::uuid(),
                    'categoria_id' => $transporte->id,
                    'concepto' => 'Reparto de pedidos (Demo)',
                    'fecha' => now()->toDateString(),
                    'monto' => $monto,
                    'metodo_pago' => 'efectivo',
                ], $admin->id);
                if ($dias === 3) {
                    (new AnularGasto)->execute($gasto, 'Registro duplicado de ejemplo', $admin->id);
                }
            });
        }
        (new RegistrarGasto)->execute([
            'token_registro' => (string) Str::uuid(),
            'recordatorio_gasto_id' => $recordatorio->id,
            'periodo_recordatorio' => $hoy->toDateString(),
            'concepto' => $recordatorio->concepto,
            'fecha' => $hoy->toDateString(),
            'monto' => 47.25,
            'metodo_pago' => 'transferencia',
        ], $admin->id);
    }
}
