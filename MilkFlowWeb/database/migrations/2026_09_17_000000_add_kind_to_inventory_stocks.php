<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * El almacén deja de ser una lista plana donde el queso y las botellas se ven
 * igual.
 *
 * Una fila de stock ahora declara qué es: un insumo (se compra y se consume,
 * nunca tiene precio de venta) o un producto terminado (se produce y se vende,
 * con sus tres tarifas). Así la pantalla puede mostrar el costo de lo comprado
 * en un lado y el precio de lo vendible en el otro, sin adivinar por el código.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_stocks', function (Blueprint $table) {
            // Nullable a propósito: una fila vieja que no case con el catálogo
            // se queda sin clasificar en vez de mentir que es un insumo.
            $table->string('kind', 10)->nullable()->after('item_name');
            $table->foreignId('supply_id')->nullable()->after('kind')
                ->constrained('supplies')->nullOnDelete();
            $table->foreignId('product_id')->nullable()->after('supply_id')
                ->constrained('products')->nullOnDelete();

            $table->index('kind');
        });

        // El item_code es único en las tres tablas, así que alcanza para cruzar.
        DB::table('supplies')->orderBy('id')->chunk(200, function ($insumos) {
            foreach ($insumos as $insumo) {
                DB::table('inventory_stocks')
                    ->where('item_code', $insumo->item_code)
                    ->update(['kind' => 'insumo', 'supply_id' => $insumo->id]);
            }
        });

        DB::table('products')->orderBy('id')->chunk(200, function ($productos) {
            foreach ($productos as $producto) {
                DB::table('inventory_stocks')
                    ->where('item_code', $producto->item_code)
                    ->update(['kind' => 'producto', 'product_id' => $producto->id]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('inventory_stocks', function (Blueprint $table) {
            $table->dropForeign(['supply_id']);
            $table->dropForeign(['product_id']);
            $table->dropIndex(['kind']);
            $table->dropColumn(['kind', 'supply_id', 'product_id']);
        });
    }
};
