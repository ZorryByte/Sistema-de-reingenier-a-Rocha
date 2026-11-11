<?php

namespace App\Services;

use App\Exceptions\BackupException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class GoogleDriveBackupStorage
{
    public function __construct(private GoogleDriveCredentialService $credentials) {}

    public function configured(): bool
    {
        return $this->credentials->connected();
    }

    public function upload(string $localPath, ?string $existingFileId = null): array
    {
        $this->assertConfigured();

        $metadata = ['name' => basename($localPath)];
        if (! $existingFileId) {
            $metadata['parents'] = [$this->credentials->folderId()];
        }

        $boundary = 'hbakery_'.bin2hex(random_bytes(12));
        $body = "--{$boundary}\r\nContent-Type: application/json; charset=UTF-8\r\n\r\n"
            .json_encode($metadata, JSON_THROW_ON_ERROR)
            ."\r\n--{$boundary}\r\nContent-Type: application/octet-stream\r\n\r\n"
            .file_get_contents($localPath)
            ."\r\n--{$boundary}--";

        $url = $existingFileId
            ? 'https://www.googleapis.com/upload/drive/v3/files/'.$existingFileId
            : 'https://www.googleapis.com/upload/drive/v3/files';

        $request = $this->request()->withBody($body, "multipart/related; boundary={$boundary}");
        $response = $existingFileId
            ? $request->patch($url.'?uploadType=multipart&fields=id,name,size,md5Checksum')
            : $request->post($url.'?uploadType=multipart&fields=id,name,size,md5Checksum');

        if (! $response->successful()) {
            throw new BackupException(
                'Google Drive rechazo la subida: '.$response->body(),
                'No se pudo copiar el respaldo a Google Drive'
            );
        }

        return $response->json();
    }

    public function download(string $fileId, string $destination): string
    {
        $this->assertConfigured();
        $response = $this->request()->get("https://www.googleapis.com/drive/v3/files/{$fileId}?alt=media");

        if (! $response->successful()) {
            throw new BackupException('No se pudo descargar el archivo de Google Drive', 'No se pudo recuperar la copia remota');
        }

        file_put_contents($destination, $response->body());

        return $destination;
    }

    public function delete(string $fileId): void
    {
        $this->assertConfigured();
        $response = $this->request()->delete("https://www.googleapis.com/drive/v3/files/{$fileId}");

        if (! $response->successful() && $response->status() !== 404) {
            throw new BackupException('No se pudo eliminar el archivo de Google Drive', 'No se pudo eliminar la copia remota');
        }
    }

    public function status(): array
    {
        $this->assertConfigured();
        $response = $this->request()->get('https://www.googleapis.com/drive/v3/about', [
            'fields' => 'user(emailAddress),storageQuota(limit,usage)',
        ]);

        if (! $response->successful()) {
            throw new BackupException('Google Drive no respondio correctamente', 'No se pudo comprobar Google Drive');
        }

        return $response->json();
    }

    private function request(): PendingRequest
    {
        $token = Http::asForm()->retry(2, 500)->post('https://oauth2.googleapis.com/token', [
            'client_id' => $this->credentials->clientId(),
            'client_secret' => $this->credentials->clientSecret(),
            'refresh_token' => $this->credentials->refreshToken(),
            'grant_type' => 'refresh_token',
        ]);

        if (! $token->successful() || ! $token->json('access_token')) {
            throw new BackupException('No se pudo renovar el acceso a Google Drive', 'Google Drive requiere volver a autorizarse');
        }

        return Http::withToken($token->json('access_token'))->retry(2, 750);
    }

    private function assertConfigured(): void
    {
        if (! $this->configured()) {
            throw new BackupException('Faltan credenciales de Google Drive', 'Google Drive no esta configurado por completo');
        }
    }
}
