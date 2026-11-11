<?php

namespace App\Support;

use App\Models\User;

class AyudaCatalogo
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function para(User $user): array
    {
        return array_values(array_filter(
            $this->articulos(),
            fn (array $articulo) => $articulo['acceso'] === 'compartido' || $user->esAdmin(),
        ));
    }

    /**
     * @return array<int, string>
     */
    public function frecuentesPara(User $user): array
    {
        if ($user->esAdmin()) {
            return ['revisar-jornada', 'gestionar-pedidos', 'controlar-inventario', 'registrar-gasto'];
        }

        return ['revisar-jornada', 'crear-pedido', 'gestionar-pedidos', 'registrar-venta'];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function articulos(): array
    {
        return [
            $this->articulo(
                'revisar-jornada',
                'Primeros pasos',
                'Revisar la jornada',
                'Consulta ventas, pedidos próximos y alertas antes de comenzar.',
                ['dashboard', 'inicio', 'turno', 'alertas', 'hoy'],
                [
                    'Abre el Dashboard.',
                    'Revisa los pedidos y órdenes que necesitan atención.',
                    'Confirma si hay alertas que puedan afectar el trabajo.',
                    'Usa una acción rápida para continuar con la primera tarea.',
                ],
                'Ya sabes qué necesita atención durante el turno.',
                'dashboard',
                'Ir al Dashboard',
                ['crear-pedido', 'gestionar-pedidos'],
            ),
            $this->articulo(
                'crear-pedido',
                'Pedidos y ventas',
                'Crear un pedido u orden',
                'Registra productos para entrega futura o preparación inmediata.',
                ['crear', 'pedido', 'orden', 'apartar', 'reservar', 'cliente', 'fecha'],
                [
                    'Abre Comandas y elige Nuevo pedido u orden.',
                    'Selecciona si se entregará después o se preparará ahora.',
                    'Agrega los productos y revisa las cantidades.',
                    'Completa los datos del cliente cuando sean necesarios.',
                    'Guarda y confirma que aparezca en la bandeja.',
                ],
                'El registro queda visible y el sistema reserva lo necesario.',
                'pedidos.create',
                'Crear pedido u orden',
                ['gestionar-pedidos', 'entregar-pedido'],
                'Si no hay existencias suficientes, revisa las cantidades antes de guardar.',
            ),
            $this->articulo(
                'gestionar-pedidos',
                'Pedidos y ventas',
                'Dar seguimiento a pedidos',
                'Busca, actualiza o cancela pedidos desde una sola bandeja.',
                ['buscar', 'editar', 'cancelar', 'filtrar', 'sincronizar', 'orden lista', 'historial'],
                [
                    'Abre Comandas.',
                    'Usa los filtros para encontrar el registro.',
                    'Abre el detalle para revisar productos, estado e historial.',
                    'Elige Editar, Marcar lista o Cancelar según corresponda.',
                    'Confirma que el estado cambió en la bandeja.',
                ],
                'La bandeja y la línea de tiempo muestran el cambio realizado.',
                'pedidos.index',
                'Ver comandas',
                ['crear-pedido', 'entregar-pedido'],
                'La bandeja se actualiza automáticamente. Si pierde conexión, espera el siguiente intento.',
            ),
            $this->articulo(
                'entregar-pedido',
                'Pedidos y ventas',
                'Entregar un pedido',
                'Cierra el pedido, registra el pago y genera la venta.',
                ['entregar', 'cobrar', 'pago', 'orden lista', 'venta', 'comprobante'],
                [
                    'Abre el pedido y confirma que los productos estén listos.',
                    'Marca la orden como lista cuando corresponda.',
                    'Elige Entregar y registra la forma de pago.',
                    'Revisa el total antes de confirmar.',
                    'Abre la venta creada o descarga el comprobante.',
                ],
                'El pedido queda entregado y vinculado con su venta.',
                'pedidos.index',
                'Buscar pedido',
                ['gestionar-pedidos', 'registrar-venta'],
                'Confirma el pago antes de entregar. La operación modifica existencias.',
            ),
            $this->articulo(
                'registrar-venta',
                'Pedidos y ventas',
                'Registrar una venta',
                'Cobra una venta directa y guarda sus formas de pago.',
                ['vender', 'cobrar', 'pago', 'efectivo', 'tarjeta', 'ticket', 'pdf'],
                [
                    'Abre Ventas y elige Nueva venta.',
                    'Agrega los productos y sus cantidades.',
                    'Registra una o varias formas de pago.',
                    'Comprueba que los pagos cubran el total.',
                    'Guarda y entrega el comprobante si lo necesitan.',
                ],
                'La venta queda registrada y las existencias se actualizan.',
                'ventas.create',
                'Registrar venta',
                ['entregar-pedido'],
                'Revisa productos y pagos antes de guardar. La venta afecta el inventario.',
            ),
            $this->articulo(
                'gestionar-productos',
                'Catálogo y producción',
                'Preparar productos para vender',
                'Crea productos, presentaciones, precios y costos disponibles para ventas.',
                ['producto', 'presentación', 'precio', 'costo', 'stock mínimo', 'catálogo'],
                [
                    'Abre Productos y elige Nuevo producto.',
                    'Escribe el nombre y completa la información básica.',
                    'Agrega cada presentación con su precio y unidad.',
                    'Revisa el costo y el nivel mínimo de existencias.',
                    'Guarda y confirma que aparezca en el catálogo.',
                ],
                'El producto queda disponible para recetas, pedidos y ventas.',
                'productos.index',
                'Ver productos',
                ['gestionar-ingredientes', 'preparar-recetas'],
                null,
                'admin',
            ),
            $this->articulo(
                'gestionar-ingredientes',
                'Catálogo y producción',
                'Registrar ingredientes y costos',
                'Mantén cantidades, unidades, proveedores y costos actualizados.',
                ['ingrediente', 'materia prima', 'unidad', 'costo', 'proveedor', 'stock'],
                [
                    'Abre Ingredientes y elige Nuevo ingrediente.',
                    'Indica la unidad usada para medirlo.',
                    'Registra la cantidad y el costo actual.',
                    'Asocia un proveedor cuando corresponda.',
                    'Guarda y revisa su historial.',
                ],
                'El ingrediente queda listo para compras, recetas y producción.',
                'ingredientes.index',
                'Ver ingredientes',
                ['gestionar-proveedores', 'preparar-recetas', 'controlar-inventario'],
                null,
                'admin',
            ),
            $this->articulo(
                'preparar-recetas',
                'Catálogo y producción',
                'Crear y activar recetas',
                'Define qué ingredientes consume cada producto durante la producción.',
                ['receta', 'categoría', 'ingredientes', 'lote', 'activar', 'costo'],
                [
                    'Abre Recetas y crea las categorías que necesites.',
                    'Elige Nueva receta y selecciona el producto.',
                    'Indica cuántas unidades produce el lote.',
                    'Agrega los ingredientes con sus cantidades.',
                    'Guarda y activa la receta correcta.',
                ],
                'La receta activa permite calcular consumo y costo.',
                'recetas.index',
                'Ver recetas',
                ['gestionar-ingredientes', 'registrar-produccion'],
                'Solo la receta activa se utiliza para calcular el consumo.',
                'admin',
            ),
            $this->articulo(
                'registrar-produccion',
                'Catálogo y producción',
                'Registrar una producción',
                'Convierte ingredientes en productos y conserva el número de lote.',
                ['producir', 'producción', 'lote', 'consumo', 'vista previa'],
                [
                    'Abre Producción y elige Registrar producción.',
                    'Selecciona el producto y la cantidad de lotes.',
                    'Revisa la vista previa del consumo.',
                    'Confirma que existan ingredientes suficientes.',
                    'Guarda y revisa el movimiento creado.',
                ],
                'Los productos aumentan y los ingredientes consumidos disminuyen.',
                'produccion.index',
                'Ver producción',
                ['preparar-recetas', 'controlar-inventario'],
                'No confirmes si la vista previa muestra un consumo inesperado.',
                'admin',
            ),
            $this->articulo(
                'controlar-inventario',
                'Inventario y control',
                'Revisar y corregir existencias',
                'Consulta movimientos y registra ajustes, entradas o mermas.',
                ['inventario', 'kardex', 'no cuadra', 'ajuste', 'merma', 'revertir', 'historial'],
                [
                    'Abre Control de inventario y aplica los filtros necesarios.',
                    'Abre un movimiento para revisar su origen.',
                    'Usa el historial del ingrediente para encontrar la diferencia.',
                    'Registra un ajuste o merma con una nota clara.',
                    'Usa Revertir solo cuando el movimiento lo permita.',
                ],
                'El historial conserva el registro original y cualquier corrección.',
                'control-inventario.index',
                'Abrir control de inventario',
                ['gestionar-ingredientes', 'registrar-produccion'],
                'No borres el rastro de un error. Registra una corrección que explique lo ocurrido.',
                'admin',
            ),
            $this->articulo(
                'gestionar-proveedores',
                'Finanzas',
                'Gestionar proveedores y compras',
                'Consulta proveedores, recepciones e historial de precios.',
                ['proveedor', 'compra', 'recepción', 'precio', 'contacto'],
                [
                    'Abre Finanzas y entra en Proveedores.',
                    'Agrega o actualiza los datos del proveedor.',
                    'Abre su detalle para revisar ingredientes y compras.',
                    'Consulta Compras para ver recepciones anteriores.',
                    'Compara el historial antes de preparar una compra futura.',
                ],
                'La información queda disponible para seguimiento y comparación.',
                'finanzas.proveedores.index',
                'Ver proveedores',
                ['gestionar-ingredientes', 'revisar-finanzas'],
                'La creación de compras no está disponible; utiliza esta sección para consulta.',
                'admin',
            ),
            $this->articulo(
                'revisar-finanzas',
                'Finanzas',
                'Revisar las finanzas',
                'Consulta ingresos, gastos, compras y señales importantes del negocio.',
                ['finanzas', 'ingresos', 'gastos', 'compras', 'resultado', 'resumen'],
                [
                    'Abre Finanzas.',
                    'Selecciona el período que deseas revisar.',
                    'Compara ventas, compras y gastos.',
                    'Abre el detalle de cualquier dato que necesites confirmar.',
                    'Usa los reportes para ampliar el análisis.',
                ],
                'Obtienes una lectura operativa del período seleccionado.',
                'finanzas.index',
                'Abrir Finanzas',
                ['registrar-gasto', 'consultar-contabilidad'],
                'Los resultados ayudan a operar el negocio, pero no sustituyen un cierre contable formal.',
                'admin',
            ),
            $this->articulo(
                'registrar-gasto',
                'Finanzas',
                'Registrar un gasto',
                'Guarda pagos externos con categoría, comprobante y seguimiento.',
                ['gasto', 'pago', 'factura', 'comprobante', 'recordatorio', 'anular'],
                [
                    'Abre Gastos y elige Registrar gasto.',
                    'Completa fecha, concepto, categoría y monto.',
                    'Agrega proveedor y comprobante cuando existan.',
                    'Guarda y revisa la trazabilidad del registro.',
                    'Crea un recordatorio si el pago se repite.',
                ],
                'El gasto queda registrado y aparece en el libro contable.',
                'finanzas.gastos.index',
                'Ver gastos',
                ['revisar-finanzas', 'consultar-contabilidad'],
                'Ingredientes y empaques deben registrarse como compras, no como gastos externos.',
                'admin',
            ),
            $this->articulo(
                'consultar-contabilidad',
                'Finanzas',
                'Consultar libro y reportes',
                'Comprueba el origen de los movimientos y analiza resultados.',
                ['libro contable', 'asiento', 'reporte', 'rentabilidad', 'tendencia', 'valor inventario'],
                [
                    'Abre el Libro contable para revisar los asientos.',
                    'Selecciona un asiento para ver su origen y detalle.',
                    'Vuelve a Finanzas y elige el reporte que necesitas.',
                    'Define el período o los filtros disponibles.',
                    'Contrasta el resultado con ventas, compras o gastos.',
                ],
                'Puedes seguir cada cifra hasta el movimiento que la originó.',
                'finanzas.libro-contable.index',
                'Abrir libro contable',
                ['revisar-finanzas', 'registrar-gasto'],
                null,
                'admin',
            ),
            $this->articulo(
                'gestionar-usuarios',
                'Administración',
                'Gestionar usuarios',
                'Crea cuentas y controla quién puede ingresar al sistema.',
                ['usuario', 'cuenta', 'contraseña', 'acceso', 'activar', 'desactivar'],
                [
                    'Abre Gestión de usuarios.',
                    'Crea una cuenta o abre una existente.',
                    'Completa el nombre, correo y tipo de acceso.',
                    'Activa o desactiva la cuenta según corresponda.',
                    'Guarda y confirma el estado en la lista.',
                ],
                'La cuenta queda disponible o bloqueada según el estado elegido.',
                'usuarios.index',
                'Gestionar usuarios',
                [],
                'No desactives tu propia cuenta durante una sesión de trabajo.',
                'admin',
            ),
            $this->articulo(
                'gestionar-respaldos',
                'Administración',
                'Proteger y restaurar la información',
                'Crea, verifica, descarga o restaura respaldos del sistema.',
                ['respaldo', 'backup', 'google drive', 'restaurar', 'descargar', 'verificar'],
                [
                    'Abre Respaldos y revisa el estado de protección.',
                    'Crea un respaldo o configura la programación automática.',
                    'Verifica el respaldo antes de depender de él.',
                    'Descárgalo o consérvalo en el almacenamiento configurado.',
                    'Usa Restaurar únicamente después de revisar el contenido.',
                ],
                'El historial muestra el estado y ubicación de cada respaldo.',
                'backups.index',
                'Abrir respaldos',
                [],
                'Restaurar reemplaza información actual. Verifica el respaldo antes de confirmar.',
                'admin',
            ),
        ];
    }

    /**
     * @param  array<int, string>  $palabrasClave
     * @param  array<int, string>  $pasos
     * @param  array<int, string>  $relacionados
     * @return array<string, mixed>
     */
    private function articulo(
        string $slug,
        string $categoria,
        string $titulo,
        string $resumen,
        array $palabrasClave,
        array $pasos,
        string $resultado,
        string $ruta,
        string $accion,
        array $relacionados = [],
        ?string $advertencia = null,
        string $acceso = 'compartido',
    ): array {
        return compact(
            'slug',
            'categoria',
            'titulo',
            'resumen',
            'palabrasClave',
            'pasos',
            'resultado',
            'ruta',
            'accion',
            'relacionados',
            'advertencia',
            'acceso',
        );
    }
}
