<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gastos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('categoria_id')->constrained('categoria_gastos')->restrictOnDelete();
            $table->foreignId('proveedor_id')->nullable()->constrained('proveedores')->nullOnDelete();
            $table->foreignId('recordatorio_gasto_id')->nullable()->constrained('recordatorios_gasto')->nullOnDelete();
            $table->foreignId('user_id')->constrained('users');
            $table->uuid('token_registro')->unique();
            $table->string('concepto', 160);
            $table->string('numero_documento', 100)->nullable();
            $table->timestamp('fecha')->useCurrent();
            $table->decimal('monto', 12, 2)->unsigned();
            $table->enum('metodo_pago', ['efectivo', 'tarjeta', 'transferencia', 'cheque']);
            $table->text('nota')->nullable();
            $table->enum('estado', ['registrado', 'anulado'])->default('registrado');
            $table->timestamp('anulado_at')->nullable();
            $table->foreignId('anulado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->string('motivo_anulacion', 300)->nullable();
            $table->date('periodo_recordatorio')->nullable();
            $table->decimal('monto_estimado_recordatorio', 12, 2)->unsigned()->nullable();
            $table->string('comprobante_disk', 40)->nullable();
            $table->string('comprobante_path')->nullable();
            $table->string('comprobante_nombre', 180)->nullable();
            $table->string('comprobante_mime', 100)->nullable();
            $table->unsignedBigInteger('comprobante_tamano')->nullable();
            $table->timestamps();

            $table->index(['estado', 'fecha']);
            $table->index(['categoria_id', 'fecha']);
            $table->index(['proveedor_id', 'fecha']);
            $table->index(['metodo_pago', 'fecha']);
            $table->unique(['recordatorio_gasto_id', 'periodo_recordatorio'], 'uq_gastos_recordatorio_periodo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gastos');
    }
};
