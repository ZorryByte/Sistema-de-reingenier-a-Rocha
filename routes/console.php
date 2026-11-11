<?php

use App\Services\BackupSettingsService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

$backupSettings = app(BackupSettingsService::class)->all();

if ($backupSettings['auto_backup_enabled']) {
    match ($backupSettings['backup_frequency']) {
        'weekly' => Schedule::command('backup:create --nivel=semanal')
            ->weeklyOn($backupSettings['backup_day_number'], $backupSettings['backup_time']),
        'monthly' => Schedule::command('backup:create --nivel=mensual')
            ->monthlyOn(1, $backupSettings['backup_time']),
        default => Schedule::command('backup:create --nivel=diario')
            ->daily()
            ->at($backupSettings['backup_time']),
    };
}

Schedule::command('backup:cleanup')->daily()->at('05:00');
Schedule::command('backup:sync-cloud')->hourly();
