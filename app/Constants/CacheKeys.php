<?php

namespace App\Constants;

final class CacheKeys
{
    public const VENTAS_HOY = 'dash.ventas_stats';

    public const PEDIDOS_PENDIENTES = 'dash.pedidos_pendientes';

    public const STOCK_ISSUES = 'dash.stock_issues';

    public const VENTAS_SEMANA = 'dash.ventas_semana';

    public const ACTIVIDAD_RECIENTE = 'dash.actividad_reciente';

    public const PEDIDOS_ENTREGADOS = 'dash.pedidos_entregados';

    public const TOP_PRODUCTOS = 'dash.top_productos';

    public const PREP_PROMEDIO = 'dash.prep_promedio';

    public const NAVBAR_PEDIDOS = 'navbar.pending_pedidos';

    public const NAVBAR_STOCK = 'navbar.stock_bajo';

    public const ORDENES_ACTIVAS = 'dash.ordenes_activas';

    public const VENTAS_HOY_RANGO = 'dash.ventas_rango.hoy';

    public const VENTAS_MES_RANGO = 'dash.ventas_rango.mes';

    public static function all(): array
    {
        return [
            self::VENTAS_HOY,
            self::PEDIDOS_PENDIENTES,
            self::STOCK_ISSUES,
            self::VENTAS_SEMANA,
            self::ACTIVIDAD_RECIENTE,
            self::PEDIDOS_ENTREGADOS,
            self::TOP_PRODUCTOS,
            self::PREP_PROMEDIO,
            self::NAVBAR_PEDIDOS,
            self::NAVBAR_STOCK,
            self::ORDENES_ACTIVAS,
            self::VENTAS_HOY_RANGO,
            self::VENTAS_MES_RANGO,
        ];
    }

    public static function dashboard(): array
    {
        return [
            self::VENTAS_HOY,
            self::PEDIDOS_PENDIENTES,
            self::STOCK_ISSUES,
            self::VENTAS_SEMANA,
            self::ACTIVIDAD_RECIENTE,
            self::PEDIDOS_ENTREGADOS,
            self::TOP_PRODUCTOS,
            self::PREP_PROMEDIO,
            self::ORDENES_ACTIVAS,
            self::VENTAS_HOY_RANGO,
            self::VENTAS_MES_RANGO,
        ];
    }

    public static function navbar(): array
    {
        return [
            self::NAVBAR_PEDIDOS,
            self::NAVBAR_STOCK,
        ];
    }
}
