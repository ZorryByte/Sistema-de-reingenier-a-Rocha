<?php

namespace App\Http\Requests;

use App\Support\SaleAmount;
use Illuminate\Foundation\Http\FormRequest;

class PedidoEntregaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('deliver', $this->route('pedido'));
    }

    public function rules(): array
    {
        return [
            'pagos' => ['required', 'array', 'min:1'],
            'pagos.*.metodo' => ['required', 'in:efectivo,tarjeta,transferencia,otro'],
            'pagos.*.monto' => ['required', 'numeric', 'min:0.01'],
            'pagos.*.referencia' => ['nullable', 'string', 'max:100'],
            'notas' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            if ($v->errors()->isNotEmpty()) {
                return;
            }

            $pedido = $this->route('pedido');
            $pagos = $this->input('pagos', []);

            if (! $pedido) {
                return;
            }

            $pedido->load('detalles.presentacion');

            $total = $pedido->detalles->sum(fn ($detalle) => $detalle->subtotalCents());
            $totalPagado = collect($pagos)->sum(fn ($pago) => SaleAmount::cents($pago['monto']));

            if ($totalPagado < $total) {
                $v->errors()->add('pagos', 'La suma de pagos es menor al total del pedido.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'pagos.required' => 'Registra al menos un método de pago.',
            'pagos.min' => 'Registra al menos un método de pago.',
            'pagos.*.metodo.required' => 'Selecciona un método de pago.',
            'pagos.*.metodo.in' => 'Método de pago no válido.',
            'pagos.*.monto.required' => 'El monto del pago es obligatorio.',
            'pagos.*.monto.min' => 'El monto debe ser mayor a 0.',
        ];
    }
}
