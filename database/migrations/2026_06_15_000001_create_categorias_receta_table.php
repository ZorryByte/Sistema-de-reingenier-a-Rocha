<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categorias_receta', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 60)->unique();
            $table->string('slug', 80)->unique();
            $table->timestamps();

            $table->index('nombre');
            $table->index('slug');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categorias_receta');
    }
};
