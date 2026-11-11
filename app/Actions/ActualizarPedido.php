<?php

namespace App\Actions;

use App\Enums\PedidoEstado;
use App\Models\Cliente;
use App\Models\Pedido;
use App\Services\DisponibilidadStock;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class ActualizarPedido
{
    public function execute(Pedido $pedido, array $data, int $userId): Pedido
    {
        $this->assertUniquePresentations($data['items']);

        return DB::transaction(function () use ($pedido, $data, $userId) {
            $pedido = Pedido::lockForUpdate()->findOrFail($pedido->id);

            if (! in_array($pedido->estado, [
                PedidoEstado::EnPreparacion,
                PedidoEstado::OrdenLista,
            ], true)) {
                throw new RuntimeException('El estado cambió y el registro ya no puede editarse.');
            }

            if ($pedido->venta()->exists()) {
                throw new RuntimeException('El registro ya tiene una venta asociada.');
            }

            $clienteAnterior = $pedido->cliente_id;
            $clienteId = array_key_exists('cliente_id', $data) ? $data['cliente_id'] : $clienteAnterior;
            if ($clienteId !== null) {
                $clienteId = (int) $clienteId;
            }

            $cambiosCliente = [];
            if ($clienteId !== $clienteAnterior) {
                if ($pedido->esPedido() && $clienteId === null) {
                    throw ValidationException::withMessages(['cliente_id' => 'Selecciona un cliente para el pedido.']);
                }

                $clienteId = Cliente::bloquearActivo($clienteId);
                $cambiosCliente = ['cliente_id_anterior' => $clienteAnterior, 'cliente_id_nuevo' => $clienteId];
            }

            $pedido->load('detalles');
            $cantidadesAnteriores = $pedido->detalles->pluck('cantidad', 'presentacion_id');
            $presentaciones = DisponibilidadStock::bloquearPresentaciones(
                collect($data['items'])->pluck('presentacion_id')->merge($cantidadesAnteriores->keys()),
            );
            $detalleAnterior = $pedido->detalles->map(fn ($detalle) => [
                'presentacion_id' => $detalle->presentacion_id,
                'cantidad' => (float) $detalle->cantidad,
            ])->values()->all();

            (new LiberarReservaStock)->execute($pedido, $userId);
            $pedido->detalles()->delete();

            $pedido->update([
                'cliente_id' => $clienteId,
                'fecha_entrega_estimada' => $pedido->esPedido()
                    ? $data['fecha_entrega_estimada']
                    : null,
                'notas' => $data['notas'] ?? null,
            ]);

            foreach ($data['items'] as $item) {
                $pedido->detalles()->create([
                    'presentacion_id' => $item['presentacion_id'],
                    'cantidad' => $item['cantidad'],
                    'notas' => $item['notas'] ?? null,
                ]);
            }

            $pedido->load('detalles.presentacion');
            (new ReservarStock)->execute($pedido, $presentaciones, $cantidadesAnteriores);

            (new RegistrarEventoPedido)->execute(
                $pedido,
                'actualizado',
                $userId,
                $pedido->estado,
                $pedido->estado,
                [
                    'detalles_anteriores' => $detalleAnterior,
                    'detalles_nuevos' => $pedido->detalles->map(fn ($detalle) => [
                        'presentacion_id' => $detalle->presentacion_id,
                        'cantidad' => (float) $detalle->cantidad,
                    ])->values()->all(),
                ] + $cambiosCliente,
            );

            return $pedido->refresh();
        }, 3);
    }

    private function assertUniquePresentations(array $items): void
    {
        $ids = collect($items)->pluck('presentacion_id');

        if ($ids->unique()->count() !== $ids->count()) {
            throw new RuntimeException('Cada presentación solo puede aparecer una vez.');
        }
    }
}
