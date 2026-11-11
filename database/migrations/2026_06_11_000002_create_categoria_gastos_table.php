<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categoria_gastos', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 40)->unique();
            $table->foreignId('cuenta_contable_id')
                ->constrained('cuentas_contables')
                ->restrictOnDelete();
            $table->string('nombre', 50)->unique();
            $table->enum('tipo', ['fijo', 'variable']);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categoria_gastos');
    }
};
