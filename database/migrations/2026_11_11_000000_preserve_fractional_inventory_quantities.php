<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->changeScale(12, 4);
    }

    public function down(): void
    {
        foreach (['venta_detalles' => 'cantidad', 'presentaciones_producto' => 'stock_actual', 'ingredientes' => 'stock_actual', 'kardex_inventario' => 'cantidad'] as $table => $column) {
            if (DB::table($table)->whereRaw("$column <> ROUND($column, 2)")->exists()) {
                throw new RuntimeException('Hay cantidades con más de dos decimales. Reducir la precisión perdería datos.');
            }
        }

        $this->changeScale(10, 2);
    }

    private function changeScale(int $precision, int $scale): void
    {
        Schema::table('venta_detalles', function (Blueprint $table) use ($precision, $scale) {
            $table->decimal('cantidad', $precision, $scale)->change();
        });
        Schema::table('presentaciones_producto', function (Blueprint $table) use ($precision, $scale) {
            $table->decimal('stock_actual', $precision, $scale)->default(0)->change();
        });
        Schema::table('ingredientes', function (Blueprint $table) use ($precision, $scale) {
            $table->decimal('stock_actual', $precision, $scale)->default(0)->change();
        });
        Schema::table('kardex_inventario', function (Blueprint $table) use ($precision, $scale) {
            $table->decimal('cantidad', $precision, $scale)->nullable()->change();
        });
    }
};
