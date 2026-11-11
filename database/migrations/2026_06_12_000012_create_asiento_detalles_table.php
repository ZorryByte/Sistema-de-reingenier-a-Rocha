<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asiento_detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asiento_contable_id')
                ->constrained('asientos_contables')
                ->restrictOnDelete();
            $table->foreignId('cuenta_contable_id')
                ->constrained('cuentas_contables');
            $table->decimal('debe', 12, 2)->unsigned()->default(0);
            $table->decimal('haber', 12, 2)->unsigned()->default(0);
            $table->string('descripcion', 255)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asiento_detalles');
    }
};
