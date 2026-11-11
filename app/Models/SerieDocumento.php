<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class SerieDocumento extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'serie',
        'descripcion',
        'ultimo_numero',
        'year',
        'activo',
    ];

    protected $casts = [
        'ultimo_numero' => 'integer',
        'year' => 'integer',
        'activo' => 'boolean',
    ];

    public function ventas(): HasMany
    {
        return $this->hasMany(Venta::class, 'serie_documento_id');
    }

    public static function obtenerOCrear(string $serie): self
    {
        $year = now()->year;

        return DB::transaction(function () use ($serie, $year) {
            $row = static::where('serie', $serie)
                ->where('year', $year)
                ->lockForUpdate()
                ->first();

            if (! $row) {
                $row = static::create([
                    'serie' => $serie,
                    'descripcion' => 'Tiquete de venta',
                    'ultimo_numero' => 0,
                    'year' => $year,
                    'activo' => true,
                ]);
            }

            return $row;
        });
    }

    public static function generarSiguienteNumero(string $serie): array
    {
        return DB::transaction(function () use ($serie) {
            $row = static::where('serie', $serie)
                ->where('year', now()->year)
                ->lockForUpdate()
                ->first();

            if (! $row) {
                $row = static::create([
                    'serie' => $serie,
                    'descripcion' => 'Tiquete de venta',
                    'ultimo_numero' => 0,
                    'year' => now()->year,
                    'activo' => true,
                ]);
            }

            $row->ultimo_numero++;
            $row->save();

            $numero = sprintf('TIQU-%s-%08d', $row->serie, $row->ultimo_numero);

            return [$row, $numero];
        });
    }
}
