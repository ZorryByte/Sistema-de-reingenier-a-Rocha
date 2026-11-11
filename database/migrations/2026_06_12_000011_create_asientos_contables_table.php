<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asientos_contables', function (Blueprint $table) {
            $table->id();
            $table->timestamp('fecha_contable')->useCurrent();
            $table->string('origen', 40);
            $table->string('estado', 30)->default('confirmado');
            $table->string('referencia_type', 100)->nullable();
            $table->unsignedBigInteger('referencia_id')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('asiento_reversado_id')
                ->nullable()
                ->constrained('asientos_contables')
                ->nullOnDelete();
            $table->text('descripcion')->nullable();
            $table->timestamps();

            $table->index(['origen', 'fecha_contable']);
            $table->index(['referencia_type', 'referencia_id'], 'idx_asientos_referencia');
            $table->unique(['origen', 'referencia_type', 'referencia_id'], 'uq_asientos_origen_referencia');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asientos_contables');
    }
};
