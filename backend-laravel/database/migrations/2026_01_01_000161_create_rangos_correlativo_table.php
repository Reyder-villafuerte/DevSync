<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Rango [desde, hasta] de números de comprobante RESERVADO a un
        // dispositivo (requisito técnico 6). Offline, el dispositivo consume su
        // rango sin colisión; sin huecos porque los rangos son contiguos y sin
        // repeticiones por la restricción de exclusión.
        Schema::create('rangos_correlativo', function (Blueprint $table) {
            $table->idUuid();
            $table->fkUuid('correlativo_id', 'correlativos');
            $table->fkUuid('dispositivo_id', 'dispositivos');
            $table->unsignedBigInteger('numero_desde');
            $table->unsignedBigInteger('numero_hasta');
            $table->unsignedBigInteger('numero_siguiente')->comment('Próximo a emitir dentro del rango');
            $table->boolean('agotado')->default(false);
            $table->timestampTz('reservado_en');
            $table->columnasSincronizacion();

            $table->index(['correlativo_id', 'dispositivo_id']);
        });

        DB::statement('ALTER TABLE rangos_correlativo ADD CONSTRAINT rc_rango_valido_chk CHECK (numero_desde <= numero_hasta AND numero_siguiente >= numero_desde AND numero_siguiente <= numero_hasta + 1)');
        // Dos rangos de la misma serie no pueden solaparse. El servicio bloquea
        // la fila del correlativo y estos triggers mantienen además la garantía
        // ante escrituras que no pasen por ese servicio.
        DB::unprepared(<<<'SQL'
            CREATE TRIGGER rangos_correlativo_sin_solape_bi
            BEFORE INSERT ON rangos_correlativo FOR EACH ROW
            BEGIN
                IF EXISTS (
                    SELECT 1 FROM rangos_correlativo
                    WHERE correlativo_id = NEW.correlativo_id
                      AND numero_desde <= NEW.numero_hasta
                      AND numero_hasta >= NEW.numero_desde
                ) THEN
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Los rangos de un correlativo no pueden solaparse';
                END IF;
            END
        SQL);
        DB::unprepared(<<<'SQL'
            CREATE TRIGGER rangos_correlativo_sin_solape_bu
            BEFORE UPDATE ON rangos_correlativo FOR EACH ROW
            BEGIN
                IF EXISTS (
                    SELECT 1 FROM rangos_correlativo
                    WHERE id <> OLD.id
                      AND correlativo_id = NEW.correlativo_id
                      AND numero_desde <= NEW.numero_hasta
                      AND numero_hasta >= NEW.numero_desde
                ) THEN
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Los rangos de un correlativo no pueden solaparse';
                END IF;
            END
        SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('rangos_correlativo');
    }
};
