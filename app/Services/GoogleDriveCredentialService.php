<?php

namespace App\Services;

use App\Models\ConfiguracionEmpresa;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;

class GoogleDriveCredentialService
{
    public function clientId(): ?string
    {
        return config('services.google_drive.client_id') ?: $this->value('client_id');
    }

    public function clientSecret(): ?string
    {
        return config('services.google_drive.client_secret') ?: $this->secret('client_secret');
    }

    public function refreshToken(): ?string
    {
        return $this->secret('refresh_token') ?: config('services.google_drive.refresh_token');
    }

    public function folderId(): ?string
    {
        return $this->value('folder_id') ?: config('services.google_drive.folder_id');
    }

    public function hasClient(): bool
    {
        return filled($this->clientId()) && filled($this->clientSecret());
    }

    public function hasManagedClient(): bool
    {
        return filled(config('services.google_drive.client_id'))
            && filled(config('services.google_drive.client_secret'));
    }

    public function accountEmail(): ?string
    {
        return $this->value('account_email');
    }

    public function connected(): bool
    {
        return $this->hasClient() && filled($this->refreshToken()) && filled($this->folderId());
    }

    public function saveClient(string $clientId, ?string $clientSecret): void
    {
        if ($this->hasManagedClient()) {
            return;
        }

        ConfiguracionEmpresa::set('backup.google_drive.client_id', trim($clientId));
        if (filled($clientSecret)) {
            ConfiguracionEmpresa::set('backup.google_drive.client_secret', Crypt::encryptString($clientSecret));
        }
    }

    public function authorizationUrl(string $redirectUri, string $state): string
    {
        return 'https://accounts.google.com/o/oauth2/v2/auth?'.http_build_query([
            'client_id' => $this->clientId(),
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => 'https://www.googleapis.com/auth/drive.file',
            'access_type' => 'offline',
            'prompt' => 'consent',
            'state' => $state,
        ]);
    }

    public function completeAuthorization(string $code, string $redirectUri): void
    {
        $token = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'client_id' => $this->clientId(),
            'client_secret' => $this->clientSecret(),
            'code' => $code,
            'redirect_uri' => $redirectUri,
            'grant_type' => 'authorization_code',
        ])->throw()->json();

        $folder = Http::withToken($token['access_token'])->post('https://www.googleapis.com/drive/v3/files?fields=id', [
            'name' => 'H Bakery Backups',
            'mimeType' => 'application/vnd.google-apps.folder',
        ])->throw()->json();

        $about = Http::withToken($token['access_token'])
            ->get('https://www.googleapis.com/drive/v3/about', ['fields' => 'user(emailAddress)'])
            ->throw()
            ->json();

        $refreshToken = $token['refresh_token'] ?? $this->refreshToken();
        if (! $refreshToken) {
            throw new \RuntimeException('Google no entrego un token de renovacion.');
        }

        ConfiguracionEmpresa::set('backup.google_drive.refresh_token', Crypt::encryptString($refreshToken));
        ConfiguracionEmpresa::set('backup.google_drive.folder_id', $folder['id']);
        ConfiguracionEmpresa::set('backup.google_drive.account_email', $about['user']['emailAddress'] ?? 'Cuenta de Google');
    }

    public function disconnect(): void
    {
        ConfiguracionEmpresa::query()->whereIn('clave', [
            'backup.google_drive.refresh_token',
            'backup.google_drive.folder_id',
            'backup.google_drive.account_email',
        ])->delete();
    }

    private function value(string $key): ?string
    {
        $value = ConfiguracionEmpresa::get('backup.google_drive.'.$key);

        return filled($value) ? $value : null;
    }

    private function secret(string $key): ?string
    {
        $value = $this->value($key);
        if (! $value) {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (\Throwable) {
            return null;
        }
    }
}
