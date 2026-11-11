<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('compra_detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('compra_id')->constrained('compras')->cascadeOnDelete();
            $table->foreignId('ingrediente_id')->nullable()->constrained('ingredientes')->nullOnDelete();
            $table->decimal('cantidad', 10, 2);
            $table->decimal('precio_unitario', 10, 4);
            $table->decimal('subtotal', 12, 2);
            $table->timestamp('created_at')->useCurrent();

            $table->index('compra_id');
            $table->index('ingrediente_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('compra_detalles');
    }
};
