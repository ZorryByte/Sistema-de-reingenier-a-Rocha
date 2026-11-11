<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kardex_inventario', function (Blueprint $table) {
            $table->id();
            $table->enum('tipo', [
                'entrada',
                'salida',
                'venta',
                'ajuste',
                'merma',
                'creacion',
                'actualizacion',
                'produccion',
                'reserva',
            ]);
            $table->string('categoria', 50)->nullable();
            $table->foreignId('ingrediente_id')
                ->nullable()
                ->constrained('ingredientes')
                ->nullOnDelete();
            $table->decimal('cantidad', 10, 2)->nullable();
            $table->decimal('stock_anterior', 12, 4)->default(0);
            $table->decimal('stock_resultante', 12, 4)->default(0);
            $table->decimal('costo_anterior', 10, 4)->nullable();
            $table->decimal('costo_resultante', 10, 4)->nullable();
            $table->foreignId('venta_id')
                ->nullable()
                ->constrained('ventas')
                ->nullOnDelete();
            $table->foreignId('pedido_id')
                ->nullable()
                ->constrained('pedidos')
                ->nullOnDelete();
            $table->foreignId('compra_id')
                ->nullable()
                ->constrained('compras')
                ->nullOnDelete();
            $table->string('referencia_type', 100)->nullable();
            $table->unsignedBigInteger('referencia_id')->nullable();
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->foreignId('user_reverso_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->text('notas')->nullable();
            $table->string('origen_movimiento', 30)->default('manual');
            $table->boolean('reversible')->default(true);
            $table->timestamp('fecha')->useCurrent();
            $table->timestamp('reverted_at')->nullable();

            $table->index(['ingrediente_id', 'fecha']);
            $table->index(['ingrediente_id', 'reverted_at'], 'idx_movimientos_reversion');
            $table->index(['tipo', 'fecha']);
            $table->index(['origen_movimiento', 'fecha']);
            $table->index('compra_id');
            $table->index('reverted_at');
            $table->index(['referencia_type', 'referencia_id'], 'idx_movimientos_referencia');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kardex_inventario');
    }
};
