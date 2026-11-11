<?php

namespace App\Http\Requests;

use App\Models\Pedido;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PedidoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('create', Pedido::class);
    }

    public function rules(): array
    {
        return [
            'tipo' => ['required', 'in:pedido,orden'],
            'cliente_id' => [
                'nullable',
                Rule::requiredIf(fn () => $this->input('tipo') === 'pedido'),
                'integer',
                'min:1',
            ],
            'notas' => ['nullable', 'string', 'max:500'],
            'fecha_entrega_estimada' => [
                'nullable',
                'date',
                Rule::requiredIf(fn () => $this->input('tipo') === 'pedido'),
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
            'cliente_id.required' => 'Selecciona un cliente para registrar el pedido.',
            'tipo.required' => 'Selecciona si registrarás un pedido o una orden.',
            'tipo.in' => 'El tipo seleccionado no es válido.',
            'fecha_entrega_estimada.required' => 'La fecha de entrega es obligatoria para los pedidos.',
            'fecha_entrega_estimada.date' => 'La fecha de entrega debe ser válida.',
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
