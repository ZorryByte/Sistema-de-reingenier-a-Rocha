<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PedidoHistorial extends Model
{
    public $timestamps = false;

    protected $table = 'pedido_historial';

    protected $fillable = [
        'pedido_id',
        'user_id',
        'evento',
        'estado_anterior',
        'estado_nuevo',
        'cambios',
        'created_at',
    ];

    protected $casts = [
        'cambios' => 'array',
        'created_at' => 'datetime',
    ];

    public function pedido(): BelongsTo
    {
        return $this->belongsTo(Pedido::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
