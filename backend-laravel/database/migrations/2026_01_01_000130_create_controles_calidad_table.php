<?php

use App\Enums\DictamenCalidad;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Control inopinado de calidad con Lactoscan. Las MEDICIONES y el
        // DICTAMEN se guardan juntos y el dictamen NO se recalcula al leer
        // (requisito técnico 5): las reglas RN-05/RN-06 pueden cambiar de
        // umbral y un control histórico debe conservar su veredicto.
        Schema::create('controles_calidad', function (Blueprint $table) {
            $table->idUuid();
            $table->fkUuid('productor_id', 'productores');
            $table->fkUuid('supervisor_id', 'usuarios');
            $table->fkUuid('ruta_acopio_id', 'rutas_acopio', nullable: true);
            $table->fkUuid('registro_acopio_id', 'registros_acopio', nullable: true);
            $table->fkUuid('dispositivo_id', 'dispositivos', nullable: true);
            $table->timestampTz('tomado_en');
            $table->string('tipo', 20)->default('inopinado');

            // --- Mediciones Lactoscan ---
            $table->decimal('agua_anadida_porcentaje', 5, 2)->nullable()->comment('% agua detectada (RN-05)');
            $table->decimal('ph', 4, 2)->nullable()->comment('Acidez (RN-06: < 6.5 rechaza)');
            $table->decimal('densidad', 6, 3)->nullable();
            $table->decimal('grasa_porcentaje', 5, 2)->nullable();
            $table->decimal('solidos_no_grasos_porcentaje', 5, 2)->nullable();
            $table->decimal('temperatura', 5, 2)->nullable();
            $table->jsonb('lactoscan_crudo')->nullable()->comment('Volcado íntegro del equipo');

            // --- Dictamen persistido ---
            $table->string('dictamen')->comment('Enum DictamenCalidad; congelado al momento del control');
            $table->string('dictamen_detalle')->nullable();
            $table->boolean('rechaza_lote')->default(false);
            $table->boolean('es_reincidencia')->default(false)->comment('Había sanción de agua previa vigente');
            $table->string('umbral_agua_aplicado')->nullable()->comment('Trazabilidad de la regla usada');

            $table->columnasSincronizacion();

            $table->index(['productor_id', 'tomado_en']);
            $table->index('dictamen');
        });

        DB::statement('ALTER TABLE controles_calidad ADD CONSTRAINT cc_ph_rango_chk CHECK (ph IS NULL OR (ph >= 0 AND ph <= 14))');
        DB::statement('ALTER TABLE controles_calidad ADD CONSTRAINT cc_agua_rango_chk CHECK (agua_anadida_porcentaje IS NULL OR (agua_anadida_porcentaje >= 0 AND agua_anadida_porcentaje <= 100))');
        DB::statement(
            'ALTER TABLE controles_calidad ADD CONSTRAINT cc_dictamen_chk CHECK (dictamen IN ('
            .collect(DictamenCalidad::cases())->map(fn ($d) => "'{$d->value}'")->implode(',')
            .'))'
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('controles_calidad');
    }
};
