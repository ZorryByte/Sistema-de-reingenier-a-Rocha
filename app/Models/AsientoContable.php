<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AsientoContable extends Model
{
    protected $table = 'asientos_contables';

    protected $fillable = [
        'fecha_contable',
        'origen',
        'estado',
        'referencia_type',
        'referencia_id',
        'user_id',
        'asiento_reversado_id',
        'descripcion',
    ];

    protected $casts = [
        'fecha_contable' => 'datetime',
    ];

    public function detalles(): HasMany
    {
        return $this->hasMany(AsientoDetalle::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id')->withDefault();
    }

    public function asientoReversado(): BelongsTo
    {
        return $this->belongsTo(self::class, 'asiento_reversado_id');
    }

    public function referencia(): MorphTo
    {
        return $this->morphTo();
    }

    protected static function booted(): void
    {
        static::updating(fn () => abort(403, 'Los asientos contables no pueden editarse. Crea una reversión.'));
        static::deleting(fn () => abort(403, 'Los asientos contables no pueden eliminarse.'));
    }
}
