<?php

namespace App\Http\Requests;

use App\Models\Ingrediente;
use Illuminate\Foundation\Http\FormRequest;

class IngredienteControlInventarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tipo' => 'required|in:entrada,merma',
            'ingrediente_id' => 'required|integer',
            'cantidad' => 'required|numeric|min:0.01',
            'modo_costo' => 'nullable|string|in:precio_actual,nuevo_precio',
            'precio_unitario' => 'nullable|numeric|min:0.0001',
            'costo_total' => 'nullable|numeric|min:0',
            'categoria' => 'nullable|string|in:inventario_inicial,ajuste_conteo,devolucion,donacion,correccion_costo,caducidad,manipulacion,presentacion,diferencia_conteo,otro',
            'notas' => 'nullable|string|max:500',
            'fecha' => 'nullable|date',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->filled('ingrediente_id') && ! Ingrediente::whereKey($this->input('ingrediente_id'))->exists()) {
                $validator->errors()->add('ingrediente_id', 'El ingrediente seleccionado no existe.');
            }

            if ($this->input('tipo') !== 'entrada') {
                return;
            }

            if (! $this->filled('categoria')) {
                $validator->errors()->add('categoria', 'Seleccione el motivo del ajuste de entrada.');
            }

            if (! $this->filled('modo_costo')) {
                $validator->errors()->add('modo_costo', 'Seleccione como manejar el costo.');
            }

            if ($this->input('modo_costo') === 'nuevo_precio') {
                if (! $this->filled('precio_unitario') && ! $this->filled('costo_total')) {
                    $validator->errors()->add('precio_unitario', 'Ingrese el nuevo costo unitario.');
                } elseif ($this->filled('precio_unitario') && (float) $this->input('precio_unitario', 0) <= 0) {
                    $validator->errors()->add('precio_unitario', 'El nuevo costo unitario debe ser mayor a 0.');
                } elseif (! $this->filled('precio_unitario') && (float) $this->input('costo_total', 0) <= 0) {
                    $validator->errors()->add('costo_total', 'El costo total debe ser mayor a 0.');
                }
            }

            if ($this->input('modo_costo') === 'precio_actual' && ($this->filled('precio_unitario') || $this->filled('costo_total'))) {
                $validator->errors()->add('precio_unitario', 'El costo no cambia en este modo.');
            }
        });
    }
}
