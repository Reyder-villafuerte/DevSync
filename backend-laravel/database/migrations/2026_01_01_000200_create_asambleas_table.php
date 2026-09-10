<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Asamblea de la asociación. El quórum se calcula sobre el padrón
        // vigente y se PERSISTE (padron_snapshot, quorum_requerido) para que la
        // validez del acta no dependa de altas/bajas posteriores.
        Schema::create('asambleas', function (Blueprint $table) {
            $table->idUuid();
            $table->string('titulo');
            $table->text('descripcion')->nullable();
            $table->timestampTz('fecha');
            $table->string('lugar')->nullable();
            $table->string('tipo', 20)->default('ordinaria')->comment('ordinaria | extraordinaria');
            $table->integer('padron_snapshot')->nullable()->comment('N.º de productores activos al abrir registro');
            $table->integer('quorum_requerido')->nullable()->comment('Persistido (p. ej. 50% + 1)');
            $table->decimal('fraccion_quorum', 4, 3)->default(0.500);
            $table->integer('asistentes')->default(0);
            $table->boolean('quorum_alcanzado')->default(false);
            $table->string('estado')->default('convocada')->comment('convocada | en_curso | cerrada');
            $table->columnasSincronizacion();
        });

        DB::statement("ALTER TABLE asambleas ADD CONSTRAINT asambleas_estado_chk CHECK (estado IN ('convocada','en_curso','cerrada'))");
        DB::statement('ALTER TABLE asambleas ADD CONSTRAINT asambleas_fraccion_chk CHECK (fraccion_quorum > 0 AND fraccion_quorum <= 1)');
    }

    public function down(): void
    {
        Schema::dropIfExists('asambleas');
    }
};
