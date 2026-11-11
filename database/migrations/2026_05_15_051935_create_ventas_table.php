<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ventas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('pedido_id')
                ->nullable()
                ->unique('ventas_pedido_id_unique')
                ->constrained('pedidos')
                ->nullOnDelete();
            $table->decimal('total', 10, 2);
            $table->enum('estado', ['pagada', 'cancelada'])->default('pagada');
            $table->string('numero_factura', 50)->nullable();
            $table->foreignId('serie_documento_id')->nullable()
                ->constrained('serie_documentos')->nullOnDelete();
            $table->string('pdf_path', 255)->nullable();
            $table->text('notas')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['estado', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ventas');
    }
};
