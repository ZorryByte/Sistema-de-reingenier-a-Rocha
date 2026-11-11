<?php

namespace App\Http\Requests;

use App\Enums\GastoEstado;
use App\Enums\GastoMetodoPago;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class GastoFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->esAdmin() === true;
    }

    public function rules(): array
    {
        return [
            'desde' => ['nullable', 'date'],
            'hasta' => ['nullable', 'date', 'after_or_equal:desde'],
            'categoria_id' => ['nullable', 'integer', 'exists:categoria_gastos,id'],
            'proveedor_id' => ['nullable', 'integer', 'exists:proveedores,id'],
            'metodo_pago' => ['nullable', new Enum(GastoMetodoPago::class)],
            'estado' => ['nullable', new Enum(GastoEstado::class)],
        ];
    }
}
