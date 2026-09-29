<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Catálogo de producción abierto: la planta deja de saber solo hacer queso.
 *
 * Las categorías, los insumos, los productos y su receta son datos que el jefe
 * de producción crea desde la web, de modo que un yogurt de fresa embotellado o
 * una mantequilla se den de alta sin tocar el código.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Categorías que el usuario crea: "Lácteo base", "Envases", "Frutas"...
        Schema::create('inventory_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 80);
            $table->string('slug', 80)->unique();
            $table->string('scope', 20)->default('insumo'); // insumo, producto
            $table->string('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['scope', 'is_active']);
        });

        // Insumos / materia prima. El stock vive en inventory_stocks por item_code.
        Schema::create('supplies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_category_id')->constrained('inventory_categories')->cascadeOnDelete();
            $table->string('item_code', 50)->unique(); // MILK_RAW_LITERS, BOTELLA_1L, FRESA_KG
            $table->string('name', 120);
            $table->string('unit', 20); // litros, unidades, kilos
            $table->decimal('unit_cost', 10, 2)->default(0); // costo referencial de compra
            $table->decimal('minimum_stock', 12, 2)->default(0); // aviso de reposición
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Productos terminados con su tiempo de proceso y sus tres tarifas.
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_category_id')->constrained('inventory_categories')->cascadeOnDelete();
            $table->string('item_code', 50)->unique(); // CHEESE_MOLD_UNITS, YOGURT_FRESA_1L
            $table->string('name', 120);
            $table->string('unit', 20); // moldes, botellas, kilos
            $table->decimal('process_hours', 6, 2)->default(0); // lo que tarda el proceso
            $table->decimal('price_provider', 10, 2)->default(0); // proveedor de leche
            $table->decimal('price_wholesale', 10, 2)->default(0); // mayorista
            $table->decimal('price_local', 10, 2)->default(0); // cliente local
            $table->text('process_notes')->nullable(); // cuajado, fermentación, maduración...
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // Receta: qué consume UNA unidad del producto (aquí viven los 10 L del queso).
        Schema::create('product_recipe_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('supply_id')->constrained('supplies')->cascadeOnDelete();
            $table->decimal('quantity_per_unit', 12, 4);
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->unique(['product_id', 'supply_id']);
        });

        // Orden de producción: no es instantánea, tiene inicio, duración y cierre.
        Schema::create('production_orders', function (Blueprint $table) {
            $table->id();
            $table->string('batch_number', 50)->unique();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('supervisor_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('planned_quantity', 12, 2);
            $table->decimal('produced_quantity', 12, 2)->nullable(); // lo que salió de verdad
            $table->string('status', 20)->default('planificada'); // planificada, en_proceso, terminada, cancelada
            $table->timestamp('started_at')->nullable();
            $table->timestamp('expected_ready_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['status', 'expected_ready_at']);
        });

        // Consumo real de cada insumo en la orden (queda como historia del lote).
        Schema::create('production_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_order_id')->constrained('production_orders')->cascadeOnDelete();
            $table->foreignId('supply_id')->constrained('supplies')->cascadeOnDelete();
            $table->decimal('quantity_used', 12, 4);
            $table->string('unit', 20);
            $table->timestamps();

            $table->unique(['production_order_id', 'supply_id']);
        });

        // El stock plano que ya existía pasa a reconocer su categoría.
        Schema::table('inventory_stocks', function (Blueprint $table) {
            $table->foreignId('inventory_category_id')->nullable()->after('item_name')
                ->constrained('inventory_categories')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('inventory_stocks', function (Blueprint $table) {
            $table->dropForeign(['inventory_category_id']);
            $table->dropColumn('inventory_category_id');
        });

        Schema::dropIfExists('production_order_items');
        Schema::dropIfExists('production_orders');
        Schema::dropIfExists('product_recipe_items');
        Schema::dropIfExists('products');
        Schema::dropIfExists('supplies');
        Schema::dropIfExists('inventory_categories');
    }
};
