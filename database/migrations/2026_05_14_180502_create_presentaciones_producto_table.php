<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('presentaciones_producto', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')
                ->constrained('productos')
                ->cascadeOnDelete();
            $table->string('nombre', 60);
            $table->decimal('cantidad_unidades', 10, 4);
            $table->decimal('precio', 10, 2);
            $table->enum('tipo_venta', ['unidad', 'peso', 'porcion'])->nullable()->default('unidad');
            $table->decimal('stock_actual', 10, 2)->default(0);
            $table->decimal('stock_minimo', 10, 2)->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->index('producto_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('presentaciones_producto');
    }
};
