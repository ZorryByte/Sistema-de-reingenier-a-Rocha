<?php

namespace App\Http\Requests;

use App\Enums\GastoMetodoPago;
use App\Models\RecordatorioGasto;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class GastoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->esAdmin() === true;
    }

    public function rules(): array
    {
        return [
            'token_registro' => ['required', 'uuid'],
            'categoria_id' => [
                'required',
                'integer',
                Rule::exists('categoria_gastos', 'id')->where('activo', true),
            ],
            'proveedor_id' => ['nullable', 'integer', 'exists:proveedores,id'],
            'recordatorio_gasto_id' => [
                'nullable',
                'integer',
                Rule::exists('recordatorios_gasto', 'id')->where('activo', true),
            ],
            'periodo_recordatorio' => ['nullable', 'required_with:recordatorio_gasto_id', 'date'],
            'concepto' => ['required', 'string', 'max:160', 'not_regex:/<[^>]+>/', 'not_regex:/[\x00-\x1F\x7F]/u'],
            'numero_documento' => ['nullable', 'string', 'max:100', 'not_regex:/<[^>]+>/', 'not_regex:/[\x00-\x1F\x7F]/u'],
            'fecha' => ['required', 'date', 'before_or_equal:today'],
            'monto' => ['required', 'numeric', 'min:0.01', 'max:999999.99', 'regex:/^\d{1,6}(\.\d{1,2})?$/'],
            'metodo_pago' => ['required', new Enum(GastoMetodoPago::class)],
            'nota' => ['nullable', 'string', 'max:500', 'not_regex:/<[^>]+>/', 'not_regex:/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u'],
            'comprobante' => [
                'nullable',
                'file',
                'max:5120',
                'mimes:pdf,jpg,jpeg,png',
                'mimetypes:application/pdf,image/jpeg,image/png',
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        $recordatorioId = $this->input('recordatorio_gasto_id') ?: null;
        $recordatorio = $recordatorioId
            ? RecordatorioGasto::activos()->find($recordatorioId)
            : null;

        $this->merge([
            'concepto' => preg_replace('/\s+/u', ' ', trim((string) $this->input('concepto'))) ?: null,
            'numero_documento' => trim((string) $this->input('numero_documento')) ?: null,
            'nota' => trim((string) $this->input('nota')) ?: null,
            'categoria_id' => $recordatorio?->categoria_id ?? $this->input('categoria_id'),
            'proveedor_id' => $recordatorio ? $recordatorio->proveedor_id : ($this->input('proveedor_id') ?: null),
            'recordatorio_gasto_id' => $recordatorioId,
        ]);
    }
}
