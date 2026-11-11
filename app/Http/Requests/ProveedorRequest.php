<?php

namespace App\Http\Requests;

use App\Models\Proveedor;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProveedorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->esAdmin() === true;
    }

    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:100', 'not_regex:/[\x00-\x1F\x7F]/u'],
            'telefono' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+() .-]+$/'],
            'email' => ['nullable', 'email', 'max:100'],
            'notas' => ['nullable', 'string', 'max:500', 'not_regex:/<[^>]+>/'],
            'contexto' => ['nullable', Rule::in(['proveedores', 'ingrediente_create', 'ingrediente_edit', 'gasto_create'])],
            'ingrediente_id' => ['nullable', 'required_if:contexto,ingrediente_edit', 'integer', 'exists:ingredientes,id'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'nombre' => Proveedor::normalizarNombre((string) $this->input('nombre')),
            'telefono' => $this->normalizarOpcional('telefono'),
            'email' => $this->filled('email') ? mb_strtolower(trim((string) $this->input('email'))) : null,
            'notas' => $this->normalizarOpcional('notas'),
            'contexto' => $this->input('contexto', 'proveedores'),
        ]);
    }

    private function normalizarOpcional(string $campo): ?string
    {
        return $this->filled($campo) ? trim((string) $this->input($campo)) : null;
    }
}
