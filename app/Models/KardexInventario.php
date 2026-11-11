<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KardexInventario extends Model
{
    protected $table = 'kardex_inventario';

    public $timestamps = false;

    const CREATED_AT = 'fecha'; // Fecha de negocio

    const UPDATED_AT = null;

    protected $fillable = [
        'tipo',
        'categoria',
        'ingrediente_id',
        'cantidad',
        'stock_anterior',
        'stock_resultante',
        'costo_anterior',
        'costo_resultante',
        'venta_id',
        'pedido_id',
        'compra_id',
        'referencia_type',
        'referencia_id',
        'user_id',
        'user_reverso_id',
        'notas',
        'origen_movimiento',
        'reversible',
        'fecha',
        'reverted_at',
    ];

    protected $casts = [
        'cantidad' => 'decimal:4',
        'stock_anterior' => 'decimal:4',
        'stock_resultante' => 'decimal:4',
        'costo_anterior' => 'decimal:4',
        'costo_resultante' => 'decimal:4',
        'reversible' => 'boolean',
        'fecha' => 'datetime',
        'reverted_at' => 'datetime',
    ];

    public function ingrediente(): BelongsTo
    {
        return $this->belongsTo(Ingrediente::class, 'ingrediente_id')->withTrashed();
    }

    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class, 'venta_id');
    }

    public function pedido(): BelongsTo
    {
        return $this->belongsTo(Pedido::class, 'pedido_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id')
            ->withDefault();
    }

    public function usuarioReverso(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_reverso_id')
            ->withDefault();
    }

    public function scopeActivos(Builder $query): Builder
    {
        return $query->whereNull('reverted_at');
    }

    public function scopeRevertidos(Builder $query): Builder
    {
        return $query->whereNotNull('reverted_at');
    }

    public function scopePorTipo(Builder $query, ?string $tipo): Builder
    {
        return $tipo ? $query->where('tipo', $tipo) : $query;
    }

    public function scopePorIngrediente(Builder $query, ?int $ingredienteId): Builder
    {
        return $ingredienteId ? $query->where('ingrediente_id', $ingredienteId) : $query;
    }

    public function scopePorFecha(Builder $query, ?string $desde, ?string $hasta): Builder
    {
        if ($desde) {
            $query->where('fecha', '>=', Carbon::parse($desde)->startOfDay());
        }
        if ($hasta) {
            $query->where('fecha', '<=', Carbon::parse($hasta)->endOfDay());
        }

        return $query;
    }

    public function scopePorCategoria(Builder $query, ?string $categoria): Builder
    {
        return $categoria ? $query->where('categoria', $categoria) : $query;
    }

    public function scopeCreaciones(Builder $query): Builder
    {
        return $query->where('tipo', 'creacion');
    }

    public function esReversible(): bool
    {
        return $this->reversible && $this->origen_movimiento !== 'reserva' && is_null($this->reverted_at);
    }

    public function getTipoColor(): string
    {
        return match ($this->tipo) {
            'entrada' => 'text-emerald-700 bg-emerald-50 border-emerald-200',
            'salida' => 'text-red-700 bg-red-50 border-red-200',
            'venta' => 'text-rose-800 bg-rose-50 border-rose-200',
            'ajuste' => 'text-amber-700 bg-amber-50 border-amber-200',
            'merma' => 'text-gray-600 bg-gray-50 border-gray-200',
            'creacion' => 'text-violet-700 bg-violet-50 border-violet-200',
            'actualizacion' => 'text-blue-700 bg-blue-50 border-blue-200',
            'produccion' => 'text-orange-700 bg-orange-50 border-orange-200',
            'reserva' => 'text-cyan-700 bg-cyan-50 border-cyan-200',
            default => 'text-gray-500 bg-gray-50 border-gray-200',
        };
    }

    public function getTipoLabel(): string
    {
        return match ($this->tipo) {
            'creacion' => 'Creación',
            'actualizacion' => 'Actualización',
            default => ucfirst($this->tipo),
        };
    }

    public function getReferenciaLabel(): string
    {
        if (! $this->referencia_type || ! $this->referencia_id) {
            return '';
        }

        $model = $this->referencia_type;
        if (! class_exists($model)) {
            return class_basename($this->referencia_type);
        }

        if ($this->relationLoaded('referencia')) {
            $entity = $this->referencia;
        } else {
            $entity = (new $model)->find($this->referencia_id);
        }

        return $entity ? $entity->nombre ?? class_basename($model).' #'.$this->referencia_id : class_basename($model).' #'.$this->referencia_id;
    }

    public function getCategoriaLabel(): string
    {
        return match ($this->categoria) {
            'producto' => 'Producto',
            'presentacion' => 'Presentación',
            'receta' => 'Receta',
            'caducidad' => 'Caducidad',
            'manipulacion' => 'Manipulación',
            default => ucfirst($this->categoria ?? ''),
        };
    }

    public function getOrigenLabel(): string
    {
        return match ($this->origen_movimiento) {
            'manual' => 'Manual',
            'venta' => 'Venta',
            'ajuste' => 'Ajuste',
            'reversion' => 'Reversión',
            'liberacion' => 'Liberación',
            'sistema' => 'Sistema',
            'produccion' => 'Producción',
            'reserva' => 'Reserva',
            'cancelacion' => 'Cancelación',
            'compra' => 'Compra',
            default => 'Desconocido',
        };
    }

    protected static function booted(): void
    {
        static::updating(function (self $model) {
            $dirty = array_keys($model->getDirty());
            $allowed = ['reverted_at', 'user_reverso_id'];
            $blocked = array_diff($dirty, $allowed);

            if (! empty($blocked)) {
                abort(403, 'El valor de inventario no puede editarse. Crea un ajuste.');
            }
        });

        static::deleting(function () {
            abort(403, 'El valor de inventario no puede eliminarse.');
        });
    }
}
