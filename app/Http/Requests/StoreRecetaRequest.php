<?php

namespace App\Http\Requests;

use App\Models\PresentacionProducto;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRecetaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'producto_id' => $this->normalizarId($this->input('producto_id')),
            'presentacion_id' => $this->normalizarId($this->input('presentacion_id')),
            'categoria_receta_id' => $this->normalizarId($this->input('categoria_receta_id')),
        ]);
    }

    public function rules(): array
    {
        return [
            'producto_id' => ['nullable', 'exists:productos,id'],
            'categoria_receta_id' => [
                'required',
                Rule::exists('categorias_receta', 'id'),
            ],
            'nombre' => ['required', 'string', 'max:100'],
            'descripcion' => ['nullable', 'string', 'max:500'],
            'tiempo_preparacion' => ['required', 'integer', 'min:1'],
            'lote_unidades' => ['required', 'integer', 'min:1'],
            'presentacion_id' => ['nullable', Rule::exists('presentaciones_producto', 'id')],
            'ingredientes' => ['required', 'array', 'min:1'],
            'ingredientes.*.ingrediente_id' => ['required', 'exists:ingredientes,id'],
            'ingredientes.*.cantidad' => ['required', 'numeric', 'min:0.0001'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $productoId = $this->input('producto_id');
            $presentacionId = $this->input('presentacion_id');

            if (is_null($productoId) && ! is_null($presentacionId)) {
                $validator->errors()->add('presentacion_id', 'No se puede asociar una presentacion sin un producto.');
            }

            if (! is_null($productoId) && ! is_null($presentacionId)) {
                $pertenece = PresentacionProducto::where('id', $presentacionId)
                    ->where('producto_id', $productoId)
                    ->exists();

                if (! $pertenece) {
                    $validator->errors()->add('presentacion_id', 'La presentacion seleccionada no pertenece al producto.');
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre de la receta es obligatorio.',
            'categoria_receta_id.required' => 'Selecciona una categoria para la receta.',
            'categoria_receta_id.exists' => 'La categoria seleccionada no existe.',
            'tiempo_preparacion.required' => 'El tiempo de preparacion es obligatorio.',
            'tiempo_preparacion.integer' => 'El tiempo de preparacion debe ser un numero entero.',
            'tiempo_preparacion.min' => 'El tiempo de preparacion debe ser mayor a 0.',
            'lote_unidades.required' => 'Las unds. por lote son obligatorias.',
            'lote_unidades.integer' => 'Las unds. por lote deben ser un numero entero.',
            'lote_unidades.min' => 'Las unds. por lote deben ser mayor a 0.',
            'ingredientes.required' => 'La receta debe tener al menos un ingrediente.',
            'ingredientes.min' => 'La receta debe tener al menos un ingrediente.',
            'ingredientes.*.ingrediente_id.required' => 'Selecciona un ingrediente.',
            'ingredientes.*.ingrediente_id.exists' => 'El ingrediente seleccionado no existe.',
            'ingredientes.*.cantidad.min' => 'La cantidad debe ser mayor a 0.',
            'producto_id.exists' => 'El producto seleccionado no existe.',
            'presentacion_id.exists' => 'La presentacion seleccionada no existe.',
        ];
    }

    private function normalizarId(mixed $value): ?int
    {
        if ($value === null || $value === '' || $value === 'null') {
            return null;
        }

        return (int) $value;
    }
}
