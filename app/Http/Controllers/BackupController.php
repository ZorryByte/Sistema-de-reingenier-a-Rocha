<?php

namespace App\Http\Controllers;

use App\Models\BackupSnapshot;
use App\Services\BackupService;
use App\Services\BackupSettingsService;
use App\Services\BackupSizeEstimator;
use App\Services\BackupVerificationService;
use App\Services\GoogleDriveBackupStorage;
use App\Services\GoogleDriveCredentialService;
use Illuminate\Http\Request;

class BackupController extends Controller
{
    public function __construct(
        private BackupService $servicio,
        private BackupSettingsService $settings,
        private BackupSizeEstimator $sizeEstimator,
        private GoogleDriveBackupStorage $googleDrive,
        private GoogleDriveCredentialService $driveCredentials
    ) {}

    public function index()
    {
        $storageInfo = $this->servicio->obtenerInfoStorage();
        $lastBackupStatus = $this->servicio->obtenerEstadoUltimoBackup();
        $backups = $this->servicio->listarBackups();
        $ultimo = BackupSnapshot::completados()->orderBy('completado_en', 'desc')->first();

        $storageFormatted = [
            'used' => $storageInfo['usado_bytes'],
            'total' => $storageInfo['total_bytes'],
            'free' => $storageInfo['libre_bytes'],
            'backup_size' => $storageInfo['tamano_backups'],
            'percentage' => $storageInfo['porcentaje_usado'],
            'count' => $storageInfo['total_backups'],
        ];

        return view('backups.index', [
            'storageInfo' => $storageFormatted,
            'lastBackupStatus' => $lastBackupStatus,
            'backups' => $backups,
            'ultimo' => $ultimo,
            'sizeEstimate' => $this->formatEstimate($this->sizeEstimator->estimate(true)),
            'driveConfigured' => $this->googleDrive->configured(),
            'driveClientConfigured' => $this->driveCredentials->hasClient(),
            'driveClientId' => $this->driveCredentials->clientId(),
            'driveManagedClient' => $this->driveCredentials->hasManagedClient(),
            'driveAccountEmail' => $this->driveCredentials->accountEmail(),
        ]);
    }

    public function store(Request $request)
    {
        try {
            $snapshot = $this->servicio->crearBackup('manual');

            return redirect()->route('backups.index')
                ->with('success', 'Respaldo creado exitosamente: '.$snapshot->tamano_humano);

        } catch (\Throwable $e) {
            return redirect()->route('backups.index')
                ->with('error', $e->getMessage());
        }
    }

