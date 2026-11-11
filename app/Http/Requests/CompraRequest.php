<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CompraRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->esAdmin() === true;
    }

    public function rules(): array
    {
        return [
            'proveedor_id' => ['nullable', 'exists:proveedores,id'],
            'fecha' => ['nullable', 'date'],
            'notas' => ['nullable', 'string', 'max:500'],
            'detalles' => ['required', 'array', 'min:1'],
            'detalles.*.ingrediente_id' => ['required', 'exists:ingredientes,id'],
            'detalles.*.cantidad' => ['required', 'numeric', 'min:0.01'],
            'detalles.*.precio_unitario' => ['required', 'numeric', 'min:0.01'],
        ];
    }

    public function messages(): array
    {
        return [
            'detalles.required' => 'Debe agregar al menos un ingrediente.',
            'detalles.min' => 'Debe agregar al menos un ingrediente.',
            'detalles.*.cantidad.min' => 'La cantidad debe ser mayor a 0.',
            'detalles.*.precio_unitario.min' => 'El precio debe ser mayor a 0.',
        ];
    }
}
