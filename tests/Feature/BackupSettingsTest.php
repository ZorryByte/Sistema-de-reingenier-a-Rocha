<?php

namespace Tests\Feature;

use App\Models\BackupSnapshot;
use App\Models\ConfiguracionEmpresa;
use App\Models\User;
use App\Services\BackupArchiveService;
use App\Services\BackupService;
use App\Services\BackupVerificationService;
use App\Services\GoogleDriveCredentialService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BackupSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_save_google_drive_credentials_encrypted(): void
    {
        $response = $this->actingAs(User::factory()->admin()->create())->post(route('backups.configure.store'), [
            ...$this->validSettings(),
            'backup_disk' => 'google_drive',
            'cloud_disk' => 'google_drive',
            'google_client_id' => 'client.apps.googleusercontent.com',
            'google_client_secret' => 'secret-value',
        ]);

        $response->assertRedirect(route('backups.configure'));
        $this->assertSame('client.apps.googleusercontent.com', app(GoogleDriveCredentialService::class)->clientId());
        $this->assertSame('secret-value', app(GoogleDriveCredentialService::class)->clientSecret());
        $this->assertNotSame('secret-value', ConfiguracionEmpresa::get('backup.google_drive.client_secret'));
    }

    public function test_google_callback_stores_connection_and_creates_folder(): void
    {
        app(GoogleDriveCredentialService::class)->saveClient('client-id', 'client-secret');
        Http::fake([
            'oauth2.googleapis.com/token' => Http::response(['access_token' => 'access', 'refresh_token' => 'refresh']),
            'www.googleapis.com/drive/v3/files*' => Http::response(['id' => 'folder-id']),
            'www.googleapis.com/drive/v3/about*' => Http::response(['user' => ['emailAddress' => 'backups@example.com']]),
        ]);

        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->withSession(['google_drive_oauth_state' => 'state'])
            ->get(route('backups.google.callback', ['state' => 'state', 'code' => 'code']))
            ->assertRedirect(route('backups.configure'));

        $this->assertTrue(app(GoogleDriveCredentialService::class)->connected());
        $this->assertSame('backups@example.com', app(GoogleDriveCredentialService::class)->accountEmail());
    }

    public function test_admin_can_check_and_disconnect_google_drive(): void
    {
        app(GoogleDriveCredentialService::class)->saveClient('client-id', 'client-secret');
        ConfiguracionEmpresa::set('backup.google_drive.refresh_token', Crypt::encryptString('refresh'));
        ConfiguracionEmpresa::set('backup.google_drive.folder_id', 'folder-id');
        ConfiguracionEmpresa::set('backup.google_drive.account_email', 'backups@example.com');
        Http::fake([
            'oauth2.googleapis.com/token' => Http::response(['access_token' => 'access']),
            'www.googleapis.com/drive/v3/about*' => Http::response(['user' => ['emailAddress' => 'backups@example.com']]),
        ]);

        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->get(route('backups.google.check'))
            ->assertSessionHas('success');
        $this->actingAs($admin)->post(route('backups.google.disconnect'))
            ->assertRedirect(route('backups.configure'));

        $this->assertFalse(app(GoogleDriveCredentialService::class)->connected());
        $this->assertNull(app(GoogleDriveCredentialService::class)->accountEmail());
    }

    public function test_connected_account_is_manageable_from_settings(): void
    {
        app(GoogleDriveCredentialService::class)->saveClient('client-id', 'client-secret');
        ConfiguracionEmpresa::set('backup.google_drive.refresh_token', Crypt::encryptString('refresh'));
        ConfiguracionEmpresa::set('backup.google_drive.folder_id', 'folder-id');
        ConfiguracionEmpresa::set('backup.google_drive.account_email', 'backups@example.com');
        ConfiguracionEmpresa::set('backup.backup_disk', 'google_drive');

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('backups.configure'))
            ->assertOk()
            ->assertSee('backups@example.com')
            ->assertSee('Comprobar')
            ->assertSee('Cambiar cuenta')
            ->assertSee('Desconectar');
    }

    public function test_pending_backup_syncs_to_google_drive(): void
    {
        app(GoogleDriveCredentialService::class)->saveClient('client-id', 'client-secret');
        ConfiguracionEmpresa::set('backup.google_drive.refresh_token', Crypt::encryptString('refresh'));
        ConfiguracionEmpresa::set('backup.google_drive.folder_id', 'folder-id');
        ConfiguracionEmpresa::set('backup.backup_disk', 'google_drive');
        Http::fake([
            'oauth2.googleapis.com/token' => Http::response(['access_token' => 'access']),
            'www.googleapis.com/upload/drive/v3/files*' => Http::response(['id' => 'drive-file-id']),
        ]);

        $path = storage_path('app/backups/google-test.sql');
        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }
        file_put_contents($path, "-- MySQL dump\n-- Dump completed\n");

        $snapshot = BackupSnapshot::create([
            'uuid' => '11111111-1111-1111-1111-111111111111',
            'nivel' => 'manual',
            'ruta_archivo' => $path,
            'cloud_estado' => 'pendiente',
            'cloud_disk' => 'google_drive',
            'checksum' => hash_file('sha256', $path),
            'tamano_bytes' => filesize($path),
            'estado' => 'completado',
            'iniciado_en' => now(),
            'completado_en' => now(),
        ]);

        $result = app(BackupService::class)->sincronizarBackupsPendientes($snapshot->id);

        $this->assertSame(1, $result['sincronizados']);
        $this->assertDatabaseHas('backup_snapshots', ['id' => $snapshot->id, 'cloud_path' => 'drive-file-id']);
        @unlink($path);
    }

    public function test_dump_with_embedded_privilege_error_is_rejected(): void
    {
        $path = storage_path('app/backups/error-dump.sql');
        file_put_contents($path, "-- MySQL dump\nmysqldump: Error: Access denied when trying to dump tablespaces\n-- Dump completed\n");

        $this->assertFalse(app(BackupVerificationService::class)->isSqlDumpComplete($path));
        @unlink($path);
    }

    public function test_archive_contains_database_manifest_and_is_verifiable(): void
    {
        $sql = storage_path('app/backups/archive-test.sql');
        file_put_contents($sql, "-- MySQL dump\nCREATE TABLE `sample` (`id` int);\n-- Dump completed\n");

        $archive = app(BackupArchiveService::class)->create($sql, true);
        $result = app(BackupArchiveService::class)->verify($archive);

        $this->assertTrue($result['valid']);
        $this->assertFileExists($archive);
        $this->assertTrue(app(BackupVerificationService::class)->isSqlDumpComplete($archive));
        @unlink($archive);
    }

    private function validSettings(): array
    {
        return [
            'auto_backup_enabled' => '1',
            'backup_frequency' => 'daily',
            'backup_time' => '02:00',
            'backup_day' => 'monday',
            'rotation_type' => 'keep_days',
            'rotation_keep_count' => 7,
            'rotation_keep_days' => 30,
            'backup_scope' => 'full',
            'include_files' => '1',
            'backup_disk' => 'local',
            'cloud_disk' => 'google_drive',
            'cloud_path' => 'backups',
            'compress_backups' => '0',
            'notify_on_success' => '1',
            'notify_on_failure' => '1',
        ];
    }
}
