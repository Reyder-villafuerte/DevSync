<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Solicitud del productor para cambiar de zona; la aprueba
        // Administración. Al aprobar, el Service actualiza productores.zona_id.
        Schema::create('solicitudes_cambio_zona', function (Blueprint $table) {
            $table->idUuid();
            $table->fkUuid('productor_id', 'productores');
            $table->fkUuid('zona_actual_id', 'zonas');
            $table->fkUuid('zona_solicitada_id', 'zonas');
            $table->string('motivo');
            $table->string('estado')->default('pendiente')->comment('pendiente | aprobada | rechazada');
            $table->fkUuid('resuelto_por', 'usuarios', nullable: true);
            $table->timestampTz('resuelto_en')->nullable();
            $table->string('comentario_resolucion')->nullable();
            $table->columnasSincronizacion();

            $table->index(['productor_id', 'estado']);
        });

        DB::statement("ALTER TABLE solicitudes_cambio_zona ADD CONSTRAINT scz_estado_chk CHECK (estado IN ('pendiente','aprobada','rechazada'))");
        DB::statement('ALTER TABLE solicitudes_cambio_zona ADD CONSTRAINT scz_zonas_distintas_chk CHECK (zona_actual_id <> zona_solicitada_id)');
    }

    public function down(): void
    {
        Schema::dropIfExists('solicitudes_cambio_zona');
    }
};
