<?php

namespace App\Services;

use App\Models\BackupSnapshot;
use Illuminate\Support\Facades\Log;

class BackupVerificationService
{
    public function __construct(private BackupArchiveService $archive) {}

    public function verifyChecksum(string $filePath, string $expectedHash): bool
    {
        if (! file_exists($filePath)) {
            return false;
        }

        $actualHash = hash_file('sha256', $filePath);

        return hash_equals($expectedHash, $actualHash);
    }

    public function isSqlDumpComplete(string $filePath): bool
    {
        if (strtolower(pathinfo($filePath, PATHINFO_EXTENSION)) === 'tar') {
            $sqlPath = $this->archive->extractDatabase($filePath);
            try {
                return $this->isSqlDumpComplete($sqlPath);
            } finally {
                $this->archive->cleanupExtracted($filePath, $sqlPath);
            }
        }

        if (! file_exists($filePath)) {
            return false;
        }

        try {
            $handle = fopen($filePath, 'rb');
            if ($handle === false) {
                return false;
            }

            fseek($handle, -2048, SEEK_END);
            $footer = fread($handle, 2048);
            fclose($handle);

            if ($this->containsDumpError($filePath)) {
                return false;
            }

            return str_contains($footer, '-- Dump completed')
                || str_contains($footer, 'UNLOCK TABLES')
                || str_contains($footer, '-- Finalización del respaldo');
        } catch (\Throwable $e) {
            Log::warning('Error verificando completitud de dump', ['error' => $e->getMessage()]);

            return false;
        }
    }

    public function containsDumpError(string $filePath): bool
    {
        if (strtolower(pathinfo($filePath, PATHINFO_EXTENSION)) === 'tar') {
            $sqlPath = $this->archive->extractDatabase($filePath);
            try {
                return $this->containsDumpError($sqlPath);
            } finally {
                $this->archive->cleanupExtracted($filePath, $sqlPath);
            }
        }

        $handle = fopen($filePath, 'rb');
        if ($handle === false) {
            return true;
        }

        while (($line = fgets($handle)) !== false) {
            if (preg_match('/^mysqldump:\s+(?:Error|Got error)|access denied.*(?:dump|tablespace)/i', trim($line))) {
                fclose($handle);

                return true;
            }
        }

        fclose($handle);

        return false;
    }

    public function auditContents(string $filePath): array
    {
        if (strtolower(pathinfo($filePath, PATHINFO_EXTENSION)) === 'tar') {
            $sqlPath = $this->archive->extractDatabase($filePath);
            try {
                return $this->auditContents($sqlPath);
            } finally {
                $this->archive->cleanupExtracted($filePath, $sqlPath);
            }
        }

        $tables = [];
        $rows = $this->estimateTableCounts($filePath);
        $handle = fopen($filePath, 'rb');

        if ($handle !== false) {
            while (($line = fgets($handle)) !== false) {
                if (preg_match('/^CREATE TABLE `([^`]+)`/i', $line, $matches)) {
                    $tables[] = $matches[1];
                }
            }
            fclose($handle);
        }

        sort($tables);

        return ['tables' => $tables, 'rows' => $rows, 'table_count' => count($tables)];
    }

    public function hasValidHeader(string $filePath): bool
    {
        if (strtolower(pathinfo($filePath, PATHINFO_EXTENSION)) === 'tar') {
            $sqlPath = $this->archive->extractDatabase($filePath);
            try {
                return $this->hasValidHeader($sqlPath);
            } finally {
                $this->archive->cleanupExtracted($filePath, $sqlPath);
            }
        }

        if (! file_exists($filePath)) {
            return false;
        }

        try {
            $handle = fopen($filePath, 'rb');
            if ($handle === false) {
                return false;
            }

            $header = $this->stripLeadingMysqldumpWarnings(fread($handle, 1024));
            fclose($handle);

            return str_starts_with(trim($header), '-- MySQL dump')
                || str_starts_with(trim($header), '/*!')
                || str_starts_with(trim($header), '-- Respaldo');
        } catch (\Throwable $e) {
            Log::warning('Error verificando header de dump', ['error' => $e->getMessage()]);

            return false;
        }
    }

    private function stripLeadingMysqldumpWarnings(string $content): string
    {
        return preg_replace('/^(?:mysqldump: \[Warning\].*\R)+/i', '', $content) ?? $content;
    }

    public function estimateTableCounts(string $filePath): array
    {
        if (strtolower(pathinfo($filePath, PATHINFO_EXTENSION)) === 'tar') {
            $sqlPath = $this->archive->extractDatabase($filePath);
            try {
                return $this->estimateTableCounts($sqlPath);
            } finally {
                $this->archive->cleanupExtracted($filePath, $sqlPath);
            }
        }

        if (! file_exists($filePath)) {
            return [];
        }

        $tables = [];

        try {
            $handle = fopen($filePath, 'r');
            if ($handle === false) {
                return [];
            }

            $currentTable = null;

            while (($line = fgets($handle)) !== false) {
                if (preg_match('/^INSERT INTO `?(\w+)`?/i', $line, $matches)) {
                    $table = $matches[1];

                    if (! isset($tables[$table])) {
                        $tables[$table] = 0;
                    }

                    $tables[$table] += substr_count($line, '),(') + 1;
                }
            }

            fclose($handle);
        } catch (\Throwable $e) {
            Log::warning('Error estimando conteo de tablas', ['error' => $e->getMessage()]);
        }

        return $tables;
    }

    public function validateBeforeRestore(int $snapshotId): array
    {
        $errors = [];

        $snapshot = BackupSnapshot::find($snapshotId);

        if (! $snapshot) {
            return ['valid' => false, 'errors' => ['El respaldo no existe']];
        }

        if (! file_exists($snapshot->ruta_archivo)) {
            $errors[] = 'El archivo de respaldo no existe en disco';
        } else {
            $archiveValidation = $this->archive->verify($snapshot->ruta_archivo);
            $errors = [...$errors, ...$archiveValidation['errors']];

            if (! $this->verifyChecksum($snapshot->ruta_archivo, $snapshot->checksum)) {
                $errors[] = 'El checksum no coincide - archivo corrupto';
            }

            if (! $this->hasValidHeader($snapshot->ruta_archivo)) {
                $errors[] = 'El archivo no tiene un formato SQL válido';
            }

            if (! $this->isSqlDumpComplete($snapshot->ruta_archivo)) {
                $errors[] = 'El respaldo parece incompleto';
            }
        }

        return ['valid' => empty($errors), 'errors' => $errors];
    }
}
