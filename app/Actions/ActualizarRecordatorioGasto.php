<?php

namespace App\Actions;

use App\Enums\GastoFrecuenciaUnidad;
use App\Models\RecordatorioGasto;
use Carbon\CarbonInterface;

class ActualizarRecordatorioGasto
{
    public function execute(RecordatorioGasto $recordatorio, CarbonInterface $periodo): void
    {
        $proxima = match ($recordatorio->unidad_frecuencia) {
            GastoFrecuenciaUnidad::Dias => $periodo->copy()->addDays($recordatorio->intervalo),
            GastoFrecuenciaUnidad::Semanas => $periodo->copy()->addWeeks($recordatorio->intervalo),
            GastoFrecuenciaUnidad::Meses => $periodo->copy()->addMonthsNoOverflow($recordatorio->intervalo),
        };

        $recordatorio->update(['proxima_fecha' => $proxima->toDateString()]);
    }
}
