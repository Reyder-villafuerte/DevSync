<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Renglones que explican el monto neto: ingreso por leche (+) y cada
        // descuento por sanción (-). Sirve para imprimir el sobre.
        Schema::create('detalle_liquidacion', function (Blueprint $table) {
            $table->idUuid();
            $table->fkUuid('liquidacion_id', 'liquidaciones');
            $table->string('concepto')->comment('ingreso_leche | descuento_agua | sancion | ajuste');
            $table->string('descripcion');
            $table->uuid('referencia_id')->nullable()->comment('sancion_id / registro_acopio_id según concepto');
            $table->decimal('monto', 12, 2)->comment('Con signo: + suma, - descuenta');
            $table->columnasSincronizacion();

            $table->index('liquidacion_id');
        });

        DB::statement("ALTER TABLE detalle_liquidacion ADD CONSTRAINT dl_concepto_chk CHECK (concepto IN ('ingreso_leche','descuento_agua','sancion','ajuste'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('detalle_liquidacion');
    }
};
