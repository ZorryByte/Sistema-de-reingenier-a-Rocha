<?php

namespace App\Jobs;

use App\Services\BackupService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CreateBackupJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 3600;

    public int $tries = 1;

    /**
     * Crear trabajo de respaldo.
     */
    public function __construct(
        public string $nivel = 'manual'
    ) {}

    /**
     * Ejecutar el trabajo.
     */
    public function handle(BackupService $service): void
    {
        Log::info('Iniciando trabajo de respaldo', ['nivel' => $this->nivel]);

        try {
            $snapshot = $service->crearBackup($this->nivel);

            Log::info('Trabajo de respaldo completado', [
                'nivel' => $this->nivel,
                'uuid' => $snapshot->uuid,
            ]);

        } catch (\Exception $e) {
            Log::error('Trabajo de respaldo fallido', [
                'nivel' => $this->nivel,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
