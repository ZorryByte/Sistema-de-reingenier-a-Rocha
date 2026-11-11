<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recetas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')
                ->nullable()
                ->constrained('productos')
                ->nullOnDelete();
            $table->foreignId('presentacion_id')
                ->nullable()
                ->constrained('presentaciones_producto')
                ->nullOnDelete();
            $table->string('nombre', 100);
            $table->text('descripcion')->nullable();
            $table->unsignedInteger('tiempo_preparacion')->nullable();
            $table->unsignedInteger('lote_unidades')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->index('producto_id');
            $table->index('presentacion_id');
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement('
                CREATE UNIQUE INDEX idx_receta_default_activa
                ON recetas ((CASE WHEN activo = TRUE AND presentacion_id IS NULL AND producto_id IS NOT NULL THEN producto_id ELSE NULL END))
            ');

            DB::statement('
                CREATE UNIQUE INDEX idx_receta_presentacion_activa
                ON recetas ((CASE WHEN activo = TRUE AND presentacion_id IS NOT NULL THEN presentacion_id ELSE NULL END))
            ');
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE recetas DROP INDEX IF EXISTS idx_receta_default_activa');
            DB::statement('ALTER TABLE recetas DROP INDEX IF EXISTS idx_receta_presentacion_activa');
        }
        Schema::dropIfExists('recetas');
    }
};
