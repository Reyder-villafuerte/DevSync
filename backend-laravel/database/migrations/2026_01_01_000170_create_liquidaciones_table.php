<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Liquidación semanal por productor. Todos los montos se CONGELAN al
        // calcular (precio de leche de esa semana, sanciones vigentes), de modo
        // que una liquidación pasada no cambia si hoy cambia una tarifa.
        Schema::create('liquidaciones', function (Blueprint $table) {
            $table->idUuid();
            $table->fkUuid('semana_pago_id', 'semanas_pago');
            $table->fkUuid('productor_id', 'productores');
            $table->decimal('litros_totales', 10, 2)->default(0);
            $table->decimal('precio_litro_aplicado', 10, 4)->comment('Snapshot: normal o degradado (RN-05)');
            $table->boolean('tarifa_degradada')->default(false);
            $table->decimal('monto_bruto', 12, 2)->default(0);
            $table->decimal('total_descuentos', 12, 2)->default(0);
            $table->decimal('monto_neto', 12, 2)->default(0);
            $table->string('estado')->default('calculada')->comment('calculada | pagada | entregada | anulada');
            $table->timestampTz('sobre_entregado_en')->nullable();
            $table->columnasSincronizacion();

            // Una sola liquidación por productor y semana.
            $table->unique(['semana_pago_id', 'productor_id']);
        });

        DB::statement('ALTER TABLE liquidaciones ADD CONSTRAINT liq_montos_no_negativos_chk CHECK (litros_totales >= 0 AND monto_bruto >= 0 AND total_descuentos >= 0)');
        DB::statement('ALTER TABLE liquidaciones ADD CONSTRAINT liq_neto_coherente_chk CHECK (monto_neto = round(monto_bruto - total_descuentos, 2))');
        DB::statement("ALTER TABLE liquidaciones ADD CONSTRAINT liq_estado_chk CHECK (estado IN ('calculada','pagada','entregada','anulada'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('liquidaciones');
    }
};
