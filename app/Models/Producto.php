<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Producto extends Model
{
    protected $fillable = [
        'nombre',
        'tipo_unidad',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function presentaciones(): HasMany
    {
        return $this->hasMany(PresentacionProducto::class, 'producto_id');
    }

    public function presentacionesActivas(): HasMany
    {
        return $this->hasMany(PresentacionProducto::class, 'producto_id')
            ->where('presentaciones_producto.activo', true);
    }

    public function recetas(): HasMany
    {
        return $this->hasMany(Receta::class, 'producto_id');
    }

    public function recetaDefault(): HasOne
    {
        return $this->hasOne(Receta::class, 'producto_id')
            ->where('recetas.activo', true)
            ->whereNull('recetas.presentacion_id');
    }

    public function recetaActiva(): HasOne
    {
        return $this->hasOne(Receta::class, 'producto_id')
            ->where('recetas.activo', true);
    }

    public function scopeActivos($query)
    {
        return $query->where('productos.activo', true);
    }
}
