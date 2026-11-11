<?php

namespace App\Actions;

use App\Enums\PedidoEstado;
use App\Enums\VentaEstado;
use App\Exceptions\StockNoDisponible;
use App\Models\Cliente;
use App\Models\Pedido;
use App\Models\SerieDocumento;
use App\Models\Venta;
use App\Models\VentaDetalle;
use App\Support\SaleAmount;
use App\Services\DisponibilidadStock;
use App\Models\VentaPago;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ProcesarVenta
{
    public function execute(array $data, int $userId): Venta
    {
        return DB::transaction(function () use ($data, $userId) {
            $pedido = null;
            if (! empty($data['pedido_id'])) {
                $pedido = Pedido::lockForUpdate()->findOrFail($data['pedido_id']);

                if (! in_array($pedido->estado, [PedidoEstado::EnPreparacion, PedidoEstado::OrdenLista], true)) {
                    throw new \RuntimeException('Otro usuario cambió el estado. Actualiza la página.');
                }

                if ($pedido->venta()->exists()) {
                    throw new \RuntimeException('El registro ya tiene una venta asociada.');
                }
            }

            $clienteId = $pedido === null ? Cliente::bloquearActivo($data['cliente_id'] ?? null) : null;
            $items = $data['items'];
            $reservados = $pedido?->detalles()->pluck('cantidad', 'presentacion_id') ?? collect();
            if ($reservados->isNotEmpty()) {
                $cantidades = collect($items)->groupBy('presentacion_id')
                    ->map(fn ($lineas) => round($lineas->sum('cantidad'), 4));

                if ($cantidades->count() !== $reservados->count()
                    || $cantidades->contains(fn ($cantidad, $id) => $cantidad !== (float) $reservados->get($id))) {
                    throw new \RuntimeException('El pedido cambió. Actualiza la página antes de cobrarlo.');
                }
            }

            $presentaciones = DisponibilidadStock::bloquearPresentaciones(collect($items)->pluck('presentacion_id'));
            $detalles = $this->construirDetalles($items, $presentaciones, $reservados);
            $totalCents = $detalles->sum(fn (VentaDetalle $detalle) => SaleAmount::cents($detalle->subtotal));
            $paidCents = collect($data['pagos'])->sum(fn ($pago) => SaleAmount::cents($pago['monto']));

            if ($paidCents < $totalCents) {
                throw new \RuntimeException('El pago es menor al total actualizado de la venta.');
            }


            [$serie, $numeroFactura] = SerieDocumento::generarSiguienteNumero('001');

            $venta = Venta::create([
                'user_id' => $userId,
                'pedido_id' => $data['pedido_id'] ?? null,
                'cliente_id' => $clienteId,
                'total' => $totalCents / 100,
                'estado' => VentaEstado::Pagada,
                'notas' => $data['notas'] ?? null,
                'numero_factura' => $numeroFactura,
                'serie_documento_id' => $serie->id,
            ]);

            foreach ($detalles as $detalle) {
                $detalle->venta_id = $venta->id;
                $detalle->save();
            }

            foreach ($data['pagos'] as $pago) {
                VentaPago::create([
                    'venta_id' => $venta->id,
                    'metodo' => $pago['metodo'],
                    'monto' => SaleAmount::cents($pago['monto']) / 100,
                    'referencia' => $pago['referencia'] ?? null,
                ]);
            }

            $venta->load('detalles.presentacion.producto');

            (new DescontarStock)->execute($venta);

            if ($pedido) {
                $estadoAnterior = $pedido->estado;
                $pedido->update(['estado' => PedidoEstado::Entregado]);
                (new RegistrarEventoPedido)->execute(
                    $pedido,
                    'entregado',
                    $userId,
                    $estadoAnterior,
                    PedidoEstado::Entregado,
                    ['venta_id' => $venta->id],
                );
            }

            (new RegistrarVentaContable)->execute($venta);

            return $venta;
        }, 3);
    }

    private function construirDetalles(array $items, Collection $presentaciones, Collection $reservados): Collection
    {
        return collect($items)->map(function ($item) use ($presentaciones, $reservados) {
            $presentacion = $presentaciones->get($item['presentacion_id']);

            if (! $presentacion) {
                abort(404, "Presentación no encontrada: {$item['presentacion_id']}");
            }

            if (! $presentacion->activo && ! $reservados->has($presentacion->id)) {
                throw new StockNoDisponible("La presentación \"{$presentacion->nombre}\" está inactiva.");
            }

            $detalle = new VentaDetalle([
                'presentacion_id' => $presentacion->id,
                'cantidad' => $item['cantidad'],
                'precio_unitario' => $presentacion->precio,
                'descuento' => $item['descuento'] ?? 0,
                'notas' => $item['notas'] ?? null,
            ]);

            $detalle->calcularSubtotal();

            return $detalle;
        });
    }
}
