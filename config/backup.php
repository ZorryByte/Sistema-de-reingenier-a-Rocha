<?php

return [

    'ruta_base' => env('BACKUP_STORAGE_PATH', storage_path('app/backups')),

    'storage' => [
        'disk' => env('BACKUP_STORAGE_DISK', 'local'),
        'cloud_disk' => env('BACKUP_CLOUD_DISK', 'google_drive'),
        'cloud_path' => env('BACKUP_CLOUD_PATH', 'backups'),
    ],

    'retencion' => [
        'diario' => 30,
        'semanal' => 180,
        'mensual' => 365,
    ],

    'niveles' => [
        'diario' => [
            'habilitado' => true,
            'hora' => '02:00',
        ],
        'semanal' => [
            'habilitado' => true,
            'dia' => 0,
            'hora' => '03:00',
        ],
        'mensual' => [
            'habilitado' => true,
            'dia' => 1,
            'hora' => '04:00',
        ],
    ],

    'mysqldump' => [
        'single_transaction' => true,
        'quick' => true,
        'max_allowed_packet' => '256M',
        'routines' => true,
        'events' => true,
    ],

    'timeout_segundos' => 1800,

    'espacio_minimo_mb' => 1024,

    'tablas_excluir' => [
        'jobs',
        'job_batches',
        'failed_jobs',
        'cache',
        'cache_locks',
        'sessions',
    ],

];
