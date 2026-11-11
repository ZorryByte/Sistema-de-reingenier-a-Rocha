<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Proveedor extends Model
{
    protected $table = 'proveedores';

    protected $fillable = [
        'nombre',
        'telefono',
        'email',
        'notas',
    ];

    public static function normalizarNombre(string $nombre): string
    {
        return preg_replace('/\s+/u', ' ', trim($nombre)) ?? '';
    }

    public function compras(): HasMany
    {
        return $this->hasMany(Compra::class, 'proveedor_id');
    }

    public function ingredientes(): HasMany
    {
        return $this->hasMany(Ingrediente::class, 'proveedor_id');
    }

    public function scopeBuscar($query, ?string $texto)
    {
        $texto = trim((string) $texto);

        if ($texto !== '') {
            $termino = str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $texto);

            return $query->where(function ($consulta) use ($termino) {
                $patron = '%'.$termino.'%';

                $consulta->whereRaw("nombre LIKE ? ESCAPE '!'", [$patron])
                    ->orWhereRaw("email LIKE ? ESCAPE '!'", [$patron])
                    ->orWhereRaw("telefono LIKE ? ESCAPE '!'", [$patron]);
            });
        }

        return $query;
    }
}
