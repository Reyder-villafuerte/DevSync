<?php

use App\Enums\EstadoSesionProduccion;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Sesión de producción (queso paria fresco, pasteurizado, yogur).
        // Al COMPLETARSE dispara un movimiento_stock de ingreso. El rendimiento
        // (RN-08) se calcula y persiste al completar.
        Schema::create('sesiones_produccion', function (Blueprint $table) {
            $table->idUuid();
            $table->fkUuid('producto_id', 'productos');
            $table->fkUuid('jefe_produccion_id', 'usuarios');
            $table->string('lote_codigo')->unique();
            $table->date('fecha');
            $table->decimal('litros_procesados', 10, 2);
            $table->integer('unidades_producidas')->nullable();
            // Persistidos al completar:
            $table->decimal('rendimiento_por_100l', 6, 2)->nullable()->comment('unidades / litros * 100');
            $table->boolean('cumple_rn08')->nullable();
            $table->string('rendimiento_detalle')->nullable();
            $table->string('estado')->default(EstadoSesionProduccion::PLANIFICADA->value);
            $table->timestampTz('completada_en')->nullable();
            $table->string('observaciones')->nullable();
            $table->columnasSincronizacion();

            $table->index(['producto_id', 'fecha']);
            $table->index('estado');
        });

        DB::statement('ALTER TABLE sesiones_produccion ADD CONSTRAINT sp_litros_positivos_chk CHECK (litros_procesados > 0)');
        DB::statement('ALTER TABLE sesiones_produccion ADD CONSTRAINT sp_unidades_no_negativas_chk CHECK (unidades_producidas IS NULL OR unidades_producidas >= 0)');
        DB::statement(
            'ALTER TABLE sesiones_produccion ADD CONSTRAINT sp_estado_chk CHECK (estado IN ('
            .collect(EstadoSesionProduccion::cases())->map(fn ($e) => "'{$e->value}'")->implode(',')
            .'))'
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('sesiones_produccion');
    }
};
