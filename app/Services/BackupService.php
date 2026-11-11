<?php

namespace App\Services;

use App\Exceptions\BackupException;
use App\Models\BackupLog;
use App\Models\BackupSnapshot;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class BackupService
{
    public function __construct(
        private BackupStorageGuard $guard,
        private BackupVerificationService $verificador,
        private BackupSettingsService $settings,
        private GoogleDriveBackupStorage $googleDrive,
        private BackupArchiveService $archive
    ) {}

    public function crearBackup(string $nivel = 'manual'): BackupSnapshot
    {
        $this->guard->assertCanProceed();

        $uuid = Str::uuid()->toString();
        if (! $this->guard->acquireLock($uuid)) {
            throw new BackupException(
                'No se pudo adquirir lock de respaldo',
                'Ya hay un respaldo en progreso. Espere a que termine.'
            );
        }

        $snapshot = BackupSnapshot::create([
            'uuid' => $uuid,
            'nivel' => $nivel,
            'ruta_archivo' => '',
            'checksum' => '',
            'tamano_bytes' => 0,
            'estado' => 'en_progreso',
            'iniciado_en' => now(),
        ]);

        try {
            $rutaArchivo = $this->ejecutarMysqldump($uuid, $nivel);
            if (! $this->verificador->isSqlDumpComplete($rutaArchivo)) {
                throw new BackupException(
                    'Dump incompleto',
                    'El respaldo no se completó correctamente'
                );
            }

            $rutaArchivo = $this->archive->create($rutaArchivo, $this->settings->all()['include_files']);
            $checksum = hash_file('sha256', $rutaArchivo);

            $tamano = filesize($rutaArchivo);
            $cloudCopy = $this->subirCopiaCloudSiAplica($rutaArchivo);

            $snapshot->update([
                'ruta_archivo' => $rutaArchivo,
                'storage_disk' => 'local',
                'storage_path' => $this->relativeBackupPath($rutaArchivo),
                'cloud_disk' => $cloudCopy['disk'] ?? null,
                'cloud_path' => $cloudCopy['path'] ?? null,
                'cloud_estado' => $this->cloudEstado($cloudCopy),
                'cloud_uploaded_at' => isset($cloudCopy['path']) && ! isset($cloudCopy['error']) ? now() : null,
                'checksum' => $checksum,
                'tamano_bytes' => $tamano,
                'estado' => 'completado',
                'mensaje_error' => $cloudCopy['error'] ?? null,
                'completado_en' => now(),
            ]);

            $this->registrarLog($snapshot, 'crear', 'Backup creado exitosamente');

            if (isset($cloudCopy['error'])) {
                $this->registrarLog($snapshot, 'crear', 'Copia cloud omitida: '.$cloudCopy['error']);
            }

            Log::info('Backup creado', ['uuid' => $uuid, 'nivel' => $nivel, 'tamano' => $tamano]);

            return $snapshot;

        } catch (\Throwable $e) {
            $snapshot->update([
                'estado' => 'fallido',
                'mensaje_error' => $e->getMessage(),
                'completado_en' => now(),
            ]);

            $this->registrarLog($snapshot, 'crear', 'Error: '.$e->getMessage());

            throw new BackupException(
                $e->getMessage(),
                $e instanceof BackupException ? $e->getMensajeUsuario() : 'Error al crear respaldo'
            );

        } finally {
            $this->guard->releaseLock($uuid);
            $this->guard->cleanupPartialBackups();
        }
    }

    public function restaurarBackup(int $snapshotId): array
    {
        $snapshot = BackupSnapshot::findOrFail($snapshotId);
        $this->materializarArchivoLocal($snapshot);

        $validacion = $this->verificador->validateBeforeRestore($snapshotId);

        if (! $validacion['valid']) {
            return [
                'exito' => false,
                'errores' => $validacion['errors'],
            ];
        }

        $uuid = Str::uuid()->toString();
        if (! $this->guard->acquireLock($uuid)) {
            return [
                'exito' => false,
                'errores' => ['Ya hay un respaldo o restauracion en progreso'],
            ];
        }

        try {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');

            $sqlPath = $this->archive->extractDatabase($snapshot->ruta_archivo);
            try {
                $this->ejecutarRestore($sqlPath);
            } finally {
                $this->archive->cleanupExtracted($snapshot->ruta_archivo, $sqlPath);
            }

            $this->archive->restoreFiles($snapshot->ruta_archivo);

            DB::statement('SET FOREIGN_KEY_CHECKS=1');

            $this->registrarLog($snapshot, 'restaurar', 'Restauración exitosa');

            return [
                'exito' => true,
                'mensaje' => 'Base de datos restaurada exitosamente',
            ];

        } catch (\Throwable $e) {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');

            $this->registrarLog($snapshot, 'restaurar', 'Error: '.$e->getMessage());

            return [
                'exito' => false,
                'errores' => ['Error durante la restauración: '.$e->getMessage()],
            ];

        } finally {
            $this->guard->releaseLock($uuid);
        }
    }

    public function listarBackups()
    {
        return BackupSnapshot::orderBy('created_at', 'desc')->paginate(15);
    }

    public function obtenerBackup(int $id): BackupSnapshot
    {
        return BackupSnapshot::findOrFail($id);
    }

    public function eliminarBackup(int $id): bool
    {
        $snapshot = BackupSnapshot::findOrFail($id);

        if (file_exists($snapshot->ruta_archivo)) {
            @unlink($snapshot->ruta_archivo);
        }

        $this->eliminarCopiaCloud($snapshot);

        $this->registrarLog($snapshot, 'eliminar', 'Backup eliminado');

        return $snapshot->delete();
    }

    public function verificarBackup(int $id): array
    {
        $snapshot = BackupSnapshot::findOrFail($id);
        $rutaArchivo = $this->materializarArchivoLocal($snapshot);

        $checksumValido = $this->verificador->verifyChecksum($rutaArchivo, $snapshot->checksum);
        $completo = $this->verificador->isSqlDumpComplete($rutaArchivo);
        $headerValido = $this->verificador->hasValidHeader($rutaArchivo);

        $errores = [];

        if (! $checksumValido) {
            $errores[] = 'Checksum no coincide';
        }

        if (! $completo) {
            $errores[] = 'Dump incompleto';
        }

        if (! $headerValido) {
            $errores[] = 'Header SQL inválido';
        }

        $this->registrarLog($snapshot, 'verificar', empty($errores) ? 'Verificación OK' : implode(', ', $errores));

        return [
            'valido' => empty($errores),
            'errores' => $errores,
        ];
    }

    public function prepararArchivoLocal(BackupSnapshot $snapshot): string
    {
        return $this->materializarArchivoLocal($snapshot);
    }

    public function obtenerInfoStorage(): array
    {
        $ruta = config('backup.ruta_base', storage_path('app/backups'));
        $totalBytes = @disk_total_space($ruta) ?: 0;
        $libreBytes = @disk_free_space($ruta) ?: 0;
        $usadoBytes = $totalBytes - $libreBytes;

        $snapshots = BackupSnapshot::completados()->get();
        $tamanoBackups = $snapshots->sum('tamano_bytes');

        return [
            'total_bytes' => $totalBytes,
            'usado_bytes' => $usadoBytes,
            'libre_bytes' => $libreBytes,
            'tamano_backups' => $tamanoBackups,
            'porcentaje_usado' => $totalBytes > 0 ? round(($usadoBytes / $totalBytes) * 100, 1) : 0,
            'total_backups' => $snapshots->count(),
        ];
    }

    public function obtenerEstadoUltimoBackup(): string
    {
        $ultimo = BackupSnapshot::completados()
            ->orderBy('completado_en', 'desc')
            ->first();

        if (! $ultimo) {
            return 'never';
        }

        $horasDesde = now()->diffInHours($ultimo->completado_en);

        if ($horasDesde < 24) {
            return 'fresh';
        }

        if ($horasDesde < 48) {
            return 'aging';
        }

        return 'stale';
    }

    public function limpiarRetencion(): int
    {
        $settings = $this->settings->all();
        if ($settings['rotation_type'] === 'keep_all') {
            return 0;
        }

        $query = BackupSnapshot::completados()->orderByDesc('completado_en');
        $seleccionados = $settings['rotation_type'] === 'keep_count'
            ? $query->skip($settings['rotation_keep_count'])->take(PHP_INT_MAX)->get()
            : $query->where('completado_en', '<', now()->subDays($settings['rotation_keep_days']))->get();

        $total = 0;
        foreach ($seleccionados as $snapshot) {
            if (file_exists($snapshot->ruta_archivo)) {
                @unlink($snapshot->ruta_archivo);
            }
            $this->registrarLog($snapshot, 'eliminar', 'Limpieza automatica por politica '.$settings['rotation_type']);
            $this->eliminarCopiaCloud($snapshot);
            $snapshot->delete();
            $total++;
        }

        return $total;

    }

    public function sincronizarBackupsPendientes(?int $snapshotId = null): array
    {
        $query = BackupSnapshot::completados()
            ->whereIn('cloud_estado', ['pendiente', 'fallido']);

        if ($snapshotId) {
            $query->whereKey($snapshotId);
        }

        $sincronizados = 0;
        $fallidos = 0;
        $errores = [];

        foreach ($query->get() as $snapshot) {
            if (! file_exists($snapshot->ruta_archivo)) {
                $fallidos++;
                $errores[$snapshot->id] = 'No existe copia local para sincronizar';

                continue;
            }

            $resultado = $this->subirCopiaCloudSiAplica($snapshot->ruta_archivo, $snapshot);

            if (isset($resultado['path']) && ! isset($resultado['error'])) {
                $snapshot->update([
                    'cloud_estado' => 'sincronizado',
                    'cloud_disk' => $resultado['disk'],
                    'cloud_path' => $resultado['path'],
                    'cloud_uploaded_at' => now(),
                    'mensaje_error' => null,
                ]);
                $this->registrarLog($snapshot, 'crear', 'Copia cloud sincronizada');
                $sincronizados++;

                continue;
            }

            $snapshot->update([
                'cloud_estado' => 'fallido',
                'cloud_disk' => $resultado['disk'] ?? $snapshot->cloud_disk,
                'cloud_path' => $resultado['path'] ?? $snapshot->cloud_path,
                'mensaje_error' => $resultado['error'] ?? 'No se pudo sincronizar con la nube',
            ]);
            $fallidos++;
            $errores[$snapshot->id] = $snapshot->mensaje_error;
        }

        return [
            'sincronizados' => $sincronizados,
            'fallidos' => $fallidos,
            'errores' => $errores,
        ];
    }

    /**
     * Get the full path to mysqldump executable.
     * Tries PATH first, then common Laragon MySQL paths.
     */
    private function getMysqldumpPath(): string
    {
        // Try common Laragon MySQL paths first (prefer MySQL over MariaDB)
        $laragonPaths = [
            'C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysqldump.exe',
            'C:\laragon\bin\mysql\mysql-8.0.35-winx64\bin\mysqldump.exe',
            'C:\laragon\bin\mysql\mysql-5.7.24-winx64\bin\mysqldump.exe',
        ];

        foreach ($laragonPaths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        // Fallback to PATH (may pick up MariaDB version)
        return 'mysqldump';
    }

    /**
     * Get the full path to mysql executable.
     * Tries common Laragon MySQL paths first.
     */
    private function getMysqlPath(): string
    {
        // Try common Laragon MySQL paths first (prefer MySQL over MariaDB)
        $laragonPaths = [
            'C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysql.exe',
            'C:\laragon\bin\mysql\mysql-8.0.35-winx64\bin\mysql.exe',
            'C:\laragon\bin\mysql\mysql-5.7.24-winx64\bin\mysql.exe',
        ];

        foreach ($laragonPaths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        // Fallback to PATH (may pick up MariaDB version)
        return 'mysql';
    }

    private function ejecutarMysqldump(string $uuid, string $nivel): string
    {
        $config = config('database.connections.mysql');
        $directorio = config('backup.ruta_base', storage_path('app/backups'));
        $fecha = now()->format('Y-m-d_H-i-s');
        $rutaTmp = "{$directorio}/{$fecha}_{$uuid}.sql.tmp";
        $rutaError = "{$directorio}/{$fecha}_{$uuid}.err.tmp";
        $rutaFinal = "{$directorio}/{$fecha}_{$uuid}.sql";
        $rutaCredenciales = "{$directorio}/{$fecha}_{$uuid}.cnf.tmp";

        $excluir = config('backup.tablas_excluir', []);
        $ignoreTables = '';

        foreach ($excluir as $tabla) {
            $ignoreTables .= " --ignore-table={$config['database']}.{$tabla}";
        }

        $mysqldumpPath = $this->getMysqldumpPath();
        file_put_contents($rutaCredenciales, $this->mysqlOptionFile($config));
        @chmod($rutaCredenciales, 0600);

        $comando = sprintf(
            '"%s" --defaults-extra-file=%s --single-transaction --quick --no-tablespaces --max_allowed_packet=256M --routines --events --complete-insert %s %s > %s 2> %s',
            $mysqldumpPath,
            escapeshellarg($rutaCredenciales),
            $ignoreTables,
            escapeshellarg($config['database']),
            escapeshellarg($rutaTmp),
            escapeshellarg($rutaError)
        );

        $salida = [];
        $codigoRetorno = 0;

        exec($comando, $salida, $codigoRetorno);
        @unlink($rutaCredenciales);

        if ($codigoRetorno !== 0 || ! file_exists($rutaTmp) || filesize($rutaTmp) === 0) {
            // Capture error details from the temp file if it exists
            $errorDetail = '';
            if (file_exists($rutaError) && filesize($rutaError) > 0) {
                $errorDetail = trim(file_get_contents($rutaError));
            } elseif (file_exists($rutaTmp) && filesize($rutaTmp) > 0) {
                $errorDetail = trim(file_get_contents($rutaTmp));
            } elseif (! empty($salida)) {
                $errorDetail = implode("\n", $salida);
            }

            @unlink($rutaTmp);
            @unlink($rutaError);
            throw new BackupException(
                'mysqldump falló: '.$errorDetail,
                'Error al generar el respaldo de la base de datos'
            );
        }

        $warnings = file_exists($rutaError) ? trim(file_get_contents($rutaError)) : '';
        @unlink($rutaError);

        if ($warnings !== '' && preg_match('/\b(error|access denied|failed|denied)\b/i', $warnings)) {
            @unlink($rutaTmp);
            throw new BackupException('mysqldump reporto un error: '.$warnings, 'El respaldo no contiene una copia confiable de la base de datos');
        }

        rename($rutaTmp, $rutaFinal);

        return $rutaFinal;
    }

    private function subirCopiaCloudSiAplica(string $rutaArchivo, ?BackupSnapshot $snapshot = null): array
    {
        $settings = $this->settings->all();
        $disk = $settings['backup_disk'];

        if ($disk === 'local') {
            return [];
        }

        if ($disk !== 'google_drive') {
            return ['disk' => $disk, 'error' => 'Destino remoto no soportado'];
        }

        try {
            $uploaded = $this->googleDrive->upload($rutaArchivo, $snapshot?->cloud_path);
        } catch (\Throwable $e) {
            Log::warning('Backup cloud upload failed; local copy retained', [
                'disk' => $disk,
                'error' => $e->getMessage(),
            ]);

            return ['disk' => $disk, 'error' => $e->getMessage()];
        }

        return ['disk' => $disk, 'path' => $uploaded['id']];
    }

    private function cloudEstado(array $cloudCopy): string
    {
        if ($cloudCopy === []) {
            return 'no_configurado';
        }

        return isset($cloudCopy['error']) ? 'pendiente' : 'sincronizado';
    }

    private function materializarArchivoLocal(BackupSnapshot $snapshot): string
    {
        if (file_exists($snapshot->ruta_archivo)) {
            return $snapshot->ruta_archivo;
        }

        if (! $snapshot->cloud_disk || ! $snapshot->cloud_path) {
            return $snapshot->ruta_archivo;
        }

        $directorio = config('backup.ruta_base', storage_path('app/backups'));
        $rutaLocal = $directorio.'/'.basename($snapshot->cloud_path);

        if (! is_dir($directorio)) {
            mkdir($directorio, 0755, true);
        }

        try {
            if ($snapshot->cloud_disk !== 'google_drive') {
                return $snapshot->ruta_archivo;
            }

            $this->googleDrive->download($snapshot->cloud_path, $rutaLocal);
            $snapshot->update(['ruta_archivo' => $rutaLocal]);

            return $rutaLocal;
        } catch (\Throwable $e) {
            Log::warning('No se pudo leer copia cloud de backup', [
                'snapshot_id' => $snapshot->id,
                'disk' => $snapshot->cloud_disk,
                'path' => $snapshot->cloud_path,
                'error' => $e->getMessage(),
            ]);

            return $snapshot->ruta_archivo;
        }

        return $snapshot->ruta_archivo;
    }

    private function eliminarCopiaCloud(BackupSnapshot $snapshot): void
    {
        if (! $snapshot->cloud_disk || ! $snapshot->cloud_path) {
            return;
        }

        try {
            if ($snapshot->cloud_disk === 'google_drive') {
                $this->googleDrive->delete($snapshot->cloud_path);
            }
        } catch (\Throwable $e) {
            Log::warning('No se pudo eliminar copia cloud de backup', [
                'snapshot_id' => $snapshot->id,
                'disk' => $snapshot->cloud_disk,
                'path' => $snapshot->cloud_path,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function relativeBackupPath(string $rutaArchivo): string
    {
        $base = rtrim(config('backup.ruta_base', storage_path('app/backups')), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;

        return str_starts_with($rutaArchivo, $base)
            ? substr($rutaArchivo, strlen($base))
            : basename($rutaArchivo);
    }

    private function ejecutarRestore(string $rutaArchivo): void
    {
        $config = config('database.connections.mysql');
        $rutaRestaurar = $this->prepararDumpParaRestore($rutaArchivo);

        $mysqlPath = $this->getMysqlPath();
        $rutaCredenciales = $rutaArchivo.'.client.cnf.tmp';
        file_put_contents($rutaCredenciales, $this->mysqlOptionFile($config));
        @chmod($rutaCredenciales, 0600);

        $comando = sprintf(
            '"%s" --defaults-extra-file=%s %s < %s 2>&1',
            $mysqlPath,
            escapeshellarg($rutaCredenciales),
            escapeshellarg($config['database']),
            escapeshellarg($rutaRestaurar)
        );

        $salida = [];
        $codigoRetorno = 0;

        exec($comando, $salida, $codigoRetorno);
        @unlink($rutaCredenciales);

        if ($rutaRestaurar !== $rutaArchivo) {
            @unlink($rutaRestaurar);
        }

        if ($codigoRetorno !== 0) {
            throw new BackupException(
                'mysql restore falló: '.implode("\n", $salida),
                'Error al restaurar la base de datos'
            );
        }
    }

    private function prepararDumpParaRestore(string $rutaArchivo): string
    {
        $handle = fopen($rutaArchivo, 'rb');
        if ($handle === false) {
            return $rutaArchivo;
        }

        $firstLine = fgets($handle);
        fclose($handle);

        if ($firstLine === false || ! preg_match('/^mysqldump: \[Warning\]/i', $firstLine)) {
            return $rutaArchivo;
        }

        $rutaLimpia = $rutaArchivo.'.restore.sql';
        $source = fopen($rutaArchivo, 'rb');
        $target = fopen($rutaLimpia, 'wb');

        if ($source === false || $target === false) {
            if (is_resource($source)) {
                fclose($source);
            }
            if (is_resource($target)) {
                fclose($target);
            }

            return $rutaArchivo;
        }

        while (($line = fgets($source)) !== false) {
            if (preg_match('/^mysqldump: \[Warning\]/i', $line)) {
                continue;
            }

            fwrite($target, $line);
            break;
        }

        while (! feof($source)) {
            fwrite($target, fread($source, 8192));
        }

        fclose($source);
        fclose($target);

        return $rutaLimpia;
    }

    private function mysqlOptionFile(array $config): string
    {
        $escape = static fn (mixed $value): string => '"'.str_replace(
            ['\\', '"', "\r", "\n"],
            ['\\\\', '\\"', '', ''],
            (string) $value
        ).'"';

        return "[client]\n"
            .'host='.$escape($config['host'])."\n"
            .'port='.$escape($config['port'])."\n"
            .'user='.$escape($config['username'])."\n"
            .'password='.$escape($config['password'] ?? '')."\n";
    }

    private function registrarLog(BackupSnapshot $snapshot, string $accion, string $contexto): void
    {
        BackupLog::create([
            'snapshot_id' => $snapshot->id,
            'accion' => $accion,
            'user_id' => auth()->id(),
            'ip_address' => request()?->ip(),
            'contexto' => $contexto,
        ]);
    }
}
