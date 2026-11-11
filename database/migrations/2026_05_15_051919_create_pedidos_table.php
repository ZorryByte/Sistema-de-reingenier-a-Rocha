<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pedidos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->enum('estado', [
                'en preparacion',
                'orden lista',
                'entregado',
                'cancelado',
            ])->default('en preparacion');
            $table->enum('tipo', [
                'pedido',
                'orden',
            ])->default('pedido');
            $table->timestamp('fecha')->useCurrent();
            $table->timestamp('updated_at')->nullable();
            $table->timestamp('fecha_entrega_estimada')->nullable();
            $table->text('notas')->nullable();

            $table->index(['tipo', 'estado', 'fecha'], 'pedidos_tipo_estado_fecha_index');
            $table->index('fecha_entrega_estimada', 'pedidos_fecha_entrega_index');
        });

        Schema::create('pedido_historial', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pedido_id')->constrained('pedidos')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('evento', 40);
            $table->string('estado_anterior', 30)->nullable();
            $table->string('estado_nuevo', 30)->nullable();
            $table->json('cambios')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['pedido_id', 'created_at'], 'pedido_historial_pedido_fecha_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pedido_historial');
        Schema::dropIfExists('pedidos');
    }
};
