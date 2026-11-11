<?php

namespace App\Http\Requests;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ActualizarPedidoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('update', $this->route('pedido'));
    }

    public function rules(): array
    {
        $pedido = $this->route('pedido');

        return [
            'cliente_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'notas' => ['nullable', 'string', 'max:500'],
            'fecha_entrega_estimada' => [
                'nullable',
                'date',
                Rule::requiredIf(fn () => $pedido?->esPedido()),
            ],
            'items' => ['required', 'array', 'min:1'],
            'items.*.presentacion_id' => ['required', 'distinct', 'exists:presentaciones_producto,id'],
            'items.*.cantidad' => ['required', 'numeric', 'min:0.01'],
            'items.*.notas' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'fecha_entrega_estimada.required' => 'La fecha de entrega es obligatoria para los pedidos.',
            'items.required' => 'Agrega al menos un producto.',
            'items.min' => 'Agrega al menos un producto.',
            'items.*.presentacion_id.distinct' => 'Cada presentación solo puede aparecer una vez.',
            'items.*.presentacion_id.exists' => 'Uno de los productos seleccionados ya no está disponible.',
            'items.*.cantidad.min' => 'La cantidad debe ser mayor a cero.',
        ];
    }

    public function validated($key = null, $default = null)
    {
        $data = parent::validated($key, $default);

        if ($key !== null) {
            return $data;
        }

        if (! empty($data['fecha_entrega_estimada'])) {
            $data['fecha_entrega_estimada'] = Carbon::parse($data['fecha_entrega_estimada'])
                ->format('Y-m-d H:i:s');
        }

        return $data;
    }
}
