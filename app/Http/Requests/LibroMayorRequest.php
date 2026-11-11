<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class LibroMayorRequest extends FormRequest
{
    protected $redirectRoute = 'finanzas.libro-mayor.index';

    protected function prepareForValidation(): void
    {
        $hoy = now();

        $this->mergeIfMissing([
            'desde' => $hoy->copy()->startOfMonth()->toDateString(),
            'hasta' => $hoy->copy()->endOfMonth()->toDateString(),
        ]);
    }

    public function rules(): array
    {
        return [
            'cuenta_id' => ['nullable', 'bail', 'integer', 'exists:cuentas_contables,id'],
            'desde' => ['bail', 'required', 'date_format:Y-m-d'],
            'hasta' => ['bail', 'required', 'date_format:Y-m-d'],
            'page' => ['sometimes', 'integer', 'min:1', 'max:2147483647'],
        ];
    }

    public function messages(): array
    {
        return [
            'cuenta_id.integer' => 'Selecciona una cuenta contable válida.',
            'cuenta_id.exists' => 'La cuenta contable seleccionada no existe.',
            'desde.required' => 'Indica la fecha inicial.',
            'hasta.required' => 'Indica la fecha final.',
            'desde.date_format' => 'La fecha inicial debe tener el formato AAAA-MM-DD.',
            'hasta.date_format' => 'La fecha final debe tener el formato AAAA-MM-DD.',
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->hasAny(['desde', 'hasta'])) {
                return;
            }

            $datos = $validator->getData();
            if ($datos['desde'] > $datos['hasta']) {
                $validator->errors()->add('hasta', 'La fecha final debe ser igual o posterior a la fecha inicial.');
            }
        }];
    }
}
