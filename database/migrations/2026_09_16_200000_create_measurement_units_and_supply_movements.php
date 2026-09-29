<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Dos piezas que faltaban al catálogo:
 *
 * 1. La unidad de medida deja de ser texto libre y pasa a ser catálogo
 *    configurable (Litros, Mililitros, Gramos, Kilogramos, Unidades...).
 * 2. Kardex: todo movimiento de un insumo queda registrado con su motivo y el
 *    saldo que dejó, para poder auditar en qué se fue la leche o la fruta.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('measurement_units', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50);
            $table->string('abbreviation', 10)->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('supplies', function (Blueprint $table) {
            $table->foreignId('measurement_unit_id')->nullable()->after('unit')
                ->constrained('measurement_units')->nullOnDelete();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('measurement_unit_id')->nullable()->after('unit')
                ->constrained('measurement_units')->nullOnDelete();
        });

        Schema::create('supply_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supply_id')->constrained('supplies')->cascadeOnDelete();
            $table->string('type', 30); // ingreso, consumo_produccion, devolucion, ajuste
            $table->decimal('quantity', 12, 4); // con signo: negativo = salida
            $table->decimal('balance_after', 12, 4);
            $table->foreignId('production_order_id')->nullable()->constrained('production_orders')->nullOnDelete();
            $table->foreignId('registered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->index(['supply_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supply_movements');

        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['measurement_unit_id']);
            $table->dropColumn('measurement_unit_id');
        });

        Schema::table('supplies', function (Blueprint $table) {
            $table->dropForeign(['measurement_unit_id']);
            $table->dropColumn('measurement_unit_id');
        });

        Schema::dropIfExists('measurement_units');
    }
};
