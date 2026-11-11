<?php

namespace App\Models;

use App\Traits\HasStock;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ingrediente extends Model
{
    use HasStock, SoftDeletes;

    public const CATEGORIAS = [
        'harinas' => 'Harinas',
        'lacteos' => 'Lácteos',
        'endulzantes' => 'Endulzantes',
        'leudantes' => 'Leudantes',
        'grasas' => 'Grasas',
        'saborizantes' => 'Saborizantes',
        'decoracion' => 'Decoración',
        'empaques' => 'Empaques',
        'refrigerados' => 'Refrigerados',
        'congelados' => 'Congelados',
        'otros' => 'Otros',
    ];

    protected $fillable = [
        'nombre',
        'categoria',
        'proveedor_id',
        'unidad_medida',
        'stock_actual',
        'stock_minimo',
        'costo_unitario',
    ];

    protected $casts = [
        'stock_actual' => 'decimal:4',
        'stock_minimo' => 'decimal:2',
        'costo_unitario' => 'decimal:4',
    ];

    public function movimientos(): HasMany
    {
        return $this->hasMany(KardexInventario::class, 'ingrediente_id');
    }

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class, 'proveedor_id');
    }

    public function movimientosActivos(): HasMany
    {
        return $this->hasMany(KardexInventario::class, 'ingrediente_id')
            ->whereNull('reverted_at');
    }

    public function recetaDetalles(): HasMany
    {
        return $this->hasMany(RecetaDetalle::class, 'ingrediente_id');
    }

    public function categoriaLabel(): string
    {
        return self::CATEGORIAS[$this->categoria] ?? 'Otros';
    }

    public function scopeBuscar($query, ?string $texto)
    {
        if ($texto) {
            $termino = str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $texto);

            return $query->whereRaw("nombre LIKE ? ESCAPE '!'", ['%'.$termino.'%']);
        }

        return $query;
    }

    public function scopeCategoria($query, ?string $cat)
    {
        if ($cat && $cat !== 'all' && array_key_exists($cat, self::CATEGORIAS)) {
            return $query->where('categoria', $cat);
        }

        return $query;
    }

    public function scopeStockBajo($query)
    {
        return $query->whereColumn('stock_actual', '<=', 'stock_minimo')
            ->where('stock_minimo', '>', 0);
    }
}
