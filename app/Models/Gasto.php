<?php

namespace App\Models;

use App\Enums\GastoEstado;
use App\Enums\GastoMetodoPago;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Gasto extends Model
{
    protected $fillable = [
        'categoria_id',
        'proveedor_id',
        'recordatorio_gasto_id',
        'token_registro',
        'concepto',
        'numero_documento',
        'fecha',
        'monto',
        'metodo_pago',
        'nota',
        'periodo_recordatorio',
        'monto_estimado_recordatorio',
        'comprobante_disk',
        'comprobante_path',
        'comprobante_nombre',
        'comprobante_mime',
        'comprobante_tamano',
    ];

    protected $casts = [
        'fecha' => 'datetime',
        'monto' => 'decimal:2',
        'monto_estimado_recordatorio' => 'decimal:2',
        'metodo_pago' => GastoMetodoPago::class,
        'estado' => GastoEstado::class,
        'anulado_at' => 'datetime',
        'comprobante_tamano' => 'integer',
    ];

    protected static function booted(): void
    {
        static::updating(fn () => abort(403, 'Los gastos registrados no se editan.'));
        static::deleting(fn () => abort(403, 'Los gastos no pueden eliminarse.'));
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(CategoriaGasto::class, 'categoria_id');
    }

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class, 'proveedor_id');
    }

    public function recordatorio(): BelongsTo
    {
        return $this->belongsTo(RecordatorioGasto::class, 'recordatorio_gasto_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function usuarioAnulacion(): BelongsTo
    {
        return $this->belongsTo(User::class, 'anulado_por')->withDefault();
    }

    public function asientos(): MorphMany
    {
        return $this->morphMany(AsientoContable::class, 'referencia');
    }

    public function scopeRegistrados($query)
    {
        return $query->where('estado', GastoEstado::Registrado->value);
    }

    public function scopePorFecha($query, $desde = null, $hasta = null)
    {
        return $query
            ->when($desde, fn ($q) => $q->whereDate('fecha', '>=', $desde))
            ->when($hasta, fn ($q) => $q->whereDate('fecha', '<=', $hasta));
    }

    public function scopePorCategoria($query, ?int $categoriaId)
    {
        return $query->when($categoriaId, fn ($q) => $q->where('categoria_id', $categoriaId));
    }
}
