<?php

use App\Http\Controllers\BackupController;
use App\Http\Controllers\AyudaController;
use App\Http\Controllers\CategoriaRecetaController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\CompraController;
use App\Http\Controllers\ControlInventarioController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FinanzasController;
use App\Http\Controllers\GastoController;
use App\Http\Controllers\IngredienteControlInventarioController;
use App\Http\Controllers\IngredienteController;
use App\Http\Controllers\LibroContableController;
use App\Http\Controllers\LibroMayorController;
use App\Http\Controllers\PedidoController;
use App\Http\Controllers\ProduccionController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\ProveedorController;
use App\Http\Controllers\RecetaController;
use App\Http\Controllers\RecordatorioGastoController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ValorInventarioController;
use App\Http\Controllers\VentaController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('dashboard'))->name('home');

// Rutas accesibles para ambos roles: admin y caja
Route::middleware('auth')->group(function () {
    Route::get('clientes/buscar', [ClienteController::class, 'buscar'])
        ->middleware('throttle:60,1')->name('clientes.buscar');
    Route::post('clientes/alta-rapida', [ClienteController::class, 'altaRapida'])
        ->middleware('throttle:20,1')->name('clientes.alta-rapida');

    // Dashboard
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('dashboard/ventas', [DashboardController::class, 'ventas'])
        ->name('dashboard.ventas');

    // Pedidos
    Route::resource('pedidos', PedidoController::class)->only(['index', 'create', 'show', 'edit']);
    Route::post('pedidos', [PedidoController::class, 'store'])
        ->middleware('throttle:30,1')->name('pedidos.store');
    Route::match(['put', 'patch'], 'pedidos/{pedido}', [PedidoController::class, 'update'])
        ->middleware('throttle:30,1')->name('pedidos.update');
    Route::post('pedidos/{pedido}/entregar', [PedidoController::class, 'entregar'])
        ->middleware('throttle:20,1')->name('pedidos.entregar');
    Route::patch('pedidos/{pedido}/cancelar', [PedidoController::class, 'cancelar'])
        ->middleware('throttle:20,1')->name('pedidos.cancelar');
    Route::post('pedidos/{pedido}/marcar-lista', [PedidoController::class, 'marcarLista'])
        ->middleware('throttle:20,1')
        ->name('pedidos.marcarLista');
    Route::get('pedidos/{pedido}/pdf', [PedidoController::class, 'pdf'])->name('pedidos.pdf');

    // Ventas
    Route::resource('ventas', VentaController::class)->only(['index', 'create', 'store', 'show']);
    Route::patch('ventas/{venta}/cancelar', [VentaController::class, 'cancelar'])->name('ventas.cancelar');
    Route::get('ventas/{venta}/pdf', [VentaController::class, 'pdf'])->name('ventas.pdf');

    // Ayuda
    Route::get('ayuda', AyudaController::class)->name('ayuda');

});

