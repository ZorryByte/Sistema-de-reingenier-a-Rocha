<?php

namespace App\Actions\Dashboard\Widgets;

use App\Actions\Dashboard\DashboardWidget;
use App\Models\BackupSnapshot;
use App\Services\BackupService;
use Carbon\CarbonInterface;

class BackupStatusWidget implements DashboardWidget
{
    public function key(): string
    {
        return '';
    }

    public function ttl(): CarbonInterface
    {
        return now();
    }

    public function compute(): array
    {
        $backupService = app(BackupService::class);
        $ultimoBackup = BackupSnapshot::completados()
            ->orderBy('completado_en', 'desc')
            ->first();

        return [
            'estado' => $backupService->obtenerEstadoUltimoBackup(),
            'fecha' => $ultimoBackup?->completado_en,
            'tamano' => $ultimoBackup?->tamano_bytes
                ? $this->formatBytes($ultimoBackup->tamano_bytes)
                : null,
        ];
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2).' GB';
        }
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2).' MB';
        }
        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 2).' KB';
        }

        return $bytes.' B';
    }
}
