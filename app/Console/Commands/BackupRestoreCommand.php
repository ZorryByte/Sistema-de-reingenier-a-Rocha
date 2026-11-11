<?php

namespace App\Console\Commands;

use App\Services\BackupService;
use Illuminate\Console\Command;

class BackupRestoreCommand extends Command
{
    protected $signature = 'backup:restore {id : ID del respaldo a restaurar} {--force : Sin confirmación}';

    protected $description = 'Restaurar base de datos desde un respaldo';

    public function handle(BackupService $servicio): int
    {
        $id = $this->argument('id');

        if (! $this->option('force')) {
            if (! $this->confirm("¿Está seguro de restaurar el respaldo #{$id}? Esto reemplazará TODA la base de datos actual.")) {
                $this->info('Operación cancelada.');

                return self::SUCCESS;
            }
        }

        $this->info("Restaurando respaldo #{$id}...");

        try {
            $resultado = $servicio->restaurarBackup($id);

            if ($resultado['exito']) {
                $this->newLine();
                $this->info('✓ '.$resultado['mensaje']);

                return self::SUCCESS;
            }

            $this->newLine();
            $this->error('✗ Error en la restauración:');

            foreach ($resultado['errores'] as $error) {
                $this->line("  - {$error}");
            }

            return self::FAILURE;

        } catch (\Throwable $e) {
            $this->error('✗ Error inesperado: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}
