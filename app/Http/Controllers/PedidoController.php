<?php

namespace App\Http\Controllers;

use App\Actions\ActualizarPedido;
use App\Actions\CrearPedido;
use App\Actions\LiberarReservaStock;
use App\Actions\MarcarOrdenLista;
use App\Actions\ProcesarVenta;
use App\Actions\RegistrarEventoPedido;
use App\Enums\PedidoEstado;
use App\Http\Requests\ActualizarPedidoRequest;
use App\Http\Requests\PedidoEntregaRequest;
use App\Http\Requests\PedidoRequest;
use App\Models\Pedido;
use App\Models\PresentacionProducto;
use App\Services\DisponibilidadStock;
use App\Models\User;
use App\Services\PdfDataBuilder;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

class PedidoController extends Controller
{
    private const SYNC_INTERVAL_MS = 10000;

    public function index(Request $request): View|JsonResponse
    {
        Gate::authorize('viewAny', Pedido::class);

        [$pedidos, $contadores] = $this->buildListing($request);

        if ($request->expectsJson()) {
            return response()->json([
                'html' => view('pedidos.partials.board', compact('pedidos', 'contadores'))->render(),
                'synced_at' => now()->toIso8601String(),
                'interval_ms' => self::SYNC_INTERVAL_MS,
                'active_count' => $contadores[PedidoEstado::EnPreparacion->value]
                    + $contadores[PedidoEstado::OrdenLista->value],
            ]);
        }

        return view('pedidos.index', compact('pedidos', 'contadores'));
    }

    public function create(): View
    {
        Gate::authorize('create', Pedido::class);

        return view('pedidos.create', $this->catalogData());
    }

    public function store(PedidoRequest $request, CrearPedido $action): RedirectResponse
    {
        try {
            $pedido = $action->execute($request->validated(), (int) $request->user()->id);
            $nombre = $pedido->esOrden() ? 'Orden' : 'Pedido';

            return redirect()->route('pedidos.show', $pedido)
                ->with('success', "{$nombre} #{$pedido->id} creado correctamente.");
        } catch (ValidationException $e) {
            throw $e;
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        } catch (Throwable $e) {
            Log::error('No se pudo crear el pedido', ['exception' => $e]);

            return back()->withInput()->with('error', 'No pudimos guardar el registro. Intenta de nuevo.');
        }
    }

    public function show(Pedido $pedido): View
    {
        Gate::authorize('view', $pedido);
        $pedido->load('detalles.presentacion.producto', 'usuario', 'venta', 'historial.usuario', 'cliente');

        return view('pedidos.show', compact('pedido'));
    }

    public function edit(Pedido $pedido): View|RedirectResponse
    {
        Gate::authorize('update', $pedido);

        if (! in_array($pedido->estado, [PedidoEstado::EnPreparacion, PedidoEstado::OrdenLista], true)) {
            return redirect()->route('pedidos.show', $pedido)
                ->with('error', 'Este registro ya no puede editarse.');
        }

        $pedido->load('detalles.presentacion', 'cliente');

        return view('pedidos.edit', array_merge(
            ['pedido' => $pedido],
            $this->catalogData($pedido),
        ));
    }

    public function update(
        ActualizarPedidoRequest $request,
        Pedido $pedido,
        ActualizarPedido $action,
    ): RedirectResponse {
        try {
            $pedido = $action->execute($pedido, $request->validated(), (int) $request->user()->id);
            $nombre = $pedido->esOrden() ? 'Orden' : 'Pedido';

            return redirect()->route('pedidos.show', $pedido)
                ->with('success', "{$nombre} #{$pedido->id} actualizado correctamente.");
        } catch (ValidationException $e) {
            throw $e;
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        } catch (Throwable $e) {
            Log::error('No se pudo actualizar el pedido', [
                'pedido_id' => $pedido->id,
                'exception' => $e,
            ]);

            return back()->withInput()->with('error', 'No pudimos actualizar el registro. Intenta de nuevo.');
        }
    }

    public function entregar(
        PedidoEntregaRequest $request,
        Pedido $pedido,
        ProcesarVenta $action,
    ): RedirectResponse {
        Gate::authorize('deliver', $pedido);

        $items = $pedido->detalles->map(fn ($detalle) => [
            'presentacion_id' => $detalle->presentacion_id,
            'cantidad' => $detalle->cantidad,
        ])->all();

        try {
            /** @var User $user */
            $user = Auth::user();
            $venta = $action->execute([
                'pedido_id' => $pedido->id,
                'items' => $items,
                'pagos' => $request->pagos,
                'notas' => $request->notas,
            ], $user->id);

            return redirect()->route('ventas.show', $venta)
                ->with('success', "Registro #{$pedido->id} entregado correctamente.");
        } catch (RuntimeException $e) {
            return back()->withInput()->withErrors(['entrega' => $e->getMessage()]);
        } catch (Throwable $e) {
            Log::error('No se pudo entregar el pedido', [
                'pedido_id' => $pedido->id,
                'exception' => $e,
            ]);

            return back()->withInput()->withErrors(['entrega' => 'No pudimos completar la entrega. Verifica el estado e intenta de nuevo.']);
        }
    }

