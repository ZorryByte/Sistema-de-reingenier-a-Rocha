<?php

namespace App\Http\Requests;

use App\Enums\GastoFrecuenciaUnidad;
use App\Enums\GastoMetodoPago;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class RecordatorioGastoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->esAdmin() === true;
    }

    public function rules(): array
    {
        return [
            'categoria_id' => ['required', 'integer', Rule::exists('categoria_gastos', 'id')->where('activo', true)],
            'proveedor_id' => ['nullable', 'integer', 'exists:proveedores,id'],
            'concepto' => ['required', 'string', 'max:160', 'not_regex:/<[^>]+>/', 'not_regex:/[\x00-\x1F\x7F]/u'],
            'monto_estimado' => ['nullable', 'numeric', 'min:0.01', 'max:999999.99', 'regex:/^\d{1,6}(\.\d{1,2})?$/'],
            'metodo_pago' => ['nullable', new Enum(GastoMetodoPago::class)],
            'unidad_frecuencia' => ['required', new Enum(GastoFrecuenciaUnidad::class)],
            'intervalo' => ['required', 'integer', 'min:1', 'max:365'],
            'proxima_fecha' => ['required', 'date', 'after_or_equal:today'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'concepto' => preg_replace('/\s+/u', ' ', trim((string) $this->input('concepto'))) ?: null,
            'proveedor_id' => $this->input('proveedor_id') ?: null,
            'metodo_pago' => $this->input('metodo_pago') ?: null,
            'monto_estimado' => $this->input('monto_estimado') ?: null,
        ]);
    }
}