    public function download(BackupSnapshot $backup)
    {
        if (file_exists($backup->ruta_archivo)) {
            return response()->download($backup->ruta_archivo, basename($backup->ruta_archivo));
        }

        if ($backup->cloud_disk && $backup->cloud_path) {
            try {
                $path = $this->servicio->prepararArchivoLocal($backup);
                if (file_exists($path)) {
                    return response()->download($path, basename($path));
                }
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return redirect()->route('backups.index')
            ->with('error', 'El archivo de respaldo no existe');
    }

    public function restore(BackupSnapshot $backup)
    {
        $preview = [];

        $rutaArchivo = $this->servicio->prepararArchivoLocal($backup);

        if (file_exists($rutaArchivo)) {
            $verificador = app(BackupVerificationService::class);
            $preview = $verificador->estimateTableCounts($rutaArchivo);
        }

        return view('backups.restore', [
            'backup' => $backup,
            'preview' => $preview,
        ]);
    }

    public function restoreExecute(Request $request, BackupSnapshot $backup)
    {
        $request->validate([
            'confirmacion' => 'required|in:RESTAURAR',
        ]);

        try {
            $resultado = $this->servicio->restaurarBackup($backup->id);

            if ($resultado['exito']) {
                return redirect()->route('backups.index')
                    ->with('success', $resultado['mensaje']);
            }

            return redirect()->route('backups.index')
                ->with('error', implode(', ', $resultado['errores']));

        } catch (\Throwable $e) {
            return redirect()->route('backups.index')
                ->with('error', 'Error al restaurar: '.$e->getMessage());
        }
    }

    public function verify(BackupSnapshot $backup)
    {
        try {
            $resultado = $this->servicio->verificarBackup($backup->id);

            if ($resultado['valido']) {
                return redirect()->route('backups.index')
                    ->with('success', 'Respaldo verificado correctamente');
            }

            return redirect()->route('backups.index')
                ->with('error', 'Problemas encontrados: '.implode(', ', $resultado['errores']));

        } catch (\Throwable $e) {
            return redirect()->route('backups.index')
                ->with('error', 'Error al verificar: '.$e->getMessage());
        }
    }

    public function destroy(BackupSnapshot $backup)
    {
        try {
            $this->servicio->eliminarBackup($backup->id);

            return redirect()->route('backups.index')
                ->with('success', 'Respaldo eliminado exitosamente');

        } catch (\Throwable $e) {
            return redirect()->route('backups.index')
                ->with('error', 'Error al eliminar: '.$e->getMessage());
        }
    }

    public function preview(BackupSnapshot $backup)
    {
        $verificador = app(BackupVerificationService::class);
        $tablas = [];

        $rutaArchivo = $this->servicio->prepararArchivoLocal($backup);

        if (file_exists($rutaArchivo)) {
            $counts = $verificador->estimateTableCounts($rutaArchivo);

            foreach ($counts as $nombre => $filas) {
                $tablas[] = ['name' => $nombre, 'rows' => $filas];
            }
        }

        return response()->json(['tables' => $tablas]);
    }

    public function configure()
    {
        $storageInfo = $this->servicio->obtenerInfoStorage();
        $settings = $this->settings->all();
        $ultimo = BackupSnapshot::completados()->orderBy('completado_en', 'desc')->first();
        $lastBackupStatus = $this->servicio->obtenerEstadoUltimoBackup();

        return view('backups.configure', [
            'niveles' => config('backup.niveles'),
            'retencion' => config('backup.retencion'),
            'ultimo' => $ultimo,
            'lastBackupStatus' => $lastBackupStatus,
            'nextBackupLabel' => $this->nextBackupLabel($settings),
            'destinationLabel' => $settings['backup_disk'] === 'google_drive'
                ? 'Local + Google Drive'
                : 'Solo local',
            'scopeLabel' => $settings['backup_scope'] === 'full'
                ? 'Base de datos completa'
                : 'Solo tablas criticas',
            'retentionLabel' => $this->retentionLabel($settings),
            'autoBackupEnabled' => $settings['auto_backup_enabled'],
            'backupFrequency' => $settings['backup_frequency'],
            'backupTime' => $settings['backup_time'],
            'backupDay' => $settings['backup_day'],
            'rotationType' => $settings['rotation_type'],
            'rotationKeepCount' => $settings['rotation_keep_count'],
            'rotationKeepDays' => $settings['rotation_keep_days'],
            'backupScope' => $settings['backup_scope'],
            'includeFiles' => $settings['include_files'],
            'backupDisk' => $settings['backup_disk'],
            'cloudDisk' => $settings['cloud_disk'],
            'cloudPath' => $settings['cloud_path'],
            'backupPath' => $settings['backup_path'],
            'availableSpace' => $this->formatBytes($storageInfo['libre_bytes']),
            'compressBackups' => $settings['compress_backups'],
            'notifyOnSuccess' => $settings['notify_on_success'],
            'notifyOnFailure' => $settings['notify_on_failure'],
            'sizeEstimate' => $this->formatEstimate($this->sizeEstimator->estimate($settings['include_files'])),
            'driveConfigured' => $this->googleDrive->configured(),
            'driveClientConfigured' => $this->driveCredentials->hasClient(),
            'driveClientId' => $this->driveCredentials->clientId(),
            'driveManagedClient' => $this->driveCredentials->hasManagedClient(),
            'driveAccountEmail' => $this->driveCredentials->accountEmail(),
        ]);
    }

    private function nextBackupLabel(array $settings): string
    {
        if (! $settings['auto_backup_enabled']) {
            return 'Respaldos automaticos desactivados';
        }

        return match ($settings['backup_frequency']) {
            'weekly' => 'Semanal, '.$this->dayLabel($settings['backup_day']).' a las '.$settings['backup_time'],
            'monthly' => 'Mensual, dia 1 a las '.$settings['backup_time'],
            default => 'Diario a las '.$settings['backup_time'],
        };
    }

    private function retentionLabel(array $settings): string
    {
        return match ($settings['rotation_type']) {
            'keep_all' => 'Mantener todos',
            'keep_count' => 'Mantener ultimos '.$settings['rotation_keep_count'],
            default => 'Eliminar mayores de '.$settings['rotation_keep_days'].' dias',
        };
    }

    private function dayLabel(string $day): string
    {
        return [
            'monday' => 'lunes',
            'tuesday' => 'martes',
            'wednesday' => 'miercoles',
            'thursday' => 'jueves',
            'friday' => 'viernes',
            'saturday' => 'sabado',
            'sunday' => 'domingo',
        ][$day] ?? 'lunes';
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes >= 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2).' '.$units[$i];
    }

    public function configureStore(Request $request)
    {
        $validated = $request->validate([
            'auto_backup_enabled' => ['nullable', 'boolean'],
            'backup_frequency' => ['required', 'in:daily,weekly,monthly'],
            'backup_time' => ['required', 'date_format:H:i'],
            'backup_day' => ['required', 'in:monday,tuesday,wednesday,thursday,friday,saturday,sunday'],
            'rotation_type' => ['required', 'in:keep_all,keep_count,keep_days'],
            'rotation_keep_count' => ['required', 'integer', 'min:1', 'max:100'],
            'rotation_keep_days' => ['required', 'integer', 'min:1', 'max:365'],
            'backup_scope' => ['required', 'in:full,critical'],
            'include_files' => ['nullable', 'boolean'],
            'backup_disk' => ['required', 'in:local,google_drive'],
            'cloud_disk' => ['required_if:backup_disk,google_drive', 'nullable', 'in:google_drive'],
            'cloud_path' => ['nullable', 'string', 'max:180'],
            'compress_backups' => ['nullable', 'boolean'],
            'notify_on_success' => ['nullable', 'boolean'],
            'notify_on_failure' => ['nullable', 'boolean'],
            'google_client_id' => ['nullable', 'string', 'max:255'],
            'google_client_secret' => ['nullable', 'string', 'max:500'],
        ]);

        $this->settings->update([
            ...$validated,
            'auto_backup_enabled' => $request->boolean('auto_backup_enabled'),
            'include_files' => $request->boolean('include_files'),
            'compress_backups' => $request->boolean('compress_backups'),
            'notify_on_success' => $request->boolean('notify_on_success'),
            'notify_on_failure' => $request->boolean('notify_on_failure'),
            'cloud_disk' => 'google_drive',
            'cloud_path' => trim($validated['cloud_path'] ?? 'backups', '/') ?: 'backups',
        ]);

        if (filled($validated['google_client_id'] ?? null)) {
            $this->driveCredentials->saveClient(
                $validated['google_client_id'],
                $validated['google_client_secret'] ?? null
            );
        }

        return redirect()->route('backups.configure')
            ->with('success', 'Configuración guardada');
    }

    public function googleConnect(Request $request)
    {
        abort_unless($this->driveCredentials->hasClient(), 422, 'Guarda primero las credenciales de Google Drive.');
        $state = bin2hex(random_bytes(24));
        $request->session()->put('google_drive_oauth_state', $state);

        return redirect()->away($this->driveCredentials->authorizationUrl(route('backups.google.callback'), $state));
    }

    public function googleCallback(Request $request)
    {
        $savedState = (string) $request->session()->pull('google_drive_oauth_state');
        abort_unless($savedState !== '' && hash_equals($savedState, $request->string('state')->toString()), 403);

        if (! $request->filled('code')) {
            return redirect()->route('backups.configure')->with('error', 'Google Drive no fue autorizado.');
        }

        try {
            $this->driveCredentials->completeAuthorization($request->string('code')->toString(), route('backups.google.callback'));

            return redirect()->route('backups.configure')->with('success', 'Google Drive conectado.');
        } catch (\Throwable $e) {
            report($e);

            return redirect()->route('backups.configure')->with('error', 'No se pudo conectar Google Drive.');
        }
    }

    public function googleCheck()
    {
        try {
            $this->googleDrive->status();

            return redirect()->route('backups.configure')->with('success', 'Google Drive funciona correctamente.');
        } catch (\Throwable $e) {
            report($e);

            return redirect()->route('backups.configure')->with('error', 'No se pudo acceder a Google Drive. Vuelve a conectar la cuenta.');
        }
    }

    public function googleDisconnect()
    {
        $this->driveCredentials->disconnect();

        return redirect()->route('backups.configure')->with('success', 'Google Drive desconectado.');
    }

    private function formatEstimate(array $estimate): array
    {
        return [
            ...$estimate,
            'estimated' => $this->formatBytes($estimate['estimated_bytes']),
            'range' => $this->formatBytes($estimate['minimum_bytes']).' a '.$this->formatBytes($estimate['maximum_bytes']),
            'database' => $this->formatBytes($estimate['database_bytes']),
            'files' => $this->formatBytes($estimate['files_bytes']),
        ];
    }
}
