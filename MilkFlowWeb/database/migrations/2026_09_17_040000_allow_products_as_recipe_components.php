<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Un producto terminado puede ser ingrediente de otro.
 *
 * Hasta ahora una receta solo podía listar insumos, así que no había forma de
 * expresar «el sándwich lleva un queso» ni una masa madre intermedia: el
 * catálogo se cortaba en el primer nivel.
 *
 * Cada renglón de receta (y cada consumo del lote) apunta ahora a UNA de las
 * dos cosas: un insumo o un producto componente. El índice único se retira
 * porque en MySQL dos NULL no chocan entre sí y dejaría de servir; la unicidad
 * la garantiza el servicio, que reescribe la receta entera cada vez.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_recipe_items', function (Blueprint $table) {
            $table->dropUnique(['product_id', 'supply_id']);
            $table->foreignId('supply_id')->nullable()->change();
            $table->foreignId('component_product_id')->nullable()->after('supply_id')
                ->constrained('products')->cascadeOnDelete();
            $table->index(['product_id', 'supply_id']);
        });

        Schema::table('production_order_items', function (Blueprint $table) {
            $table->dropUnique(['production_order_id', 'supply_id']);
            $table->foreignId('supply_id')->nullable()->change();
            $table->foreignId('component_product_id')->nullable()->after('supply_id')
                ->constrained('products')->cascadeOnDelete();
            $table->index(['production_order_id', 'supply_id']);
        });
    }

    public function down(): void
    {
        Schema::table('production_order_items', function (Blueprint $table) {
            $table->dropIndex(['production_order_id', 'supply_id']);
            $table->dropForeign(['component_product_id']);
            $table->dropColumn('component_product_id');
            $table->foreignId('supply_id')->nullable(false)->change();
            $table->unique(['production_order_id', 'supply_id']);
        });

        Schema::table('product_recipe_items', function (Blueprint $table) {
            $table->dropIndex(['product_id', 'supply_id']);
            $table->dropForeign(['component_product_id']);
            $table->dropColumn('component_product_id');
            $table->foreignId('supply_id')->nullable(false)->change();
            $table->unique(['product_id', 'supply_id']);
        });
    }
};
