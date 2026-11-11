<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;

class Cliente extends Model
{
    protected $table = 'clientes';

    protected $fillable = ['nombre', 'telefono', 'archivado_at'];

    protected $casts = ['archivado_at' => 'datetime'];

    public function pedidos(): HasMany
    {
        return $this->hasMany(Pedido::class);
    }

    public function ventasDirectas(): HasMany
    {
        return $this->hasMany(Venta::class)->whereNull('pedido_id');
    }

    public function scopeActivos(Builder $query): Builder
    {
        return $query->whereNull('archivado_at');
    }

    public function scopeBuscar(Builder $query, ?string $texto): Builder
    {
        $texto = trim($texto ?? '');

        if ($texto === '') {
            return $query;
        }

        $patron = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $texto).'%';
        $numero = preg_replace('/[\s()+.-]/u', '', $texto);

        return $query->where(function (Builder $consulta) use ($patron, $numero) {
            $consulta->whereRaw("nombre LIKE ? ESCAPE '!'", [$patron]);

            if (ctype_digit($numero ?? '')) {
                $consulta->orWhere('telefono', 'like', '%'.$numero.'%');
            }
        });
    }

    public function getTelefonoVisibleAttribute(): string
    {
        return preg_match('/^\+503(\d{4})(\d{4})$/D', $this->telefono, $partes)
            ? "+503 {$partes[1]}-{$partes[2]}"
            : $this->telefono;
    }

    public static function bloquearActivo(?int $id): ?int
    {
        if ($id === null) {
            return null;
        }

        $cliente = self::query()->activos()->lockForUpdate()->find($id);

        if ($cliente === null) {
            throw ValidationException::withMessages(['cliente_id' => 'Selecciona un cliente activo.']);
        }

        return $cliente->id;
    }
}
