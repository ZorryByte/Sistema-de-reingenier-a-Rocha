<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompraDetalle extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'compra_id',
        'ingrediente_id',
        'cantidad',
        'precio_unitario',
        'subtotal',
    ];

    protected $casts = [
        'cantidad' => 'decimal:2',
        'precio_unitario' => 'decimal:4',
        'subtotal' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::updating(fn () => abort(403, 'Los detalles de compra no pueden editarse.'));
        static::deleting(fn () => abort(403, 'Los detalles de compra no pueden eliminarse.'));
    }

    public function compra(): BelongsTo
    {
        return $this->belongsTo(Compra::class, 'compra_id');
    }

    public function ingrediente(): BelongsTo
    {
        return $this->belongsTo(Ingrediente::class)->withTrashed();
    }

    public function calcularSubtotal(): void
    {
        $this->subtotal = round((float) $this->cantidad * (float) $this->precio_unitario, 2);
    }
}
