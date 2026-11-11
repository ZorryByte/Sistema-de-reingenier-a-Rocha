<?php

namespace App\Models;

use App\Enums\GastoFrecuenciaUnidad;
use App\Enums\GastoMetodoPago;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RecordatorioGasto extends Model
{
    protected $table = 'recordatorios_gasto';

    protected $fillable = [
        'categoria_id',
        'proveedor_id',
        'concepto',
        'monto_estimado',
        'metodo_pago',
        'unidad_frecuencia',
        'intervalo',
        'proxima_fecha',
        'activo',
    ];

    protected $casts = [
        'monto_estimado' => 'decimal:2',
        'metodo_pago' => GastoMetodoPago::class,
        'unidad_frecuencia' => GastoFrecuenciaUnidad::class,
        'proxima_fecha' => 'date',
        'activo' => 'boolean',
    ];

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(CategoriaGasto::class, 'categoria_id');
    }

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class, 'proveedor_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id')->withDefault();
    }

    public function gastos(): HasMany
    {
        return $this->hasMany(Gasto::class, 'recordatorio_gasto_id');
    }

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    public function scopePendientes($query)
    {
        return $query->activos()->whereDate('proxima_fecha', '<=', today());
    }
}
