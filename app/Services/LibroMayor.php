<?php

namespace App\Services;

use App\Models\AsientoDetalle;
use App\Models\CuentaContable;
use App\Support\Money;
use Carbon\CarbonImmutable;

class LibroMayor
{
    public function consultar(
        CuentaContable $cuenta,
        CarbonImmutable $desde,
        CarbonImmutable $hasta,
        int $pagina,
        int $porPagina,
    ): array {
        $inicioPagina = ($pagina - 1) * $porPagina;
        $finPagina = $inicioPagina + $porPagina;
        $saldoInicial = $debe = $haber = $total = 0;
        $filas = [];

        // Una sola lectura mantiene los saldos y las filas de la página en el mismo corte.
        $detalles = AsientoDetalle::query()
            ->join('asientos_contables as asiento', 'asiento.id', '=', 'asiento_detalles.asiento_contable_id')
            ->where('asiento_detalles.cuenta_contable_id', $cuenta->id)
            ->where('asiento.estado', 'confirmado')
            ->where('asiento.fecha_contable', '<=', $hasta->endOfDay()->format('Y-m-d H:i:s.u'))
            ->orderBy('asiento.fecha_contable')
            ->orderBy('asiento.id')
            ->orderBy('asiento_detalles.id')
            ->select('asiento_detalles.*', 'asiento.fecha_contable', 'asiento.descripcion as descripcion_asiento')
            ->withCasts(['fecha_contable' => 'datetime'])
            ->cursor();

        foreach ($detalles as $detalle) {
            $cargo = Money::cents($detalle->debe);
            $abono = Money::cents($detalle->haber);

            if ($detalle->fecha_contable->lt($desde)) {
                $saldoInicial += $cargo - $abono;

                continue;
            }

            $debe += $cargo;
            $haber += $abono;
            if ($total >= $inicioPagina && $total < $finPagina) {
                $filas[] = [
                    'detalle' => $detalle,
                    'saldo' => $saldoInicial + $debe - $haber,
                ];
            }
            $total++;
        }

        return [
            'saldoInicial' => $saldoInicial,
            'debe' => $debe,
            'haber' => $haber,
            'saldoFinal' => $saldoInicial + $debe - $haber,
            'filas' => $filas,
            'total' => $total,
        ];
    }
}
