<?php

use App\Enums\TipoSancion;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Consecuencia económica/administrativa de un dictamen de calidad.
        // La genera EvaluacionCalidadService; la consume LiquidacionService.
        Schema::create('sanciones', function (Blueprint $table) {
            $table->idUuid();
            $table->fkUuid('productor_id', 'productores');
            $table->fkUuid('control_calidad_id', 'controles_calidad');
            // Semana en cuya liquidación se aplica el descuento. Se fija cuando
            // la sanción cae dentro de un ciclo abierto.
            $table->fkUuid('semana_pago_id', 'semanas_pago', nullable: true);
            $table->string('tipo')->comment('Enum TipoSancion');
            // El descuento por agua se expresa como % del bruto de la semana;
            // LiquidacionService lo convierte a monto y lo congela en
            // monto_descuento al liquidar (requisito técnico 4).
            $table->decimal('porcentaje_descuento', 5, 4)->nullable();
            $table->decimal('monto_descuento', 10, 2)->nullable();
            $table->decimal('tarifa_degradada_litro', 10, 4)->nullable()->comment('S/ 0.60-0.70 (RN-05 >= 5%)');
            $table->boolean('retira_del_padron')->default(false);
            $table->boolean('expulsa')->default(false);
            $table->boolean('aplicada_en_liquidacion')->default(false);
            $table->string('estado')->default('vigente')->comment('vigente | aplicada | anulada');
            $table->string('detalle')->nullable();
            $table->columnasSincronizacion();

            $table->index(['productor_id', 'estado']);
            $table->index('semana_pago_id');
        });

        DB::statement(
            'ALTER TABLE sanciones ADD CONSTRAINT sanciones_tipo_chk CHECK (tipo IN ('
            .collect(TipoSancion::cases())->map(fn ($t) => "'{$t->value}'")->implode(',')
            .'))'
        );
        DB::statement("ALTER TABLE sanciones ADD CONSTRAINT sanciones_estado_chk CHECK (estado IN ('vigente','aplicada','anulada'))");
        DB::statement('ALTER TABLE sanciones ADD CONSTRAINT sanciones_monto_no_negativo_chk CHECK (monto_descuento IS NULL OR monto_descuento >= 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('sanciones');
    }
};
