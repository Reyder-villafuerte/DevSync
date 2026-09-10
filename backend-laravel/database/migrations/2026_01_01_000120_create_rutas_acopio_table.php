<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Cabecera de un recorrido de madrugada: acopiador + ruta + fecha.
        // Se crea OFFLINE en el móvil (subida = true). Su ciclo de estados:
        // en_curso -> cerrada (cierra ruta) -> descargada (en tina) ->
        // conciliada (comparada contra caudalímetro en planta).
        Schema::create('rutas_acopio', function (Blueprint $table) {
            $table->idUuid();
            $table->fkUuid('acopiador_id', 'acopiadores');
            $table->fkUuid('ruta_id', 'rutas');
            $table->fkUuid('dispositivo_id', 'dispositivos', nullable: true);
            $table->date('fecha');
            $table->timestampTz('hora_inicio');
            $table->timestampTz('hora_cierre')->nullable();
            // Snapshot del total declarado al cerrar ruta. NO es el stock; es un
            // dato de la cabecera para conciliación rápida. El detalle real vive
            // en registros_acopio.
            $table->decimal('litros_declarados', 10, 2)->default(0);
            $table->string('estado')->default('en_curso');
            $table->columnasSincronizacion();

            $table->unique(['acopiador_id', 'fecha']);
            $table->index(['ruta_id', 'fecha']);
        });

        DB::statement("ALTER TABLE rutas_acopio ADD CONSTRAINT rutas_acopio_estado_chk CHECK (estado IN ('en_curso','cerrada','descargada','conciliada'))");
        DB::statement('ALTER TABLE rutas_acopio ADD CONSTRAINT rutas_acopio_litros_no_negativos_chk CHECK (litros_declarados >= 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('rutas_acopio');
    }
};