    public function cancelar(Pedido $pedido): RedirectResponse
    {
        Gate::authorize('cancel', $pedido);

        try {
            DB::transaction(function () use ($pedido) {
                $pedido = Pedido::lockForUpdate()->findOrFail($pedido->id);

                if (! in_array($pedido->estado, [PedidoEstado::EnPreparacion, PedidoEstado::OrdenLista], true)) {
                    throw new RuntimeException('Otro usuario cambió el estado. Actualiza la página.');
                }

                if ($pedido->venta()->exists()) {
                    throw new RuntimeException('El registro ya tiene una venta asociada.');
                }

                $estadoAnterior = $pedido->estado;
                (new LiberarReservaStock)->execute($pedido, (int) Auth::id());
                $pedido->update(['estado' => PedidoEstado::Cancelado]);
                (new RegistrarEventoPedido)->execute(
                    $pedido,
                    'cancelado',
                    (int) Auth::id(),
                    $estadoAnterior,
                    PedidoEstado::Cancelado,
                );
            }, 3);

            return redirect()->route('pedidos.index')
                ->with('success', 'Registro cancelado y reserva liberada.');
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        } catch (Throwable $e) {
            Log::error('No se pudo cancelar el pedido', [
                'pedido_id' => $pedido->id,
                'exception' => $e,
            ]);

            return back()->with('error', 'No pudimos cancelar el registro. Intenta de nuevo.');
        }
    }

    public function marcarLista(Pedido $pedido): RedirectResponse
    {
        Gate::authorize('markReady', $pedido);

        try {
            $pedido = (new MarcarOrdenLista)->execute($pedido, (int) Auth::id());

            return redirect()->route('pedidos.show', $pedido)
                ->with('success', "Orden #{$pedido->id} marcada como lista.");
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        } catch (Throwable $e) {
            Log::error('No se pudo marcar la orden como lista', [
                'pedido_id' => $pedido->id,
                'exception' => $e,
            ]);

            return back()->with('error', 'No pudimos actualizar la orden. Intenta de nuevo.');
        }
    }

    public function pdf(Pedido $pedido)
    {
        Gate::authorize('view', $pedido);
        $pedido->load('detalles.presentacion.producto', 'usuario', 'venta');
        $data = PdfDataBuilder::buildPedidoData($pedido);

        return Pdf::loadView('pdf.pedido', $data)
            ->setPaper([0, 0, 226.77, 600], 'portrait')
            ->download("pedido-{$pedido->id}.pdf");
    }

    private function buildListing(Request $request): array
    {
        $query = Pedido::with(['usuario', 'detalles.presentacion.producto', 'venta'])
            ->latest('fecha');

        $tipo = $request->string('tipo')->toString();
        if (in_array($tipo, ['pedido', 'orden'], true)) {
            $query->where('tipo', $tipo);
        }

        $estado = $request->string('estado')->toString();
        if (in_array($estado, array_column(PedidoEstado::cases(), 'value'), true)) {
            $query->where('estado', $estado);
        }

        if ($request->filled('fecha')) {
            $request->validate(['fecha' => ['date_format:Y-m-d']]);
            $query->whereDate('fecha_entrega_estimada', $request->string('fecha')->toString());
        }

        $resumen = Pedido::query()
            ->selectRaw('tipo, estado, COUNT(*) as total')
            ->groupBy('tipo', 'estado')
            ->get();

        $contadores = [
            'todos' => (int) $resumen->sum('total'),
            'pedido' => (int) $resumen->where('tipo', 'pedido')->sum('total'),
            'orden' => (int) $resumen->where('tipo', 'orden')->sum('total'),
        ];

        foreach (PedidoEstado::cases() as $caso) {
            $contadores[$caso->value] = (int) $resumen->where('estado', $caso->value)->sum('total');
        }

        return [$query->paginate(15)->withQueryString(), $contadores];
    }

    private function catalogData(?Pedido $pedido = null): array
    {
        $yaReservadas = $pedido?->detalles->pluck('presentacion_id')->all() ?? [];
        $all = PresentacionProducto::conProducto()
            ->where(fn ($query) => $query->activas()->orWhereIn('presentaciones_producto.id', $yaReservadas))
            ->get();
        $disponibilidad = DisponibilidadStock::paraCatalogo($all, $pedido?->id);

        return [
            'presentaciones' => $all->groupBy('producto.nombre'),
            'catalogo' => $all->map(fn ($presentacion) => [
                'id' => $presentacion->id,
                'producto' => $presentacion->producto->nombre,
                'presentacion' => $presentacion->nombre,
                'precio' => (float) $presentacion->precio,
                'tiene_receta' => $presentacion->recetaActiva()->exists()
                    || $presentacion->producto->recetaDefault()->exists(),
                'stock' => $disponibilidad[$presentacion->id],
            ])->values(),
        ];
    }
}
