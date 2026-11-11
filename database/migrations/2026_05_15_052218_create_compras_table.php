<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('compras', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proveedor_id')->nullable()->constrained('proveedores')->nullOnDelete();
            $table->foreignId('user_id')->constrained('users');
            $table->timestamp('fecha')->useCurrent();
            $table->decimal('total', 12, 2);
            $table->text('notas')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('proveedor_id');
            $table->index('user_id');
            $table->index('fecha');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('compras');
    }
};
