<?php

namespace App\Models;

use App\Enums\VentaEstado;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Venta extends Model
{
    public $timestamps = false;

    const CREATED_AT = 'created_at'; // Audit timestamp for sales

    const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'pedido_id',
        'cliente_id',
        'total',
        'estado',
        'notas',
        'numero_factura',
        'serie_documento_id',
        'pdf_path',
    ];

    protected $casts = [
        'total' => 'decimal:2',
        'created_at' => 'datetime',
        'estado' => VentaEstado::class,
    ];

    public function serieDocumento(): BelongsTo
    {
        return $this->belongsTo(SerieDocumento::class, 'serie_documento_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function pedido(): BelongsTo
    {
        return $this->belongsTo(Pedido::class, 'pedido_id');
    }

    public function clienteDirecto(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function clienteAsociado(): ?Cliente
    {
        return $this->pedido_id === null ? $this->clienteDirecto : $this->pedido?->cliente;
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(VentaDetalle::class, 'venta_id');
    }

    public function pagos(): HasMany
    {
        return $this->hasMany(VentaPago::class, 'venta_id');
    }

    public function movimientos(): HasMany
    {
        return $this->hasMany(KardexInventario::class, 'venta_id');
    }

    public function asientos(): MorphMany
    {
        return $this->morphMany(AsientoContable::class, 'referencia');
    }
}
