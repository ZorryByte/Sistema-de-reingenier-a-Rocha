<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ingredientes', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100);
            $table->enum('categoria', [
                'harinas',
                'lacteos',
                'endulzantes',
                'leudantes',
                'grasas',
                'saborizantes',
                'decoracion',
                'empaques',
                'refrigerados',
                'congelados',
                'otros',
            ])->default('otros');
            $table->foreignId('proveedor_id')
                ->nullable()
                ->constrained('proveedores')
                ->nullOnDelete();
            $table->enum('unidad_medida', ['g', 'ml', 'unidad', 'oz']);
            $table->decimal('stock_actual', 10, 2)->default(0);
            $table->decimal('stock_minimo', 10, 2)->default(0);
            $table->decimal('costo_unitario', 10, 4)->default(0);
            $table->softDeletes();
            $table->timestamps();

            $table->index('proveedor_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ingredientes');
    }
};
