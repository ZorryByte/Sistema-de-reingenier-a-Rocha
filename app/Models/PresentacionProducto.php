<?php

namespace App\Models;

use App\Traits\HasStock;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PresentacionProducto extends Model
{
    use HasStock;

    protected $table = 'presentaciones_producto';

    protected $fillable = [
        'producto_id',
        'nombre',
        'cantidad_unidades',
        'precio',
        'stock_actual',
        'stock_minimo',
        'activo',
        'tipo_venta',
    ];

    protected $casts = [
        'precio' => 'decimal:2',
        'cantidad_unidades' => 'decimal:4',
        'stock_actual' => 'decimal:4',
        'stock_minimo' => 'decimal:2',
        'activo' => 'boolean',
    ];

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    public function ventaDetalles(): HasMany
    {
        return $this->hasMany(VentaDetalle::class, 'presentacion_id');
    }

    public function pedidoDetalles(): HasMany
    {
        return $this->hasMany(PedidoDetalle::class, 'presentacion_id');
    }

    public function recetas(): HasMany
    {
        return $this->hasMany(Receta::class, 'presentacion_id');
    }

    public function recetaActiva(): HasOne
    {
        return $this->hasOne(Receta::class, 'presentacion_id')
            ->where('recetas.activo', true);
    }

    // Shared scope for catalog query
    public function scopeConProducto($query)
    {
        return $query
            ->join('productos', 'presentaciones_producto.producto_id', '=', 'productos.id')
            ->select('presentaciones_producto.*')
            ->orderBy('productos.nombre')
            ->orderBy('presentaciones_producto.nombre')
            ->with('producto');
    }

    public function scopeActivas($query)
    {
        return $query->where('presentaciones_producto.activo', true);
    }

    public function tipoVentaLabel(): string
    {
        return match ($this->tipo_venta) {
            'unidad' => 'Por und.',
            'peso' => 'Por peso',
            'porcion' => 'Por porción',
            default => 'Por und.',
        };
    }
}
