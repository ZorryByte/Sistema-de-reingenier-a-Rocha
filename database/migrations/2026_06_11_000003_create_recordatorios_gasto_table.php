<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recordatorios_gasto', function (Blueprint $table) {
            $table->id();
            $table->foreignId('categoria_id')->constrained('categoria_gastos')->restrictOnDelete();
            $table->foreignId('proveedor_id')->nullable()->constrained('proveedores')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('concepto', 160);
            $table->decimal('monto_estimado', 12, 2)->unsigned()->nullable();
            $table->enum('metodo_pago', ['efectivo', 'tarjeta', 'transferencia', 'cheque'])->nullable();
            $table->enum('unidad_frecuencia', ['dias', 'semanas', 'meses']);
            $table->unsignedSmallInteger('intervalo')->default(1);
            $table->date('proxima_fecha');
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->index(['activo', 'proxima_fecha']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recordatorios_gasto');
    }
};
