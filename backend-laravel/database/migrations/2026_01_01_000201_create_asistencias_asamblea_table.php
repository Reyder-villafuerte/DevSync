<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Registro de asistencia por DNI. productor_id es nullable porque en la
        // puerta se anota el DNI y se concilia después; el DNI es la clave
        // funcional que impide doble marca.
        Schema::create('asistencias_asamblea', function (Blueprint $table) {
            $table->idUuid();
            $table->fkUuid('asamblea_id', 'asambleas');
            $table->fkUuid('productor_id', 'productores', nullable: true);
            $table->string('dni', 8);
            $table->string('nombre_completo')->nullable();
            $table->timestampTz('registrado_en');
            $table->fkUuid('registrado_por', 'usuarios', nullable: true);
            $table->columnasSincronizacion();

            $table->unique(['asamblea_id', 'dni']);
        });

        DB::statement("ALTER TABLE asistencias_asamblea ADD CONSTRAINT aa_dni_numerico_chk CHECK (dni ~ '^[0-9]{8}$')");
    }

    public function down(): void
    {
        Schema::dropIfExists('asistencias_asamblea');
    }
};
