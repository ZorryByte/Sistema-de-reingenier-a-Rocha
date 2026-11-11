<?php

namespace App\Models;

use App\Enums\GastoTipo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;

class CategoriaGasto extends Model
{
    protected $fillable = [
        'codigo',
        'cuenta_contable_id',
        'nombre',
        'tipo',
        'activo',
    ];

    protected $casts = [
        'tipo' => GastoTipo::class,
        'activo' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::updating(function (self $categoria) {
            if ($categoria->isDirty('activo') && ! $categoria->activo && $categoria->recordatorios()->where('activo', true)->exists()) {
                throw ValidationException::withMessages([
                    'activo' => 'Desactiva primero sus recordatorios.',
                ]);
            }
        });
    }

    public function cuenta(): BelongsTo
    {
        return $this->belongsTo(CuentaContable::class, 'cuenta_contable_id');
    }

    public function gastos(): HasMany
    {
        return $this->hasMany(Gasto::class, 'categoria_id');
    }

    public function recordatorios(): HasMany
    {
        return $this->hasMany(RecordatorioGasto::class, 'categoria_id');
    }

    public function scopeActivas($query)
    {
        return $query->where('activo', true);
    }
}