// Rutas exclusivas solo para admin
Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::resource('clientes', ClienteController::class)
        ->parameters(['clientes' => 'cliente'])->except('destroy');
    Route::patch('clientes/{cliente}/archivar', [ClienteController::class, 'archivar'])->name('clientes.archivar');
    Route::patch('clientes/{cliente}/reactivar', [ClienteController::class, 'reactivar'])->name('clientes.reactivar');

    // Productos
    Route::resource('productos', ProductoController::class);

    // Recetas
    Route::get('recetas/categorias', [CategoriaRecetaController::class, 'index'])->name('recetas.categorias.index');
    Route::post('recetas/categorias', [CategoriaRecetaController::class, 'store'])->name('recetas.categorias.store');
    Route::patch('recetas/categorias/{categoriaReceta}', [CategoriaRecetaController::class, 'update'])->name('recetas.categorias.update');
    Route::delete('recetas/categorias/{categoriaReceta}', [CategoriaRecetaController::class, 'destroy'])->name('recetas.categorias.destroy');
    Route::patch('recetas/{receta}/activar', [RecetaController::class, 'activar'])->name('recetas.activar');
    Route::resource('recetas', RecetaController::class);

    // Ingredientes
    Route::resource('ingredientes', IngredienteController::class);
    Route::get('ingredientes/control-inventario/create', [IngredienteControlInventarioController::class, 'create'])
        ->name('ingredientes.control-inventario.create');
    Route::post('ingredientes/control-inventario', [IngredienteControlInventarioController::class, 'store'])
        ->name('ingredientes.control-inventario.store');

    // Control de inventario
    Route::get('control-inventario', [ControlInventarioController::class, 'index'])->name('control-inventario.index');
    Route::get('control-inventario/{movimiento}', [ControlInventarioController::class, 'show'])->name('control-inventario.show');
    Route::post('control-inventario/{movimiento}/revertir', [ControlInventarioController::class, 'revertir'])->name('control-inventario.revertir');
    Route::get('ingredientes/{ingrediente}/historial', [ControlInventarioController::class, 'historial'])
        ->name('ingredientes.historial')
        ->withTrashed();

    // Produccion
    Route::resource('produccion', ProduccionController::class)->only(['index', 'create', 'store']);
    Route::get('produccion/preview', [ProduccionController::class, 'preview'])->name('produccion.preview');

    // Gestion de Usuarios (CRUD con delete en vez de destroy)
    Route::get('usuarios', [UserController::class, 'index'])->name('usuarios.index');
    Route::post('usuarios', [UserController::class, 'store'])->name('usuarios.store');
    Route::get('usuarios/{user}/edit', [UserController::class, 'edit'])->name('usuarios.edit');
    Route::patch('usuarios/{user}', [UserController::class, 'update'])->name('usuarios.update');
    Route::delete('usuarios/{user}', [UserController::class, 'softDelete'])->name('usuarios.delete');
    Route::patch('usuarios/{user}/toggle-activo', [UserController::class, 'toggleActivo'])->name('usuarios.toggle-activo');

    // Backups (admin only)
    Route::get('backups', [BackupController::class, 'index'])->name('backups.index');
    Route::get('backups/configure', [BackupController::class, 'configure'])->name('backups.configure');
    Route::post('backups/configure', [BackupController::class, 'configureStore'])->name('backups.configure.store');
    Route::get('backups/google/connect', [BackupController::class, 'googleConnect'])->name('backups.google.connect');
    Route::get('backups/google/callback', [BackupController::class, 'googleCallback'])->name('backups.google.callback');
    Route::get('backups/google/check', [BackupController::class, 'googleCheck'])->name('backups.google.check');
    Route::post('backups/google/disconnect', [BackupController::class, 'googleDisconnect'])->name('backups.google.disconnect');
    Route::post('backups', [BackupController::class, 'store'])->name('backups.store');
    Route::get('backups/{backup}/download', [BackupController::class, 'download'])->name('backups.download');
    Route::get('backups/{backup}/restore', [BackupController::class, 'restore'])->name('backups.restore');
    Route::post('backups/{backup}/restore', [BackupController::class, 'restoreExecute'])->name('backups.restore.store');
    Route::post('backups/{backup}/verify', [BackupController::class, 'verify'])->name('backups.verify');
    Route::get('backups/{backup}/preview', [BackupController::class, 'preview'])->name('backups.preview');
    Route::delete('backups/{backup}', [BackupController::class, 'destroy'])->name('backups.destroy');

    // Finanzas / Contaduría
    Route::prefix('finanzas')->name('finanzas.')->group(function () {
        Route::get('/', [FinanzasController::class, 'index'])->name('index');
        Route::get('resumen', [FinanzasController::class, 'resumen'])->name('resumen');
        Route::get('proveedores/buscar', [ProveedorController::class, 'search'])
            ->middleware('throttle:60,1')
            ->name('proveedores.search');
        Route::get('proveedores/create', [ProveedorController::class, 'create'])->name('proveedores.create');
        Route::post('proveedores', [ProveedorController::class, 'store'])
            ->middleware('throttle:10,1')
            ->name('proveedores.store');
        Route::get('proveedores/{proveedor}/edit', [ProveedorController::class, 'edit'])->name('proveedores.edit');
        Route::match(['put', 'patch'], 'proveedores/{proveedor}', [ProveedorController::class, 'update'])
            ->middleware('throttle:10,1')
            ->name('proveedores.update');
        Route::get('proveedores', [ProveedorController::class, 'index'])->name('proveedores.index');
        Route::get('proveedores/{proveedor}', [ProveedorController::class, 'show'])->name('proveedores.show');
        Route::resource('compras', CompraController::class)->only(['index', 'show']);
        Route::get('gastos/recordatorios', [RecordatorioGastoController::class, 'index'])
            ->name('gastos.recordatorios.index');
        Route::get('gastos/recordatorios/create', [RecordatorioGastoController::class, 'create'])
            ->name('gastos.recordatorios.create');
        Route::post('gastos/recordatorios', [RecordatorioGastoController::class, 'store'])
            ->middleware('throttle:10,1')
            ->name('gastos.recordatorios.store');
        Route::get('gastos/recordatorios/{recordatorio}/edit', [RecordatorioGastoController::class, 'edit'])
            ->name('gastos.recordatorios.edit');
        Route::put('gastos/recordatorios/{recordatorio}', [RecordatorioGastoController::class, 'update'])
            ->middleware('throttle:10,1')
            ->name('gastos.recordatorios.update');
        Route::patch('gastos/recordatorios/{recordatorio}/estado', [RecordatorioGastoController::class, 'toggle'])
            ->middleware('throttle:10,1')
            ->name('gastos.recordatorios.toggle');
        Route::get('gastos', [GastoController::class, 'index'])->name('gastos.index');
        Route::get('gastos/create', [GastoController::class, 'create'])->name('gastos.create');
        Route::post('gastos', [GastoController::class, 'store'])
            ->middleware('throttle:10,1')
            ->name('gastos.store');
        Route::get('gastos/{gasto}/comprobante', [GastoController::class, 'comprobante'])
            ->middleware('throttle:30,1')
            ->name('gastos.comprobante');
        Route::patch('gastos/{gasto}/anular', [GastoController::class, 'anular'])
            ->middleware('throttle:5,1')
            ->name('gastos.anular');
        Route::get('gastos/{gasto}', [GastoController::class, 'show'])->name('gastos.show');
        Route::get('libro-contable', [LibroContableController::class, 'index'])->name('libro-contable.index');
        Route::get('libro-contable/{asiento}', [LibroContableController::class, 'show'])->name('libro-contable.show');
        Route::get('libro-mayor', LibroMayorController::class)->name('libro-mayor.index');

        Route::get('reportes/resultados', [ReporteController::class, 'resultados'])->name('reportes.resultados');
        Route::get('reportes/rentabilidad', [ReporteController::class, 'rentabilidad'])->name('reportes.rentabilidad');
        Route::get('reportes/precios-proveedor', [ReporteController::class, 'preciosProveedor'])->name('reportes.precios-proveedor');
        Route::get('reportes/gastos-categoria', [ReporteController::class, 'gastosCategoria'])->name('reportes.gastos-categoria');
        Route::get('reportes/tendencia-ventas', [ReporteController::class, 'tendenciaVentas'])->name('reportes.tendencia-ventas');
        Route::get('reportes/valor-inventario', ValorInventarioController::class)->name('reportes.valor-inventario');
    });

});

require __DIR__.'/auth.php';
