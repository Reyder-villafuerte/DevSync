<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ventas', function (Blueprint $table) {
            $table->idUuid();
            $table->fkUuid('cliente_id', 'clientes');
            $table->fkUuid('registrado_por', 'usuarios');
            $table->fkUuid('rango_correlativo_id', 'rangos_correlativo', nullable: true);
            $table->string('tipo_comprobante', 20);
            $table->string('serie_comprobante', 10);
            $table->unsignedBigInteger('numero_comprobante');
            $table->date('fecha');
            $table->decimal('total', 12, 2)->default(0);
            $table->string('estado')->default('emitida')->comment('emitida | anulada');
            $table->columnasSincronizacion();

            // Sin repeticiones de comprobante (requisito técnico 6).
            $table->unique(['tipo_comprobante', 'serie_comprobante', 'numero_comprobante']);
            $table->index(['cliente_id', 'fecha']);
        });

        DB::statement('ALTER TABLE ventas ADD CONSTRAINT ventas_total_no_negativo_chk CHECK (total >= 0)');
        DB::statement("ALTER TABLE ventas ADD CONSTRAINT ventas_estado_chk CHECK (estado IN ('emitida','anulada'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('ventas');
    }
};
