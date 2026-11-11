<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('serie_documentos', function (Blueprint $table) {
            $table->id();
            $table->string('serie', 3)->unique();
            $table->string('descripcion', 50);
            $table->unsignedInteger('ultimo_numero')->default(0);
            $table->unsignedInteger('year');
            $table->boolean('activo')->default(true);

            $table->unique(['serie', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('serie_documentos');
    }
};
