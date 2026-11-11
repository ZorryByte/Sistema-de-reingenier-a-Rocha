<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {

        Schema::create('venta_pagos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venta_id')
                ->constrained('ventas')
                ->cascadeOnDelete();
            $table->enum('metodo', ['efectivo', 'tarjeta', 'transferencia', 'otro']);
            $table->decimal('monto', 10, 2);
            $table->string('referencia', 100)->nullable(); // número de autorización de tarjeta
            $table->timestamp('created_at')->useCurrent();

            $table->index('venta_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('venta_pagos');
    }
};
