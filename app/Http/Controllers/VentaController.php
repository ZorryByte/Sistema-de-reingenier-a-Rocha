<?php

namespace App\Http\Controllers;

use App\Actions\CancelarVenta;
use App\Actions\ProcesarVenta;
use App\Enums\VentaEstado;
use App\Http\Requests\VentaRequest;
use App\Models\PresentacionProducto;
use App\Exceptions\StockNoDisponible;
use App\Services\DisponibilidadStock;
use App\Models\User;
use App\Models\Venta;
use App\Services\PdfDataBuilder;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class VentaController extends Controller
{
    private function userId(): int
    {
        /** @var User $user */
        $user = Auth::user();

        return $user->id;
    }

    public function index(Request $request): View
    {
        $query = Venta::with('usuario', 'detalles')
            ->latest('created_at');

        // Search and filter for ventas
        if ($request->filled('search')) {
            $q = $request->string('search')->trim();
            $query->where(function ($sub) use ($q) {
                $sub->where('id', 'like', "%{$q}%")
                    ->orWhere('numero_factura', 'like', "%{$q}%")
                    ->orWhereHas('usuario', fn ($sq) => $sq->where('name', 'like', "%{$q}%"));
            });
        }

        if ($request->filled('fecha')) {
            $query->whereDate('created_at', $request->fecha);
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        $ventas = $query->paginate(15);

        return view('ventas.index', compact('ventas'));
    }

    public function create(): View
    {
        // Catalog query: active presentations with producto
        $all = PresentacionProducto::activas()
            ->conProducto()
            ->get();

        $disponibilidad = DisponibilidadStock::paraCatalogo($all);
        $precios = $all->pluck('precio', 'id');
        $presentaciones = $all->groupBy('producto.nombre');

        // Datos estructurados para catálogo grid en Alpine.js
        $catalogo = $all->map(fn ($p) => [
            'id' => $p->id,
            'producto' => $p->producto->nombre,
            'presentacion' => $p->nombre,
            'precio' => (float) $p->precio,
            'tipo_venta' => $p->tipo_venta ?? 'unidad',
            'stock' => $disponibilidad[$p->id],
        ])->values();

        return view('ventas.create', compact('presentaciones', 'precios', 'catalogo'));
    }

    public function store(VentaRequest $request, ProcesarVenta $action): RedirectResponse
    {
        try {
            $venta = $action->execute($request->validated(), $this->userId());

            if (! app()->environment('testing')) {
                try {
                    $pdfPath = $this->generarYGuardarPdf($venta);
                    $venta->update(['pdf_path' => $pdfPath]);
                } catch (\Exception $e) {
                    report($e);
                }
            }

            return redirect()->route('ventas.show', $venta)
                ->with('success', "Venta #{$venta->id} registrada correctamente.");

        } catch (ValidationException $e) {
            throw $e;
        } catch (StockNoDisponible $e) {
            return back()->withInput()->with('error', $e->getMessage());
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Error al procesar la venta. Intenta de nuevo.');
        }
    }

    private function generarYGuardarPdf(Venta $venta): string
    {
        $pdf = $this->buildPdfForVenta($venta);

        $storagePath = storage_path('app/facturas');
        if (! file_exists($storagePath)) {
            mkdir($storagePath, 0755, true);
        }

        $filename = "factura-{$venta->id}.pdf";
        $fullPath = $storagePath.'/'.$filename;

        file_put_contents($fullPath, $pdf->output());

        return 'facturas/'.$filename;
    }

    private function buildPdfForVenta(Venta $venta): \Barryvdh\DomPDF\PDF
    {
        $venta->load(
            'detalles.presentacion.producto',
            'pagos',
            'usuario',
            'pedido.cliente',
            'clienteDirecto',
        );

        $data = PdfDataBuilder::buildVentaData($venta);

        return Pdf::loadView('pdf.factura', $data)
            ->setPaper([0, 0, 226.77, 600], 'portrait');
    }

    public function show(Venta $venta): View
    {
        $venta->load(
            'detalles.presentacion.producto',
            'pagos',
            'usuario',
            'pedido'
        );

        return view('ventas.show', compact('venta'));
    }

    public function pdf(Venta $venta)
    {
        $pdf = $this->buildPdfForVenta($venta);

        return $pdf->download("factura-{$venta->id}.pdf");
    }

    public function cancelar(Request $request, Venta $venta): RedirectResponse
    {
        if (! Auth::user()->esAdmin()) {
            abort(403, 'No tienes permiso para realizar esta acción');
        }

        if ($venta->estado !== VentaEstado::Pagada) {
            return redirect()->route('ventas.index')
                ->with('error', 'Solo se pueden cancelar ventas pagadas.');
        }

        $venta->load('movimientos');

        try {
            (new CancelarVenta)->execute($venta, Auth::user(), true);
        } catch (\RuntimeException $e) {
            return redirect()->route('ventas.index')
                ->with('error', $e->getMessage());
        }

        return redirect()->route('ventas.show', $venta)
            ->with('success', "Venta #{$venta->id} cancelada y stock restaurado.");
    }
}
