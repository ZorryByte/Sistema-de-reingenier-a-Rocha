<?php

namespace App\Console\Commands;

use App\Services\BackupService;
use Illuminate\Console\Command;

class BackupCleanupCommand extends Command
{
    protected $signature = 'backup:cleanup';

    protected $description = 'Limpiar respaldos antiguos según política de retención';

    public function handle(BackupService $servicio): int
    {
        $this->info('Ejecutando limpieza de respaldos antiguos...');

        try {
            $eliminados = $servicio->limpiarRetencion();

            $this->newLine();

            if ($eliminados > 0) {
                $this->info("✓ {$eliminados} respaldo(s) eliminado(s) según política de retención.");
            } else {
                $this->info('No hay respaldos antiguos para eliminar.');
            }

            return self::SUCCESS;

        } catch (\Throwable $e) {
            $this->error('✗ Error durante limpieza: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}
