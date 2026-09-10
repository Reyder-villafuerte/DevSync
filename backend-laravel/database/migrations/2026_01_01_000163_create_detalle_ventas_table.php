<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('detalle_ventas', function (Blueprint $table) {
            $table->idUuid();
            $table->fkUuid('venta_id', 'ventas');
            $table->fkUuid('producto_id', 'productos');
            // Precio historizado usado (requisito técnico 4): se referencia la
            // fila de precios_venta vigente y se congela el valor en la línea.
            $table->fkUuid('precio_venta_id', 'precios_venta', nullable: true);
            $table->decimal('cantidad', 12, 2);
            $table->decimal('precio_unitario', 10, 2)->comment('Snapshot del precio vigente a la fecha');
            $table->decimal('subtotal', 12, 2);
            $table->columnasSincronizacion();

            $table->index('venta_id');
        });

        DB::statement('ALTER TABLE detalle_ventas ADD CONSTRAINT dv_cantidad_positiva_chk CHECK (cantidad > 0)');
        DB::statement('ALTER TABLE detalle_ventas ADD CONSTRAINT dv_subtotal_coherente_chk CHECK (subtotal = round(cantidad * precio_unitario, 2))');
    }

    public function down(): void
    {
        Schema::dropIfExists('detalle_ventas');
    }
};
