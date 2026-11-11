<?php

namespace App\Models;

use App\Support\SaleAmount;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VentaDetalle extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'venta_id',
        'presentacion_id',
        'cantidad',
        'precio_unitario',
        'descuento',
        'subtotal',
        'notas',
    ];

    protected $casts = [
        'cantidad' => 'decimal:4',
        'precio_unitario' => 'decimal:2',
        'descuento' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class, 'venta_id');
    }

    public function presentacion(): BelongsTo
    {
        return $this->belongsTo(PresentacionProducto::class, 'presentacion_id')
            ->withDefault(['nombre' => 'Producto eliminado', 'precio' => 0]);
    }

    public function calcularSubtotal(): void
    {
        $gross = SaleAmount::grossCents($this->precio_unitario, $this->cantidad);
        $discount = min(SaleAmount::cents($this->descuento), $gross);
        $this->descuento = $discount / 100;
        $this->subtotal = ($gross - $discount) / 100;
    }
}
