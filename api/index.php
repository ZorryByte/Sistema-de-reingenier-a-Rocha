<?php

if (getenv('VERCEL') === '1') {
    $storagePath = sys_get_temp_dir().'/rocha-storage';
    $viewsPath = $storagePath.'/framework/views';

    if (! is_dir($viewsPath) && ! mkdir($viewsPath, 0775, true) && ! is_dir($viewsPath)) {
        throw new RuntimeException('No se pudo preparar el directorio temporal de Laravel.');
    }

    $_ENV['LARAVEL_STORAGE_PATH'] = $_SERVER['LARAVEL_STORAGE_PATH'] = $storagePath;
}

require __DIR__.'/../public/index.php';
