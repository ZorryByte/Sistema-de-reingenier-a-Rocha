<?php

namespace App\Actions;

use App\Enums\PedidoEstado;
use App\Models\Cliente;
use App\Models\Pedido;
use App\Services\DisponibilidadStock;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class CrearPedido
{
    public function execute(array $data, int $userId): Pedido
    {
        $this->assertUniquePresentations($data['items']);

        return DB::transaction(function () use ($data, $userId) {
            $tipo = $data['tipo'];
            $clienteId = Cliente::bloquearActivo($data['cliente_id'] ?? null);
            if ($tipo === 'pedido' && $clienteId === null) {
                throw ValidationException::withMessages(['cliente_id' => 'Selecciona un cliente para registrar el pedido.']);
            }

            $presentaciones = DisponibilidadStock::bloquearPresentaciones(collect($data['items'])->pluck('presentacion_id'));

            $pedido = Pedido::create([
                'user_id' => $userId,
                'cliente_id' => $clienteId,
                'estado' => PedidoEstado::EnPreparacion,
                'tipo' => $tipo,
                'fecha_entrega_estimada' => $tipo === 'pedido'
                    ? ($data['fecha_entrega_estimada'] ?? null)
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
            (new ReservarStock)->execute($pedido, $presentaciones);
            (new RegistrarEventoPedido)->execute(
                $pedido,
                'creado',
                $userId,
                null,
                PedidoEstado::EnPreparacion,
                ['tipo' => $tipo],
            );

            return $pedido;
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
