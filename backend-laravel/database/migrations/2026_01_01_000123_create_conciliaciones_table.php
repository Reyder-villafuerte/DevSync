<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Comparación en planta: litros del acopiador vs. caudalímetro.
        // Diferencia y alerta se PERSISTEN calculadas (no se recalculan al
        // leer): la tolerancia vigente al momento de conciliar podría cambiar.
        Schema::create('conciliaciones', function (Blueprint $table) {
            $table->idUuid();
            $table->fkUuid('ruta_acopio_id', 'rutas_acopio');
            $table->fkUuid('registrado_por', 'usuarios');
            $table->decimal('litros_acopiador', 10, 2);
            $table->decimal('litros_caudalimetro', 10, 2);
            $table->decimal('diferencia_litros', 10, 2)->comment('caudalimetro - acopiador (persistido)');
            $table->decimal('diferencia_porcentaje', 6, 3)->comment('|dif| / acopiador * 100 (persistido)');
            $table->decimal('tolerancia_aplicada_pct', 5, 2)->default(1.00);
            $table->boolean('tiene_alerta')->comment('|dif%| > tolerancia (persistido)');
            $table->string('observacion')->nullable();
            $table->timestampTz('conciliado_en');
            $table->columnasSincronizacion();

            $table->unique('ruta_acopio_id');
        });

        DB::statement('ALTER TABLE conciliaciones ADD CONSTRAINT conciliaciones_litros_positivos_chk CHECK (litros_acopiador >= 0 AND litros_caudalimetro >= 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('conciliaciones');
    }
};
