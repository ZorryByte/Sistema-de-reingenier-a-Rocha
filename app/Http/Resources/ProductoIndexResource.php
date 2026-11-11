<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductoIndexResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'tipo_unidad' => $this->tipo_unidad,
            'activo' => $this->activo,
            'show_url' => route('productos.show', $this),
            'edit_url' => route('productos.edit', $this),
            'destroy_url' => route('productos.destroy', $this),
            'receta_default' => $this->recetaDefault ? [
                'id' => $this->recetaDefault->id,
                'nombre' => $this->recetaDefault->nombre,
                'edit_url' => route('recetas.edit', $this->recetaDefault),
            ] : null,
            'presentaciones_activas' => $this->presentacionesActivas->map(fn ($pres) => [
                'id' => $pres->id,
                'nombre' => $pres->nombre,
                'cantidad_unidades' => (float) $pres->cantidad_unidades,
                'precio' => (float) $pres->precio,
                'stock_actual' => (float) $pres->stock_actual,
                'stock_minimo' => (float) $pres->stock_minimo,
                'receta_default' => $pres->recetaActiva ? [
                    'nombre' => $pres->recetaActiva->nombre,
                    'edit_url' => route('recetas.edit', $pres->recetaActiva),
                ] : null,
            ]),
        ];
    }
}
