<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 4 zonas geográficas, repartidas en las 2 rutas.
        Schema::create('zonas', function (Blueprint $table) {
            $table->idUuid();
            $table->string('nombre')->unique();
            $table->fkUuid('ruta_id', 'rutas');
            $table->decimal('latitud', 10, 7)->nullable();
            $table->decimal('longitud', 10, 7)->nullable();
            $table->boolean('activa')->default(true);
            $table->columnasSincronizacion();

            $table->index('ruta_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('zonas');
    }
};
