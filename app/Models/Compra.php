<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Compra extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'proveedor_id',
        'fecha',
        'notas',
    ];

    protected $casts = [
        'fecha' => 'datetime',
        'total' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::updating(fn () => abort(403, 'Las compras no pueden editarse.'));
        static::deleting(fn () => abort(403, 'Las compras no pueden eliminarse.'));
    }

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class, 'proveedor_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(CompraDetalle::class, 'compra_id');
    }

    public function movimientos(): HasMany
    {
        return $this->hasMany(KardexInventario::class, 'compra_id');
    }

    public function scopePorFecha($query, $desde = null, $hasta = null)
    {
        if ($desde) {
            $query->whereDate('fecha', '>=', $desde);
        }
        if ($hasta) {
            $query->whereDate('fecha', '<=', $hasta);
        }

        return $query;
    }

    public function scopePorProveedor($query, ?int $proveedorId)
    {
        if ($proveedorId) {
            $query->where('proveedor_id', $proveedorId);
        }

        return $query;
    }

    public static function calcularTotal(array $detalles): float
    {
        $total = 0;
        foreach ($detalles as $d) {
            $total += (float) $d['cantidad'] * (float) $d['precio_unitario'];
        }

        return round($total, 2);
    }
}
