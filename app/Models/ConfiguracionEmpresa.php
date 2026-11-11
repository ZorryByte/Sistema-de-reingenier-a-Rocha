<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConfiguracionEmpresa extends Model
{
    protected $table = 'configuracion_empresa';

    public $timestamps = false;

    protected $fillable = [
        'clave',
        'valor',
    ];

    public static function get(string $clave, string $default = ''): string
    {
        return static::where('clave', $clave)->value('valor') ?? $default;
    }

    public static function set(string $clave, string $valor): void
    {
        static::updateOrCreate(
            ['clave' => $clave],
            ['valor' => $valor]
        );
    }
}
