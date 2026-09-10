<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Definición de una serie de comprobante (p. ej. BOLETA B001).
        // numero_maximo_asignado es el último número YA reservado a algún
        // dispositivo. La reserva avanza este contador bajo bloqueo de fila.
        Schema::create('correlativos', function (Blueprint $table) {
            $table->idUuid();
            $table->string('tipo_comprobante', 20)->comment('boleta | factura');
            $table->string('serie', 10);
            $table->unsignedBigInteger('numero_maximo_asignado')->default(0);
            $table->unsignedInteger('tamano_bloque_default')->default(50);
            $table->columnasSincronizacion();

            $table->unique(['tipo_comprobante', 'serie']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('correlativos');
    }
};
