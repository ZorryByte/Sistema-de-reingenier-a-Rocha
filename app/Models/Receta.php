<?php

namespace App\Models;

use App\Support\UnitFormatter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Receta extends Model
{
    protected $fillable = [
        'producto_id',
        'presentacion_id',
        'categoria_receta_id',
        'nombre',
        'descripcion',
        'tiempo_preparacion',
        'lote_unidades',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'tiempo_preparacion' => 'integer',
        'lote_unidades' => 'integer',
    ];

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    public function presentacion(): BelongsTo
    {
        return $this->belongsTo(PresentacionProducto::class, 'presentacion_id');
    }

    public function categoriaReceta(): BelongsTo
    {
        return $this->belongsTo(CategoriaReceta::class, 'categoria_receta_id');
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(RecetaDetalle::class, 'receta_id');
    }

    public function detallesConIngredientes(): HasMany
    {
        return $this->hasMany(RecetaDetalle::class, 'receta_id')
            ->with('ingrediente');
    }

    public function scopeActivas($query)
    {
        return $query->where('recetas.activo', true);
    }

    public function scopeDefault($query)
    {
        return $query->whereNull('presentacion_id');
    }

    public function scopePorPresentacion($query)
    {
        return $query->whereNotNull('presentacion_id');
    }

    public function scopeConProducto($query)
    {
        return $query->whereNotNull('producto_id');
    }

    public function scopeSinProducto($query)
    {
        return $query->whereNull('producto_id');
    }

    public function esDefault(): bool
    {
        return is_null($this->presentacion_id);
    }

    public function esIndependiente(): bool
    {
        return is_null($this->producto_id);
    }

    public function tipoLabel(): string
    {
        if ($this->esIndependiente()) {
            return 'Independiente';
        }

        return $this->esDefault()
            ? 'Default'
            : $this->presentacion?->nombre ?? 'Presentación';
    }

    public function unidadesPorLote(?PresentacionProducto $presentacion = null): int
    {
        $unidades = $this->lote_unidades ?? $presentacion?->cantidad_unidades ?? 1;

        if ($unidades <= 0) {
            throw new \RuntimeException('unds. por lote debe ser mayor a 0.');
        }

        return (int) $unidades;
    }

    public function calcularConsumoIngredientes(int $cantidadLotes): array
    {
        $this->unidadesPorLote();

        $consumos = [];

        foreach ($this->detallesConIngredientes as $detalle) {
            $ingrediente = $detalle->ingrediente;
            if (! $ingrediente) {
                continue;
            }

            $necesario = $detalle->consumoParaLotes($cantidadLotes);

            $consumos[] = [
                'id' => $ingrediente->id,
                'nombre' => $ingrediente->nombre,
                'unidad' => UnitFormatter::short($ingrediente->unidad_medida),
                'necesario' => $necesario,
                'disponible' => (float) $ingrediente->stock_actual,
                'suficiente' => $ingrediente->stock_actual >= $necesario,
            ];
        }

        return $consumos;
    }

    public function costoPorLote(): float
    {
        $costo = 0.0;
        foreach ($this->detalles as $detalle) {
            if ($detalle->ingrediente) {
                $costo += (float) $detalle->cantidad * (float) $detalle->ingrediente->costo_unitario;
            }
        }

        return $costo;
    }

    public function costoPorUnidad(): float
    {
        return $this->costoPorLote() / $this->unidadesPorLote();
    }
}
