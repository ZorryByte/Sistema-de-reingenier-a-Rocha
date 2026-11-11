<?php

namespace App\Console\Commands;

use App\Services\BackupService;
use Illuminate\Console\Command;

class BackupSyncCloudCommand extends Command
{
    protected $signature = 'backup:sync-cloud {id? : ID del respaldo a sincronizar}';

    protected $description = 'Sincronizar respaldos pendientes con Google Drive';

    public function handle(BackupService $servicio): int
    {
        $id = $this->argument('id') ? (int) $this->argument('id') : null;

        $this->info($id ? "Sincronizando respaldo #{$id}..." : 'Sincronizando respaldos pendientes...');

        $resultado = $servicio->sincronizarBackupsPendientes($id);

        $this->table(
            ['Metrica', 'Valor'],
            [
                ['Sincronizados', $resultado['sincronizados']],
                ['Fallidos', $resultado['fallidos']],
            ]
        );

        foreach ($resultado['errores'] as $snapshotId => $error) {
            $this->warn("Respaldo #{$snapshotId}: {$error}");
        }

        return $resultado['fallidos'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
