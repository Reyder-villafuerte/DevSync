<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 2 rutas de camión. Catálogo administrado en planta => bajada al móvil.
        Schema::create('rutas', function (Blueprint $table) {
            $table->idUuid();
            $table->string('nombre')->unique();
            $table->string('descripcion')->nullable();
            $table->boolean('activa')->default(true);
            $table->columnasSincronizacion();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rutas');
    }
};
