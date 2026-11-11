<?php

namespace App\Http\Requests;

use App\Models\Receta;
use Illuminate\Foundation\Http\FormRequest;

class ProductoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $presentaciones = $this->input('presentaciones', []);
        foreach ($presentaciones as $index => $p) {
            if (($p['tipo_venta'] ?? 'unidad') === 'unidad' && empty($p['cantidad_unidades'])) {
                $presentaciones[$index]['cantidad_unidades'] = 1;
            }
        }
        $this->merge(['presentaciones' => $presentaciones]);
    }

    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:100'],
            'tipo_unidad' => ['required', 'in:unidad,g,ml,oz'],
            'activo' => ['boolean'],
            'receta_id' => ['nullable', 'exists:recetas,id'],

            'presentaciones' => ['required', 'array', 'min:1'],
            'presentaciones.*.id' => ['nullable', 'exists:presentaciones_producto,id'],
            'presentaciones.*.nombre' => ['required', 'string', 'max:60'],
            'presentaciones.*.cantidad_unidades' => ['sometimes', 'numeric', 'min:0.0001', function ($attribute, $value, $fail) {
                if (preg_match('/presentaciones\.(\d+)\.cantidad_unidades/', $attribute, $m)) {
                    $tipoVenta = $this->input("presentaciones.{$m[1]}.tipo_venta");
                    if ($tipoVenta === 'unidad' && $value !== null && $value != 1) {
                        $fail('Para ventas por und., la cantidad debe ser 1.');
                    }
                    if ($tipoVenta !== 'unidad' && $value === null) {
                        $fail('La cantidad es obligatoria.');
                    }
                }
            }],
            'presentaciones.*.precio' => ['required', 'numeric', 'min:0'],
            'presentaciones.*.activo' => ['boolean'],
            'presentaciones.*.tipo_venta' => ['nullable', 'in:unidad,peso,porcion'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if (! $this->filled('receta_id')) {
                return;
            }

            $recetaLibre = Receta::whereKey($this->input('receta_id'))
                ->where('activo', true)
                ->whereNull('producto_id')
                ->whereNull('presentacion_id')
                ->exists();

            if (! $recetaLibre) {
                $validator->errors()->add('receta_id', 'Selecciona una receta independiente activa.');
            }

            $producto = $this->route('producto');
            if ($producto && $producto->recetaDefault()->exists()) {
                $validator->errors()->add('receta_id', 'Este producto ya tiene una receta general.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre del producto es obligatorio.',
            'tipo_unidad.required' => 'Selecciona una unidad base.',
            'tipo_unidad.in' => 'Unidad no válida.',
            'presentaciones.required' => 'Agrega al menos una presentación.',
            'presentaciones.min' => 'Agrega al menos una presentación.',
            'presentaciones.*.nombre.required' => 'El nombre de la presentación es obligatorio.',
            'presentaciones.*.cantidad_unidades.required' => 'La cantidad es obligatoria.',
            'presentaciones.*.cantidad_unidades.min' => 'La cantidad debe ser mayor a 0.',
            'presentaciones.*.precio.required' => 'El precio es obligatorio.',
            'presentaciones.*.precio.min' => 'El precio no puede ser negativo.',
        ];
    }
}
