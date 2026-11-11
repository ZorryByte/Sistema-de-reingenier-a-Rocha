<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clientes', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 120);
            $table->string('telefono', 16)->unique();
            $table->timestamp('archivado_at')->nullable();
            $table->timestamps();
            $table->index(['archivado_at', 'nombre']);
        });

        foreach (['pedidos' => 'fecha', 'ventas' => 'created_at'] as $nombre => $fecha) {
            Schema::table($nombre, function (Blueprint $table) use ($fecha) {
                $table->foreignId('cliente_id')->nullable()->constrained('clientes')->restrictOnDelete();
                $table->index(['cliente_id', $fecha]);
            });
        }
    }

    public function down(): void
    {
        foreach (['ventas' => 'created_at', 'pedidos' => 'fecha'] as $nombre => $fecha) {
            Schema::table($nombre, function (Blueprint $table) use ($fecha) {
                $table->dropForeign(['cliente_id']);
                $table->dropIndex(['cliente_id', $fecha]);
                $table->dropColumn('cliente_id');
            });
        }

        Schema::dropIfExists('clientes');
    }
};
