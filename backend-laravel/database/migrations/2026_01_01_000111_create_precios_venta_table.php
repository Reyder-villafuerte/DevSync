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
        // No solapamiento de vigencias para el mismo producto+tipo_cliente
        // (exclusion constraint de PostgreSQL sobre rango de fechas).
        DB::statement('CREATE EXTENSION IF NOT EXISTS btree_gist');
        DB::statement("ALTER TABLE precios_venta ADD CONSTRAINT precios_venta_sin_solape_excl EXCLUDE USING gist (producto_id WITH =, tipo_cliente WITH =, daterange(vigente_desde, COALESCE(vigente_hasta, 'infinity'::date), '[]') WITH &&) WHERE (deleted = false)");
    }

    public function down(): void
    {
        Schema::dropIfExists('precios_venta');
    }
};
