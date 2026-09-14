<?php

use App\Enums\TipoCliente;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Precio de venta por producto y tipo de cliente, HISTORIZADO
        // (requisito técnico 4). Una venta pasada conserva el precio de su
        // fecha buscando la fila cuya vigencia la contiene.
        Schema::create('precios_venta', function (Blueprint $table) {
            $table->idUuid();
            $table->fkUuid('producto_id', 'productos');
            $table->string('tipo_cliente');
            $table->decimal('precio', 10, 2);
            $table->date('vigente_desde');
            $table->date('vigente_hasta')->nullable()->comment('NULL = vigente actualmente');
            $table->columnasSincronizacion();

            $table->index(['producto_id', 'tipo_cliente', 'vigente_desde']);
        });

        DB::statement('ALTER TABLE precios_venta ADD CONSTRAINT precios_venta_precio_positivo_chk CHECK (precio > 0)');
        DB::statement('ALTER TABLE precios_venta ADD CONSTRAINT precios_venta_vigencia_chk CHECK (vigente_hasta IS NULL OR vigente_hasta >= vigente_desde)');
        DB::statement(
            'ALTER TABLE precios_venta ADD CONSTRAINT precios_venta_tipo_cliente_chk CHECK (tipo_cliente IN ('
            .collect(TipoCliente::cases())->map(fn ($t) => "'{$t->value}'")->implode(',')
            .'))'
        );
        // MySQL no tiene exclusion constraints. Los triggers conservan el
        // no-solapamiento inclusivo por producto+tipo y respetan borrado lógico.
        DB::unprepared(<<<'SQL'
            CREATE TRIGGER precios_venta_sin_solape_bi
            BEFORE INSERT ON precios_venta FOR EACH ROW
            BEGIN
                IF NEW.deleted = 0 AND EXISTS (
                    SELECT 1 FROM precios_venta
                    WHERE producto_id = NEW.producto_id
                      AND tipo_cliente = NEW.tipo_cliente
                      AND deleted = 0
                      AND vigente_desde <= COALESCE(NEW.vigente_hasta, '9999-12-31')
                      AND COALESCE(vigente_hasta, '9999-12-31') >= NEW.vigente_desde
                ) THEN
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Las vigencias de precios_venta no pueden solaparse';
                END IF;
            END
        SQL);
        DB::unprepared(<<<'SQL'
            CREATE TRIGGER precios_venta_sin_solape_bu
            BEFORE UPDATE ON precios_venta FOR EACH ROW
            BEGIN
                IF NEW.deleted = 0 AND EXISTS (
                    SELECT 1 FROM precios_venta
                    WHERE id <> OLD.id
                      AND producto_id = NEW.producto_id
                      AND tipo_cliente = NEW.tipo_cliente
                      AND deleted = 0
                      AND vigente_desde <= COALESCE(NEW.vigente_hasta, '9999-12-31')
                      AND COALESCE(vigente_hasta, '9999-12-31') >= NEW.vigente_desde
                ) THEN
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Las vigencias de precios_venta no pueden solaparse';
                END IF;
            END
        SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('precios_venta');
    }
};
