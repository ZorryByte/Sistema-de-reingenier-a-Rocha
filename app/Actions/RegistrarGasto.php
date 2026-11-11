<?php

namespace App\Actions;

use App\Models\Gasto;
use App\Models\RecordatorioGasto;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class RegistrarGasto
{
    public function execute(array $data, int $userId): Gasto
    {
        if ($existente = $this->gastoPorToken($data['token_registro'] ?? null)) {
            return $existente;
        }

        $archivo = $data['comprobante'] ?? null;
        unset($data['comprobante']);
        $comprobante = $archivo instanceof UploadedFile ? $this->guardarComprobante($archivo) : [];

        try {
            return DB::transaction(function () use ($data, $userId, $comprobante) {
                $recordatorio = $this->recordatorio(
                    $data['recordatorio_gasto_id'] ?? null,
                    $data['periodo_recordatorio'] ?? null
                );
                $periodo = $recordatorio?->proxima_fecha;
                unset($data['periodo_recordatorio']);

                if ($recordatorio) {
                    $data['categoria_id'] = $recordatorio->categoria_id;
                    $data['proveedor_id'] = $recordatorio->proveedor_id;
                    $data['monto_estimado_recordatorio'] = $recordatorio->monto_estimado;
                }

                $gasto = new Gasto([
                    ...$data,
                    ...$comprobante,
                    'recordatorio_gasto_id' => $recordatorio?->id,
                    'periodo_recordatorio' => $periodo?->toDateString(),
                ]);
                $gasto->user_id = $userId;
                $gasto->save();

                (new RegistrarGastoContable)->execute($gasto);

                if ($recordatorio && $periodo) {
                    (new ActualizarRecordatorioGasto)->execute($recordatorio, $periodo);
                }

                return $gasto->load(['categoria.cuenta', 'proveedor', 'asientos']);
            });
        } catch (QueryException $exception) {
            $this->eliminarComprobante($comprobante);

            if ($this->esConflictoUnico($exception)) {
                if ($existente = $this->gastoPorToken($data['token_registro'] ?? null)) {
                    return $existente;
                }

                if (! empty($data['recordatorio_gasto_id']) && ! empty($data['periodo_recordatorio'])) {
                    throw ValidationException::withMessages([
                        'recordatorio_gasto_id' => 'Este recordatorio ya fue registrado para el periodo seleccionado.',
                    ]);
                }
            }

            throw $exception;
        } catch (Throwable $exception) {
            $this->eliminarComprobante($comprobante);

            throw $exception;
        }
    }

    private function gastoPorToken(?string $token): ?Gasto
    {
        if (! $token) {
            return null;
        }

        return Gasto::where('token_registro', $token)
            ->with(['categoria.cuenta', 'proveedor', 'asientos'])
            ->first();
    }

    private function esConflictoUnico(QueryException $exception): bool
    {
        $sqlState = (string) ($exception->errorInfo[0] ?? '');
        $driverCode = (int) ($exception->errorInfo[1] ?? 0);

        return $sqlState === '23000' && in_array($driverCode, [1062, 1555, 2067, 19], true);
    }

    private function recordatorio(?int $id, ?string $periodo): ?RecordatorioGasto
    {
        if (! $id) {
            return null;
        }

        $recordatorio = RecordatorioGasto::activos()->lockForUpdate()->findOrFail($id);

        if (! $periodo || $recordatorio->proxima_fecha->toDateString() !== $periodo) {
            throw ValidationException::withMessages([
                'recordatorio_gasto_id' => 'El recordatorio cambió. Abre el formulario nuevamente.',
            ]);
        }

        return $recordatorio;
    }

    private function guardarComprobante(UploadedFile $archivo): array
    {
        $disk = config('filesystems.receipts_disk');
        $extension = strtolower($archivo->extension() ?: $archivo->getClientOriginalExtension());
        $nombre = Str::uuid().'.'.$extension;
        $path = $archivo->storeAs('gastos/'.now()->format('Y/m'), $nombre, $disk);

        if (! $path) {
            throw new \RuntimeException('No se pudo guardar el comprobante.');
        }

        $nombreOriginal = preg_replace('/[^\pL\pN._ -]+/u', '_', basename($archivo->getClientOriginalName()));

        return [
            'comprobante_disk' => $disk,
            'comprobante_path' => $path,
            'comprobante_nombre' => Str::limit($nombreOriginal ?: 'comprobante.'.$extension, 180, ''),
            'comprobante_mime' => $archivo->getMimeType(),
            'comprobante_tamano' => $archivo->getSize(),
        ];
    }

    private function eliminarComprobante(array $comprobante): void
    {
        if (isset($comprobante['comprobante_path'])) {
            Storage::disk($comprobante['comprobante_disk'])->delete($comprobante['comprobante_path']);
        }
    }
}
