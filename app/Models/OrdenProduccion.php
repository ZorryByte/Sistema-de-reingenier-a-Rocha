<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class OrdenProduccion extends Model
{
    protected $table = 'ordenes_produccion';

    protected $fillable = [
        'numero_lote',
        'presentacion_id',
        'cantidad',
        'user_id',
        'notas',
    ];

    protected $casts = [
        'cantidad' => 'integer',
    ];

    public function presentacion(): BelongsTo
    {
        return $this->belongsTo(PresentacionProducto::class, 'presentacion_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function scopeBuscar(Builder $query, ?string $texto): Builder
    {
        if (! $texto) {
            return $query;
        }

        $termino = preg_replace('/[%_]/', '\\\\$0', trim($texto));

        return $query->where(function (Builder $query) use ($termino) {
            $query->where('numero_lote', 'LIKE', '%'.$termino.'%')
                ->orWhere('notas', 'LIKE', '%'.$termino.'%')
                ->orWhereHas('presentacion', function (Builder $query) use ($termino) {
                    $query->where('nombre', 'LIKE', '%'.$termino.'%')
                        ->orWhereHas('producto', fn (Builder $query) => $query->where('nombre', 'LIKE', '%'.$termino.'%'));
                })
                ->orWhereHas('user', fn (Builder $query) => $query->where('name', 'LIKE', '%'.$termino.'%'));
        });
    }

    public function scopePorFecha(Builder $query, ?string $desde, ?string $hasta): Builder
    {
        if ($desde) {
            $query->where('created_at', '>=', Carbon::parse($desde)->startOfDay());
        }

        if ($hasta) {
            $query->where('created_at', '<=', Carbon::parse($hasta)->endOfDay());
        }

        return $query;
    }

    public function scopePorProducto(Builder $query, ?int $productoId): Builder
    {
        return $productoId
            ? $query->whereHas('presentacion', fn (Builder $query) => $query->where('producto_id', $productoId))
            : $query;
    }

    public function scopePorPresentacion(Builder $query, ?int $presentacionId): Builder
    {
        return $presentacionId ? $query->where('presentacion_id', $presentacionId) : $query;
    }

    public function scopePorUsuario(Builder $query, ?int $userId): Builder
    {
        return $userId ? $query->where('user_id', $userId) : $query;
    }

    public static function generarNumeroLote(): string
    {
        return DB::transaction(function () {
            $ultimo = static::whereDate('created_at', today())
                ->lockForUpdate()
                ->orderByDesc('id')
                ->first();

            $secuencial = $ultimo
                ? (int) substr($ultimo->numero_lote, -3) + 1
                : 1;

            return 'LOTE-'.now()->format('Y-m-d').'-'.str_pad($secuencial, 3, '0', STR_PAD_LEFT);
        });
    }
}
