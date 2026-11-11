<?php

namespace App\Models;

use App\Enums\PedidoEstado;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Pedido extends Model
{
    use HasFactory;

    public $timestamps = true;

    const CREATED_AT = 'fecha';

    const UPDATED_AT = 'updated_at';

    protected $fillable = [
        'user_id',
        'cliente_id',
        'estado',
        'tipo',
        'fecha_entrega_estimada',
        'notas',
    ];

    protected $casts = [
        'fecha' => 'datetime',
        'fecha_entrega_estimada' => 'datetime',
        'estado' => PedidoEstado::class,
        'tipo' => 'string',
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(PedidoDetalle::class, 'pedido_id');
    }

    public function venta(): HasOne
    {
        return $this->hasOne(Venta::class, 'pedido_id');
    }

    public function movimientos(): HasMany
    {
        return $this->hasMany(KardexInventario::class, 'pedido_id');
    }

    public function historial(): HasMany
    {
        return $this->hasMany(PedidoHistorial::class, 'pedido_id')->oldest('created_at');
    }

    public function scopeEnPreparacion($query)
    {
        return $query->where('estado', PedidoEstado::EnPreparacion);
    }

    public function scopeTipoOrden($query)
    {
        return $query->where('tipo', 'orden');
    }

    public function scopeTipoPedido($query)
    {
        return $query->where('tipo', 'pedido');
    }

    public function scopeActivos($query)
    {
        return $query->whereIn('estado', [
            PedidoEstado::EnPreparacion,
            PedidoEstado::OrdenLista,
        ]);
    }

    public function esOrden(): bool
    {
        return $this->tipo === 'orden';
    }

    public function esPedido(): bool
    {
        return $this->tipo === 'pedido';
    }
}
