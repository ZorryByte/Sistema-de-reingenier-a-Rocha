<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use PharData;

class BackupArchiveService
{
    public function create(string $sqlPath, bool $includeFiles): string
    {
        $archivePath = preg_replace('/\.sql$/', '.tar', $sqlPath) ?: $sqlPath.'.tar';
        @unlink($archivePath);

        $archive = new PharData($archivePath);
        $archive->addFile($sqlPath, 'database.sql');

        $files = [];
        if ($includeFiles) {
            $root = storage_path('app/public');
            if (is_dir($root)) {
                $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS));
                foreach ($iterator as $file) {
                    if (! $file->isFile() || $file->isLink()) {
                        continue;
                    }

                    $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($root) + 1));
                    $archivePathName = 'files/public/'.$relative;
                    $archive->addFile($file->getPathname(), $archivePathName);
                    $files[$archivePathName] = ['bytes' => $file->getSize(), 'sha256' => hash_file('sha256', $file->getPathname())];
                }
            }
        }

        $manifest = [
            'format' => 1,
            'created_at' => now()->toIso8601String(),
            'database' => [
                'driver' => DB::getDriverName(),
                'name' => DB::getDatabaseName(),
                'sha256' => hash_file('sha256', $sqlPath),
                'tables' => $this->tableCounts(),
                'excluded_tables' => config('backup.tablas_excluir', []),
            ],
            'files' => $files,
        ];

        $archive->addFromString('manifest.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
        unset($archive);
        @unlink($sqlPath);

        return $archivePath;
    }

    public function extractDatabase(string $artifactPath): string
    {
        if (strtolower(pathinfo($artifactPath, PATHINFO_EXTENSION)) !== 'tar') {
            return $artifactPath;
        }

        $archive = new PharData($artifactPath);
        $temporary = $artifactPath.'.database.sql.tmp';
        file_put_contents($temporary, $archive['database.sql']->getContent());

        return $temporary;
    }

    public function cleanupExtracted(string $artifactPath, string $databasePath): void
    {
        if ($artifactPath !== $databasePath && str_ends_with($databasePath, '.tmp')) {
            @unlink($databasePath);
        }
    }

    public function verify(string $artifactPath): array
    {
        if (strtolower(pathinfo($artifactPath, PATHINFO_EXTENSION)) !== 'tar') {
            return ['valid' => true, 'errors' => [], 'manifest' => null];
        }

        try {
            $archive = new PharData($artifactPath);
            $manifest = json_decode($archive['manifest.json']->getContent(), true, flags: JSON_THROW_ON_ERROR);
            $errors = [];

            if (! hash_equals($manifest['database']['sha256'] ?? '', hash('sha256', $archive['database.sql']->getContent()))) {
                $errors[] = 'El SQL interno no coincide con el manifiesto';
            }

            foreach ($manifest['files'] ?? [] as $path => $metadata) {
                if (! isset($archive[$path]) || ! hash_equals($metadata['sha256'], hash('sha256', $archive[$path]->getContent()))) {
                    $errors[] = 'Archivo interno alterado: '.$path;
                }
            }

            return ['valid' => $errors === [], 'errors' => $errors, 'manifest' => $manifest];
        } catch (\Throwable $e) {
            return ['valid' => false, 'errors' => ['Paquete o manifiesto invalido: '.$e->getMessage()], 'manifest' => null];
        }
    }

    public function restoreFiles(string $artifactPath): void
    {
        if (strtolower(pathinfo($artifactPath, PATHINFO_EXTENSION)) !== 'tar') {
            return;
        }

        $archive = new PharData($artifactPath);
        $prefix = 'files/public/';
        foreach (new \RecursiveIteratorIterator($archive) as $file) {
            $path = str_replace('\\', '/', $file->getPathName());
            $position = strpos($path, $prefix);
            if ($position === false) {
                continue;
            }

            $relative = substr($path, $position + strlen($prefix));
            if ($relative === '' || str_contains($relative, '..')) {
                continue;
            }

            $destination = storage_path('app/public/'.str_replace('/', DIRECTORY_SEPARATOR, $relative));
            if (! is_dir(dirname($destination))) {
                mkdir(dirname($destination), 0755, true);
            }
            file_put_contents($destination, $file->getContent());
        }
    }

    private function tableCounts(): array
    {
        if (DB::getDriverName() !== 'mysql') {
            return [];
        }

        $excluded = config('backup.tablas_excluir', []);
        $tables = DB::select('SELECT table_name FROM information_schema.tables WHERE table_schema = ? ORDER BY table_name', [DB::getDatabaseName()]);
        $counts = [];

        foreach ($tables as $table) {
            $name = $table->TABLE_NAME ?? $table->table_name;
            if (in_array($name, $excluded, true)) {
                continue;
            }
            $counts[$name] = (int) DB::table($name)->count();
        }

        return $counts;
    }
}
