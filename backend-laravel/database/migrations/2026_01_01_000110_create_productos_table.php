<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 3 productos: queso paria fresco, pasteurizado, yogur.
        Schema::create('productos', function (Blueprint $table) {
            $table->idUuid();
            $table->string('nombre')->unique();
            $table->string('tipo')->comment('queso_paria_fresco | pasteurizado | yogur');
            $table->string('unidad_medida', 20)->default('unidad');
            // RN-08: meta de rendimiento (quesos por 100 L). Guardada por
            // producto para no incrustar el 11-12 en el código del Service.
            $table->decimal('rendimiento_min_por_100l', 6, 2)->nullable();
            $table->decimal('rendimiento_max_por_100l', 6, 2)->nullable();
            $table->boolean('controla_rendimiento')->default(false);
            $table->boolean('activo')->default(true);
            $table->columnasSincronizacion();
        });

        DB::statement('ALTER TABLE productos ADD CONSTRAINT productos_rendimiento_rango_chk CHECK (rendimiento_min_por_100l IS NULL OR rendimiento_max_por_100l IS NULL OR rendimiento_min_por_100l <= rendimiento_max_por_100l)');
    }

    public function down(): void
    {
        Schema::dropIfExists('productos');
    }
};
