<?php

namespace App\Console\Commands;

use App\Models\BackupSnapshot;
use App\Services\BackupService;
use App\Services\BackupStorageGuard;
use Illuminate\Console\Command;

class BackupStatusCommand extends Command
{
    protected $signature = 'backup:status';

    protected $description = 'Mostrar estado del sistema de respaldos';

    public function handle(BackupService $servicio, BackupStorageGuard $guard): int
    {
        $estado = $servicio->obtenerEstadoUltimoBackup();
        $storage = $servicio->obtenerInfoStorage();
        $ultimo = BackupSnapshot::completados()->orderBy('completado_en', 'desc')->first();
        $enProgreso = $guard->isBackupInProgress();

        $estadoLabel = match ($estado) {
            'fresh' => '<fg=green>● Reciente (<24h)</>',
            'aging' => '<fg=yellow>● Envejecido (24-48h)</>',
            'stale' => '<fg=red>● Antiguo (>48h)</>',
            default => '<fg=gray>○ Sin respaldos</>',
        };

        $this->info('=== Estado del Sistema de Respaldos ===');
        $this->newLine();

        $this->table(
            ['Métrica', 'Valor'],
            [
                ['Último respaldo', $estadoLabel],
                ['Fecha último respaldo', $ultimo?->completado_en?->format('d/m/Y H:i:s') ?? 'N/A'],
                ['Total respaldos', $storage['total_backups']],
                ['Espacio usado por backups', $this->formatBytes($storage['tamano_backups'])],
                ['Espacio disponible disco', $this->formatBytes($storage['libre_bytes'])],
                ['Disco usado', $storage['porcentaje_usado'].'%'],
                ['En progreso', $enProgreso ? 'Sí' : 'No'],
            ]
        );

        $siguienteNivel = $this->obtenerSiguienteNivel();

        if ($siguienteNivel) {
            $this->newLine();
            $this->info("Próximo respaldo automático: {$siguienteNivel}");
        }

        return self::SUCCESS;
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes >= 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2).' '.$units[$i];
    }

    private function obtenerSiguienteNivel(): ?string
    {
        $hora = now()->format('H:i');

        $niveles = [
            'diario' => config('backup.niveles.diario.hora', '02:00'),
            'semanal' => config('backup.niveles.semanal.hora', '03:00'),
            'mensual' => config('backup.niveles.mensual.hora', '04:00'),
        ];

        foreach ($niveles as $nivel => $horaBackup) {
            if ($horaBackup > $hora) {
                return ucfirst($nivel)." a las {$horaBackup}";
            }
        }

        return 'Diario a las '.($niveles['diario'] ?? '02:00').' (mañana)';
    }
}
