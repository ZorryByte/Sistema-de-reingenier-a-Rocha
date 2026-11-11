<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IngredienteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre' => 'required|string|max:100',
            'categoria' => 'required|in:harinas,lacteos,endulzantes,leudantes,grasas,saborizantes,decoracion,empaques,refrigerados,congelados,otros',
            'proveedor_id' => 'nullable|exists:proveedores,id',
            'unidad_medida' => 'required|in:g,oz,ml,unidad',
            'stock_actual' => 'required|numeric|min:0',
            'stock_minimo' => 'required|numeric|min:0',
            'costo_unitario' => 'nullable|numeric|min:0',
            'precio' => 'nullable|numeric|min:0',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('precio') && (float) $this->precio > 0 && $this->filled('stock_actual') && (float) $this->stock_actual > 0) {
            $this->merge([
                'costo_unitario' => (float) $this->precio / (float) $this->stock_actual,
            ]);
        }
    }
}
