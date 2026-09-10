<?php

use App\Enums\EstadoSemanaPago;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Ciclo de pago: jueves (inicio) a miércoles (fin), se liquida el
        // viernes con entrega de sobre físico.
        Schema::create('semanas_pago', function (Blueprint $table) {
            $table->idUuid();
            $table->date('fecha_inicio')->comment('Jueves');
            $table->date('fecha_fin')->comment('Miércoles');
            $table->date('fecha_liquidacion')->comment('Viernes');
            $table->string('estado')->default(EstadoSemanaPago::ABIERTA->value);
            $table->fkUuid('precio_compra_leche_id', 'precios_compra_leche', nullable: true)
                ->comment('Tarifa congelada al liquidar');
            $table->timestampTz('liquidada_en')->nullable();
            $table->columnasSincronizacion();

            $table->unique(['fecha_inicio', 'fecha_fin']);
        });

        DB::statement('ALTER TABLE semanas_pago ADD CONSTRAINT semanas_pago_rango_chk CHECK (fecha_fin > fecha_inicio AND fecha_liquidacion >= fecha_fin)');
        DB::statement(
            'ALTER TABLE semanas_pago ADD CONSTRAINT semanas_pago_estado_chk CHECK (estado IN ('
            .collect(EstadoSemanaPago::cases())->map(fn ($e) => "'{$e->value}'")->implode(',')
            .'))'
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('semanas_pago');
    }
};
