<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tarifa de compra de leche por litro, HISTORIZADA. Incluye la tarifa
        // normal (S/ 1.70) y la mínima degradada de RN-05 (S/ 0.60-0.70).
        // La liquidación de una semana pasada usa la fila vigente en esa semana.
        Schema::create('precios_compra_leche', function (Blueprint $table) {
            $table->idUuid();
            $table->decimal('precio_litro', 10, 4);
            $table->decimal('precio_litro_minimo', 10, 4)->comment('Tarifa degradada RN-05 (expulsión por agua >= 5%)');
            $table->date('vigente_desde');
            $table->date('vigente_hasta')->nullable();
            $table->columnasSincronizacion();

            $table->index('vigente_desde');
        });

        DB::statement('ALTER TABLE precios_compra_leche ADD CONSTRAINT pcl_precios_positivos_chk CHECK (precio_litro > 0 AND precio_litro_minimo > 0 AND precio_litro_minimo <= precio_litro)');
        DB::statement('ALTER TABLE precios_compra_leche ADD CONSTRAINT pcl_vigencia_chk CHECK (vigente_hasta IS NULL OR vigente_hasta >= vigente_desde)');
        DB::statement('CREATE EXTENSION IF NOT EXISTS btree_gist');
        DB::statement("ALTER TABLE precios_compra_leche ADD CONSTRAINT pcl_sin_solape_excl EXCLUDE USING gist (daterange(vigente_desde, COALESCE(vigente_hasta, 'infinity'::date), '[]') WITH &&) WHERE (deleted = false)");
    }

    public function down(): void
    {
        Schema::dropIfExists('precios_compra_leche');
    }
};
