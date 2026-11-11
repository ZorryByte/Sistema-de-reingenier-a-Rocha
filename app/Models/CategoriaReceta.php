<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class CategoriaReceta extends Model
{
    protected $table = 'categorias_receta';

    protected $fillable = [
        'nombre',
        'slug',
    ];

    public function recetas(): HasMany
    {
        return $this->hasMany(Receta::class, 'categoria_receta_id');
    }

    public static function normalizarNombre(string $nombre): string
    {
        return preg_replace('/\s+/', ' ', trim($nombre));
    }

    public static function slugDesdeNombre(string $nombre): string
    {
        return Str::slug(self::normalizarNombre($nombre));
    }
}
