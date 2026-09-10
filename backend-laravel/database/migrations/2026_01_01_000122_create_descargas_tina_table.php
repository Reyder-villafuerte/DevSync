<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Cierre de ruta: el acopiador descarga en una tina de planta.
        Schema::create('descargas_tina', function (Blueprint $table) {
            $table->idUuid();
            $table->fkUuid('ruta_acopio_id', 'rutas_acopio');
            $table->string('tina', 30)->nullable();
            $table->decimal('litros_descargados', 10, 2);
            $table->timestampTz('hora_descarga');
            $table->string('recibido_por')->nullable();
            $table->columnasSincronizacion();

            $table->unique('ruta_acopio_id');
        });

        DB::statement('ALTER TABLE descargas_tina ADD CONSTRAINT descargas_tina_litros_positivos_chk CHECK (litros_descargados > 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('descargas_tina');
    }
};
