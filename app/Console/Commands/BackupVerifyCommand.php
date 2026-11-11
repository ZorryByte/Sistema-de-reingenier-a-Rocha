<?php

namespace App\Console\Commands;

use App\Services\BackupService;
use Illuminate\Console\Command;

class BackupVerifyCommand extends Command
{
    protected $signature = 'backup:verify {id : ID del respaldo a verificar}';

    protected $description = 'Verificar integridad de un respaldo';

    public function handle(BackupService $servicio): int
    {
        $id = $this->argument('id');

        $this->info("Verificando respaldo #{$id}...");

        try {
            $resultado = $servicio->verificarBackup($id);

            if ($resultado['valido']) {
                $this->newLine();
                $this->info('✓ Respaldo verificado correctamente');
                $this->line('  - Checksum: válido');
                $this->line('  - Completitud: OK');
                $this->line('  - Header SQL: válido');

                return self::SUCCESS;
            }

            $this->newLine();
            $this->error('✗ Respaldo con problemas:');

            foreach ($resultado['errores'] as $error) {
                $this->line("  - {$error}");
            }

            return self::FAILURE;

        } catch (\Throwable $e) {
            $this->error('✗ Error: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}
