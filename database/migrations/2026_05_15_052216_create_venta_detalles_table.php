<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {

        Schema::create('venta_detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venta_id')
                ->constrained('ventas')
                ->cascadeOnDelete();
            $table->foreignId('presentacion_id')
                ->nullable()
                ->constrained('presentaciones_producto')
                ->nullOnDelete();

            $table->decimal('cantidad', 10, 2);
            $table->decimal('precio_unitario', 10, 2);
            $table->decimal('descuento', 10, 2)->default(0);
            $table->decimal('subtotal', 10, 2);
            $table->text('notas')->nullable();

            $table->index('venta_id');
            $table->index('presentacion_id');
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('venta_detalles');
    }
};
