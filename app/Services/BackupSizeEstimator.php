<?php

namespace App\Services;

use App\Models\BackupSnapshot;
use Illuminate\Support\Facades\DB;

class BackupSizeEstimator
{
    public function estimate(bool $includeFiles = true): array
    {
        $databaseBytes = $this->databaseBytes();
        $filesBytes = $includeFiles ? $this->directoryBytes(storage_path('app/public')) : 0;
        $last = BackupSnapshot::completados()->latest('completado_en')->first();

        $ratio = $last && $databaseBytes > 0
            ? min(1.25, max(0.05, $last->tamano_bytes / $databaseBytes))
            : 0.35;

        $estimated = (int) ceil(($databaseBytes * $ratio) + $filesBytes);
        $margin = max(1024, (int) ceil($estimated * 0.20));

        return [
            'estimated_bytes' => $estimated,
            'minimum_bytes' => max(0, $estimated - $margin),
            'maximum_bytes' => $estimated + $margin,
            'database_bytes' => $databaseBytes,
            'files_bytes' => $filesBytes,
            'method' => $last ? 'Metadatos de MySQL e historial del ultimo respaldo' : 'Metadatos de MySQL con margen inicial conservador',
            'calculated_at' => now(),
        ];
    }

    private function databaseBytes(): int
    {
        if (DB::getDriverName() !== 'mysql') {
            return 0;
        }

        $row = DB::selectOne(
            'SELECT COALESCE(SUM(data_length + index_length), 0) AS bytes FROM information_schema.tables WHERE table_schema = ?',
            [DB::getDatabaseName()]
        );

        return (int) ($row->bytes ?? 0);
    }

    private function directoryBytes(string $directory): int
    {
        if (! is_dir($directory)) {
            return 0;
        }

        $bytes = 0;
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $file) {
            if ($file->isFile() && ! $file->isLink()) {
                $bytes += $file->getSize();
            }
        }

        return $bytes;
    }
}
