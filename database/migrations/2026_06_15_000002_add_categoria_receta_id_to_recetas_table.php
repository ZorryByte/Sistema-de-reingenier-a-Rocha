<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recetas', function (Blueprint $table) {
            $table->foreignId('categoria_receta_id')
                ->nullable()
                ->constrained('categorias_receta')
                ->nullOnDelete();

            $table->index('categoria_receta_id');
        });
    }

    public function down(): void
    {
        Schema::table('recetas', function (Blueprint $table) {
            $table->dropForeign(['categoria_receta_id']);
            $table->dropIndex(['categoria_receta_id']);
            $table->dropColumn('categoria_receta_id');
        });
    }
};
