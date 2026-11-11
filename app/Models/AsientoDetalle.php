<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AsientoDetalle extends Model
{
    protected $table = 'asiento_detalles';

    protected $fillable = [
        'asiento_contable_id',
        'cuenta_contable_id',
        'debe',
        'haber',
        'descripcion',
    ];

    protected $casts = [
        'debe' => 'decimal:2',
        'haber' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::updating(fn () => abort(403, 'Los detalles contables no se editan.'));
        static::deleting(fn () => abort(403, 'Los detalles contables no se eliminan.'));
    }

    public function asiento(): BelongsTo
    {
        return $this->belongsTo(AsientoContable::class, 'asiento_contable_id');
    }

    public function cuenta(): BelongsTo
    {
        return $this->belongsTo(CuentaContable::class, 'cuenta_contable_id');
    }
}
