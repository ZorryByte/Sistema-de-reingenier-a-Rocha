<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AnularGastoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->esAdmin() === true;
    }

    public function rules(): array
    {
        return [
            'motivo' => ['required', 'string', 'min:5', 'max:300', 'not_regex:/<[^>]+>/', 'not_regex:/[\x00-\x1F\x7F]/u'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['motivo' => preg_replace('/\s+/u', ' ', trim((string) $this->input('motivo'))) ?: null]);
    }
}
