<?php

namespace App\Console\Commands;

use App\Models\BackupSnapshot;
use Illuminate\Console\Command;

class BackupListCommand extends Command
{
    protected $signature = 'backup:list';

    protected $description = 'Listar todos los respaldos disponibles';

    public function handle(): int
    {
        $backups = BackupSnapshot::orderBy('created_at', 'desc')->get();

        if ($backups->isEmpty()) {
            $this->info('No hay respaldos registrados.');

            return self::SUCCESS;
        }

        $filas = $backups->map(fn ($b) => [
            $b->id,
            $b->nivel,
            $b->completado_en?->format('d/m/Y H:i') ?? 'En progreso',
            $b->tamano_humano,
            $b->estado,
            $b->uuid,
        ])->toArray();

        $this->table(
            ['ID', 'Nivel', 'Fecha', 'Tamaño', 'Estado', 'UUID'],
            $filas
        );

        $this->newLine();
        $this->info("Total: {$backups->count()} respaldos");

        return self::SUCCESS;
    }
}
