<?php

namespace App\Console\Commands;

use App\Exceptions\BackupException;
use App\Services\BackupService;
use Illuminate\Console\Command;

class BackupCreateCommand extends Command
{
    protected $signature = 'backup:create {--nivel=manual : Nivel del respaldo (diario, semanal, mensual, manual)}';

    protected $description = 'Crear un nuevo respaldo de la base de datos';

    public function handle(BackupService $servicio): int
    {
        $nivel = $this->option('nivel');

        if (! in_array($nivel, ['diario', 'semanal', 'mensual', 'manual'])) {
            $this->error("Nivel inválido: {$nivel}. Use: diario, semanal, mensual, manual");

            return self::FAILURE;
        }

        $this->info("Iniciando respaldo de nivel '{$nivel}'...");

        try {
            $snapshot = $servicio->crearBackup($nivel);

            $this->newLine();
            $this->info('✓ Respaldo completado exitosamente');
            $this->table(
                ['Campo', 'Valor'],
                [
                    ['UUID', $snapshot->uuid],
                    ['Nivel', $snapshot->nivel],
                    ['Tamaño', $snapshot->tamano_humano],
                    ['Archivo', basename($snapshot->ruta_archivo)],
                    ['Checksum', substr($snapshot->checksum, 0, 16).'...'],
                ]
            );

            return self::SUCCESS;

        } catch (BackupException $e) {
            $this->error('✗ '.$e->getMensajeUsuario());

            return self::FAILURE;

        } catch (\Throwable $e) {
            $this->error('✗ Error inesperado: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}
