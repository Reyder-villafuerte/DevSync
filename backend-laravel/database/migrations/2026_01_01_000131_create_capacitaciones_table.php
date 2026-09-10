<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Derivación a capacitación obligatoria en Buenas Prácticas de Ordeño.
        // RN-06 (pH < 6.5): SIN expulsión, solo capacitación.
        Schema::create('capacitaciones', function (Blueprint $table) {
            $table->idUuid();
            $table->fkUuid('productor_id', 'productores');
            $table->fkUuid('control_calidad_id', 'controles_calidad', nullable: true);
            $table->string('motivo')->default('Buenas Prácticas de Ordeño (RN-06)');
            $table->string('estado')->default('pendiente')->comment('pendiente | programada | completada');
            $table->date('fecha_programada')->nullable();
            $table->date('fecha_completada')->nullable();
            $table->columnasSincronizacion();

            $table->index(['productor_id', 'estado']);
        });

        DB::statement("ALTER TABLE capacitaciones ADD CONSTRAINT capacitaciones_estado_chk CHECK (estado IN ('pendiente','programada','completada'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('capacitaciones');
    }
};
