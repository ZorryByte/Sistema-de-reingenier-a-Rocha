<?php

namespace App\Http\Requests;

use App\Models\PresentacionProducto;
use App\Support\SaleAmount;
use Illuminate\Foundation\Http\FormRequest;

class VentaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'pedido_id' => ['nullable', 'exists:pedidos,id'],
            'cliente_id' => ['exclude_unless:pedido_id,null', 'nullable', 'integer', 'min:1'],
            'notas' => ['nullable', 'string', 'max:500'],
            'items.*.presentacion_id' => ['required', 'exists:presentaciones_producto,id'],
            'items.*.cantidad' => ['required', 'numeric', 'min:0.0001', 'max:999999.9999', 'regex:/^\d{1,6}(\.\d{1,4})?$/'],
            'items.*.descuento' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'items.*.notas' => ['nullable', 'string', 'max:255'],
            'pagos.*.metodo' => ['required', 'in:efectivo,tarjeta,transferencia,otro'],
            'pagos.*.monto' => ['required', 'numeric', 'min:0.01'],
            'pagos.*.referencia' => ['nullable', 'string', 'max:100'],
        ];

        if ($this->isMethod('post') || $this->isMethod('patch')) {
            $rules['items'] = ['required', 'array', 'min:1'];
            $rules['pagos'] = ['required', 'array', 'min:1'];
        }

        return $rules;
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            if ($v->errors()->isNotEmpty()) {
                return;
            }

            $items = $this->input('items', []);
            $pagos = $this->input('pagos', []);

            // Calcular total basado en precios de base de datos
            $presentacionIds = collect($items)->pluck('presentacion_id')->filter()->unique();
            $precios = PresentacionProducto::whereIn('id', $presentacionIds)->pluck('precio', 'id');

            $total = collect($items)->sum(fn ($item) => SaleAmount::lineCents(
                $precios[$item['presentacion_id']] ?? 0,
                $item['cantidad'],
                $item['descuento'] ?? 0,
            ));
            $totalPagado = collect($pagos)->sum(fn ($pago) => SaleAmount::cents($pago['monto']));

            if ($totalPagado < $total) {
                $v->errors()->add('pagos', 'La suma de pagos es menor al total de la venta.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'items.required' => 'La venta debe tener al menos un producto.',
            'items.min' => 'La venta debe tener al menos un producto.',
            'items.*.presentacion_id.required' => 'Selecciona una presentacion.',
            'items.*.presentacion_id.exists' => 'La presentacion no existe.',
            'items.*.cantidad.min' => 'La cantidad debe ser mayor a 0.',
            'items.*.cantidad.regex' => 'La cantidad puede tener máximo 4 decimales.',
            'items.*.descuento.min' => 'El descuento no puede ser negativo.',
            'pagos.required' => 'Registra al menos un metodo de pago.',
            'pagos.*.metodo.required' => 'Selecciona un metodo de pago.',
            'pagos.*.metodo.in' => 'Metodo de pago no valido.',
            'pagos.*.monto.min' => 'El monto del pago debe ser mayor a 0.',
        ];
    }
}
