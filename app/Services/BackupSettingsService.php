<?php

namespace App\Services;

use App\Models\ConfiguracionEmpresa;
use Illuminate\Support\Facades\Schema;

class BackupSettingsService
{
    private const PREFIX = 'backup.';

    private const DEFAULTS = [
        'auto_backup_enabled' => true,
        'backup_frequency' => 'daily',
        'backup_time' => '02:00',
        'backup_day' => 'monday',
        'rotation_type' => 'keep_days',
        'rotation_keep_count' => 7,
        'rotation_keep_days' => 30,
        'backup_scope' => 'full',
        'include_files' => true,
        'backup_disk' => null,
        'cloud_disk' => 'google_drive',
        'cloud_path' => null,
        'compress_backups' => false,
        'notify_on_success' => true,
        'notify_on_failure' => true,
    ];

    public function all(): array
    {
        $settings = self::DEFAULTS;
        $settings['backup_disk'] = config('backup.storage.disk', 'local');
        $settings['cloud_disk'] = config('backup.storage.cloud_disk', 'google_drive');
        $settings['cloud_path'] = config('backup.storage.cloud_path', 'backups');

        if (! $this->configurationTableExists()) {
            return $this->normalize($settings);
        }

        foreach (array_keys(self::DEFAULTS) as $key) {
            $settings[$key] = ConfiguracionEmpresa::get(self::PREFIX.$key, (string) $settings[$key]);
        }

        return $this->normalize($settings);
    }

    public function update(array $values): void
    {
        foreach (array_keys(self::DEFAULTS) as $key) {
            if (array_key_exists($key, $values)) {
                ConfiguracionEmpresa::set(self::PREFIX.$key, (string) $values[$key]);
            }
        }
    }

    private function normalize(array $settings): array
    {
        $settings['auto_backup_enabled'] = $this->asBool($settings['auto_backup_enabled']);
        $settings['include_files'] = $this->asBool($settings['include_files']);
        $settings['compress_backups'] = $this->asBool($settings['compress_backups']);
        $settings['notify_on_success'] = $this->asBool($settings['notify_on_success']);
        $settings['notify_on_failure'] = $this->asBool($settings['notify_on_failure']);
        $settings['rotation_keep_count'] = (int) $settings['rotation_keep_count'];
        $settings['rotation_keep_days'] = (int) $settings['rotation_keep_days'];
        $settings['backup_day_number'] = $this->dayNumber($settings['backup_day']);
        $settings['backup_path'] = config('backup.ruta_base', storage_path('app/backups'));
        $settings['backup_disk'] = $settings['backup_disk'] ?: 'local';
        if (! in_array($settings['backup_disk'], ['local', 'google_drive'], true)) {
            $settings['backup_disk'] = 'local';
        }
        $settings['cloud_disk'] = $settings['cloud_disk'] ?: 'google_drive';
        $settings['cloud_path'] = $settings['cloud_path'] ?: 'backups';

        return $settings;
    }

    private function asBool(mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOL);
    }

    private function dayNumber(string $day): int
    {
        return [
            'sunday' => 0,
            'monday' => 1,
            'tuesday' => 2,
            'wednesday' => 3,
            'thursday' => 4,
            'friday' => 5,
            'saturday' => 6,
        ][$day] ?? 1;
    }

    private function configurationTableExists(): bool
    {
        try {
            return Schema::hasTable('configuracion_empresa');
        } catch (\Throwable) {
            return false;
        }
    }
}
