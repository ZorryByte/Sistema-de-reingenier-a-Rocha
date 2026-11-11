<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BackupSnapshot extends Model
{
    protected $fillable = [
        'uuid',
        'nivel',
        'ruta_archivo',
        'storage_disk',
        'storage_path',
        'cloud_estado',
        'cloud_disk',
        'cloud_path',
        'cloud_uploaded_at',
        'checksum',
        'tamano_bytes',
        'estado',
        'mensaje_error',
        'iniciado_en',
        'completado_en',
    ];

    protected $casts = [
        'iniciado_en' => 'datetime',
        'completado_en' => 'datetime',
        'cloud_uploaded_at' => 'datetime',
        'tamano_bytes' => 'integer',
    ];

    public function logs(): HasMany
    {
        return $this->hasMany(BackupLog::class, 'snapshot_id');
    }

    public function estaCompletado(): bool
    {
        return $this->estado === 'completado';
    }

    public function estaFallido(): bool
    {
        return $this->estado === 'fallido';
    }

    public function estaEnProgreso(): bool
    {
        return $this->estado === 'en_progreso';
    }

    public function getTamanoHumanoAttribute(): string
    {
        $bytes = $this->tamano_bytes;
        $units = ['B', 'KB', 'MB', 'GB'];

        for ($i = 0; $bytes >= 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2).' '.$units[$i];
    }

    public function getNivelBadgeAttribute(): string
    {
        return match ($this->nivel) {
            'diario' => 'badge-info',
            'semanal' => 'badge-warning',
            'mensual' => 'badge-success',
            default => 'badge-default',
        };
    }

    public function scopeCompletados($query)
    {
        return $query->where('estado', 'completado');
    }

    public function scopePorNivel($query, string $nivel)
    {
        return $query->where('nivel', $nivel);
    }
}
