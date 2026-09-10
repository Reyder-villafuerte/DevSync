<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Litros entregados por un productor en un recorrido. Es el hecho
        // económico que alimenta la liquidación semanal.
        Schema::create('registros_acopio', function (Blueprint $table) {
            $table->idUuid();
            $table->fkUuid('ruta_acopio_id', 'rutas_acopio');
            $table->fkUuid('productor_id', 'productores');
            $table->decimal('litros', 8, 2);
            $table->timestampTz('hora_registro');
            $table->string('observacion')->nullable();
            // El acopiador puede marcar sospecha de adulteración en campo; el
            // dictamen formal lo pone el supervisor con Lactoscan.
            $table->boolean('sospecha_adulteracion')->default(false);
            $table->columnasSincronizacion();

            // Un productor entrega una sola vez por recorrido.
            $table->unique(['ruta_acopio_id', 'productor_id']);
            $table->index('productor_id');
        });

        DB::statement('ALTER TABLE registros_acopio ADD CONSTRAINT registros_acopio_litros_positivos_chk CHECK (litros > 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('registros_acopio');
    }
};
