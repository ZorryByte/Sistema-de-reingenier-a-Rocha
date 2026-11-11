<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ordenes_produccion', function (Blueprint $table) {
            $table->id();
            $table->string('numero_lote', 50)->unique();
            $table->foreignId('presentacion_id')
                ->constrained('presentaciones_producto')
                ->cascadeOnDelete();
            $table->integer('cantidad');
            $table->foreignId('user_id')->constrained('users');
            $table->text('notas')->nullable();
            $table->timestamps();

            $table->index('numero_lote');
            $table->index('presentacion_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ordenes_produccion');
    }
};
