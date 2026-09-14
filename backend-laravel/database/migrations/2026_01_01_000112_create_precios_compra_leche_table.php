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
        DB::unprepared(<<<'SQL'
            CREATE TRIGGER precios_compra_sin_solape_bi
            BEFORE INSERT ON precios_compra_leche FOR EACH ROW
            BEGIN
                IF NEW.deleted = 0 AND EXISTS (
                    SELECT 1 FROM precios_compra_leche
                    WHERE deleted = 0
                      AND vigente_desde <= COALESCE(NEW.vigente_hasta, '9999-12-31')
                      AND COALESCE(vigente_hasta, '9999-12-31') >= NEW.vigente_desde
                ) THEN
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Las vigencias de compra de leche no pueden solaparse';
                END IF;
            END
        SQL);
        DB::unprepared(<<<'SQL'
            CREATE TRIGGER precios_compra_sin_solape_bu
            BEFORE UPDATE ON precios_compra_leche FOR EACH ROW
            BEGIN
                IF NEW.deleted = 0 AND EXISTS (
                    SELECT 1 FROM precios_compra_leche
                    WHERE id <> OLD.id
                      AND deleted = 0
                      AND vigente_desde <= COALESCE(NEW.vigente_hasta, '9999-12-31')
                      AND COALESCE(vigente_hasta, '9999-12-31') >= NEW.vigente_desde
                ) THEN
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Las vigencias de compra de leche no pueden solaparse';
                END IF;
            END
        SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('precios_compra_leche');
    }
};
