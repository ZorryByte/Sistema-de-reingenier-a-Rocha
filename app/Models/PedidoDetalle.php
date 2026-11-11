<?php

namespace App\Models;

use App\Support\SaleAmount;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PedidoDetalle extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'pedido_id',
        'presentacion_id',
        'cantidad',
        'notas',
    ];

    protected $casts = [
        'cantidad' => 'decimal:2',
    ];

    public function subtotalCents(): int
    {
        return SaleAmount::lineCents($this->presentacion->precio, $this->cantidad);
    }

    public function pedido(): BelongsTo
    {
        return $this->belongsTo(Pedido::class, 'pedido_id');
    }

    public function presentacion(): BelongsTo
    {
        return $this->belongsTo(PresentacionProducto::class, 'presentacion_id')
            ->withDefault(['nombre' => 'Producto eliminado', 'precio' => 0]);
    }
}
