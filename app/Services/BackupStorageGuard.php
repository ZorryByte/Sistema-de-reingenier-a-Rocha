<?php

namespace App\Services;

use App\Exceptions\BackupException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BackupStorageGuard
{
    private const LOCK_KEY = 'backup_running';

    private const LOCK_TTL = 7200;

    public function assertCanProceed(): void
    {
        if ($this->isBackupInProgress()) {
            throw new BackupException(
                'Ya hay un respaldo en progreso',
                'Ya hay un respaldo en progreso. Espere a que termine.'
            );
        }

        if (! $this->canWriteToBackupDir()) {
            throw new BackupException(
                'No se puede escribir en el directorio de respaldos',
                'No se puede escribir en el directorio de respaldos. Verifique permisos.'
            );
        }

        $requeridoMb = $this->estimateRequiredSpaceMb();
        $disponibleMb = $this->getAvailableSpaceMb();

        if ($disponibleMb < $requeridoMb * 1.3) {
            throw new BackupException(
                "Espacio insuficiente: necesarios {$requeridoMb}MB, disponibles {$disponibleMb}MB",
                "Espacio en disco insuficiente. Se necesitan {$requeridoMb}MB, disponibles {$disponibleMb}MB"
            );
        }
    }

    public function estimateRequiredSpaceMb(): int
    {
        try {
            $resultado = DB::select('
                SELECT CEILING(SUM(data_length + index_length) / 1024 / 1024) as total_mb
                FROM information_schema.tables
                WHERE table_schema = DATABASE()
            ');

            return (int) ($resultado[0]->total_mb ?? 100);
        } catch (\Throwable $e) {
            Log::warning('No se pudo estimar espacio requerido', ['error' => $e->getMessage()]);

            return 100;
        }
    }

    public function getAvailableSpaceMb(): int
    {
        $ruta = config('backup.ruta_base', storage_path('app/backups'));
        $bytes = @disk_free_space($ruta);

        return $bytes !== false ? (int) ($bytes / 1024 / 1024) : 0;
    }

    public function canWriteToBackupDir(): bool
    {
        $ruta = config('backup.ruta_base', storage_path('app/backups'));

        if (! is_dir($ruta)) {
            if (! mkdir($ruta, 0755, true)) {
                return false;
            }
        }

        if (! is_writable($ruta)) {
            return false;
        }

        $testFile = $ruta.'/.write_test_'.uniqid();
        $canWrite = file_put_contents($testFile, 'test') !== false;

        if ($canWrite) {
            @unlink($testFile);
        }

        return $canWrite;
    }

    public function acquireLock(string $backupId): bool
    {
        return Cache::add(self::LOCK_KEY, $backupId, self::LOCK_TTL);
    }

    public function releaseLock(string $backupId): void
    {
        try {
            if (Cache::get(self::LOCK_KEY) === $backupId) {
                Cache::forget(self::LOCK_KEY);
            }
        } catch (\Throwable $e) {
            Log::warning('No se pudo liberar lock de backup', ['error' => $e->getMessage()]);
        }
    }

    public function isBackupInProgress(): bool
    {
        return Cache::has(self::LOCK_KEY);
    }

    public function cleanupPartialBackups(): int
    {
        $ruta = config('backup.ruta_base', storage_path('app/backups'));
        $eliminados = 0;

        if (! is_dir($ruta)) {
            return 0;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($ruta, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $archivo) {
            if ($archivo->isFile() && str_ends_with($archivo->getFilename(), '.tmp')) {
                if ($archivo->getMTime() < time() - 3600) {
                    @unlink($archivo->getPathname());
                    $eliminados++;
                }
            }
        }

        return $eliminados;
    }
}
