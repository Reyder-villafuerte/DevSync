<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
         * product_recipe_items
         *
         * Una receta puede utilizar:
         * - un insumo: supply_id
         * - un producto: component_product_id
         */

        Schema::table('product_recipe_items', function (Blueprint $table) {
            /*
             * La FK de product_id necesita un índice independiente.
             * Lo creamos antes de eliminar el UNIQUE compuesto.
             */
            $table->index('product_id');

            /*
             * El índice UNIQUE original ya no debe existir porque
             * supply_id ahora puede ser NULL y también puede utilizarse
             * component_product_id.
             */
            $table->dropUnique([
                'product_id',
                'supply_id'
            ]);

            /*
             * supply_id ahora es opcional.
             */
            $table->foreignId('supply_id')
                ->nullable()
                ->change();

            /*
             * Un producto puede ser componente de otro producto.
             */
            $table->foreignId('component_product_id')
                ->nullable()
                ->after('supply_id')
                ->constrained('products')
                ->cascadeOnDelete();

            /*
             * Índice para búsquedas por producto e insumo.
             */
            $table->index([
                'product_id',
                'supply_id'
            ]);
        });

        /*
         * production_order_items
         *
         * Una orden de producción puede utilizar:
         * - un insumo: supply_id
         * - un producto: component_product_id
         */

        Schema::table('production_order_items', function (Blueprint $table) {
            /*
             * La FK de production_order_id necesita un índice
             * independiente antes de eliminar el UNIQUE compuesto.
             */
            $table->index('production_order_id');

            /*
             * El UNIQUE original ya no es necesario.
             */
            $table->dropUnique([
                'production_order_id',
                'supply_id'
            ]);

            /*
             * supply_id ahora es opcional.
             */
            $table->foreignId('supply_id')
                ->nullable()
                ->change();

            /*
             * Permitir productos como componentes de producción.
             */
            $table->foreignId('component_product_id')
                ->nullable()
                ->after('supply_id')
                ->constrained('products')
                ->cascadeOnDelete();

            /*
             * Índice para búsquedas por orden e insumo.
             */
            $table->index([
                'production_order_id',
                'supply_id'
            ]);
        });
    }

    public function down(): void
    {
        /*
         * Restaurar production_order_items
         */
        Schema::table('production_order_items', function (Blueprint $table) {
            $table->dropIndex([
                'production_order_id',
                'supply_id'
            ]);

            $table->dropForeign([
                'component_product_id'
            ]);

            $table->dropColumn('component_product_id');

            $table->foreignId('supply_id')
                ->nullable(false)
                ->change();

            $table->unique([
                'production_order_id',
                'supply_id'
            ]);

            $table->dropIndex([
                'production_order_id'
            ]);
        });

        /*
         * Restaurar product_recipe_items
         */
        Schema::table('product_recipe_items', function (Blueprint $table) {
            $table->dropIndex([
                'product_id',
                'supply_id'
            ]);

            $table->dropForeign([
                'component_product_id'
            ]);

            $table->dropColumn('component_product_id');

            $table->foreignId('supply_id')
                ->nullable(false)
                ->change();

            $table->unique([
                'product_id',
                'supply_id'
            ]);

            $table->dropIndex([
                'product_id'
            ]);
        });
    }
};
