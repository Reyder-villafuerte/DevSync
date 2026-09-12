<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * FASE DE PRUEBAS: se retira UNIQUE(acopiador_id, fecha) de `rutas_acopio`
 * para poder abrir y cerrar varias rutas el mismo día con el mismo acopiador.
 *
 * Para volver a la regla de negocio ("una jornada por acopiador y día"):
 *   1. php artisan migrate:rollback  (o re-crear el índice con este `down`),
 *   2. config/sync.php -> 'jornada_unica_por_dia' => true,
 *   3. móvil: IniciarJornadaUseCase(unaJornadaPorDia = true) en ModuloCompartido.
 * Antes de restaurarlo hay que limpiar los duplicados que dejen las pruebas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rutas_acopio', function (Blueprint $table) {
            $table->dropUnique(['acopiador_id', 'fecha']);
            // El índice se conserva como NO único: las consultas por acopiador
            // y día (panel de recepción, conciliación) lo siguen usando.
            $table->index(['acopiador_id', 'fecha'], 'rutas_acopio_acopiador_fecha_idx');
        });
    }

    public function down(): void
    {
        Schema::table('rutas_acopio', function (Blueprint $table) {
            $table->dropIndex('rutas_acopio_acopiador_fecha_idx');
            $table->unique(['acopiador_id', 'fecha']);
        });
    }
};
