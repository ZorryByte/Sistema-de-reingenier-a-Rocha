<?php

namespace App\Http\Requests;

use App\Models\Cliente;
use Illuminate\Foundation\Http\FormRequest;

class ClienteRequest extends FormRequest
{
    public function authorize(): bool
    {
        $permiso = $this->routeIs('clientes.alta-rapida') ? 'seleccionar' : 'administrar';

        return (bool) $this->user()?->can($permiso, Cliente::class);
    }

    protected function prepareForValidation(): void
    {
        $nombre = $this->input('nombre');
        $telefono = $this->input('telefono');

        if (is_string($nombre)) {
            $this->merge(['nombre' => trim(preg_replace('/\s+/u', ' ', $nombre) ?? '')]);
        }

        if (is_string($telefono) && strlen($telefono) <= 40) {
            $telefono = preg_replace('/[\s().-]/u', '', $telefono);
            if (preg_match('/^\d{8}$/D', $telefono ?? '')) {
                $telefono = '+503'.$telefono;
            }

            $this->merge(['telefono' => $telefono]);
        }
    }

    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:120'],
            'telefono' => ['required', 'string', 'regex:/^(?:\+503[0-9]{8}|\+(?!503)[1-9][0-9]{7,14})$/D'],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'Escribe el nombre del cliente.',
            'nombre.max' => 'El nombre admite hasta 120 caracteres.',
            'telefono.required' => 'Escribe el teléfono del cliente.',
            'telefono.regex' => 'Ingresa ocho dígitos para El Salvador o un número con prefijo internacional, por ejemplo +50255551234.',
        ];
    }
}
