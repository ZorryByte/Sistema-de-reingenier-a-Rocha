<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('backup_snapshots', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->enum('nivel', ['diario', 'semanal', 'mensual', 'manual']);
            $table->string('ruta_archivo');
            $table->string('storage_disk')->default('local');
            $table->string('storage_path')->nullable();
            $table->string('cloud_estado')->default('no_configurado');
            $table->string('cloud_disk')->nullable();
            $table->string('cloud_path')->nullable();
            $table->timestamp('cloud_uploaded_at')->nullable();
            $table->string('checksum', 64);
            $table->unsignedBigInteger('tamano_bytes')->default(0);
            $table->enum('estado', ['completado', 'fallido', 'en_progreso'])->default('en_progreso');
            $table->text('mensaje_error')->nullable();
            $table->timestamp('iniciado_en');
            $table->timestamp('completado_en')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('backup_snapshots');
    }
};
