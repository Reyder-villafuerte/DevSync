<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * La venta deja de ser «una cantidad de moldes de queso».
 *
 * Hasta ahora una venta tenía UN solo producto implícito y un solo precio, con
 * el código del queso escrito en el servicio. Con `sale_items` una venta pasa a
 * tener renglones, así que el día que la planta haga yogurt o pan se vende sin
 * tocar el código.
 *
 * `sales.cheese_molds_quantity` se conserva a propósito: la app móvil la lee en
 * su pull y la usa el arqueo de caja. Pasa a significar «unidades vendidas en
 * total», que para el queso es exactamente lo que ya era.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sale_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained('sales')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->decimal('quantity', 12, 2);
            $table->decimal('unit_price', 10, 2);
            $table->decimal('subtotal', 12, 2);
            $table->timestamps();

            $table->index('product_id');
        });

        // Una venta de varios productos no tiene «un» precio unitario.
        Schema::table('sales', function (Blueprint $table) {
            $table->decimal('unit_price', 8, 2)->nullable()->change();
        });

        // Las ventas que ya existen son todas de queso: se les arma su renglón.
        $queso = DB::table('products')
            ->where('item_code', config('huata.codigos.queso'))
            ->first();

        if (! $queso) {
            return;
        }

        DB::table('sales')->orderBy('id')->chunk(200, function ($ventas) use ($queso) {
            $ahora = now();

            foreach ($ventas as $venta) {
                DB::table('sale_items')->insert([
                    'sale_id' => $venta->id,
                    'product_id' => $queso->id,
                    'quantity' => $venta->cheese_molds_quantity,
                    'unit_price' => $venta->unit_price,
                    'subtotal' => $venta->total_amount,
                    'created_at' => $ahora,
                    'updated_at' => $ahora,
                ]);
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_items');

        Schema::table('sales', function (Blueprint $table) {
            $table->decimal('unit_price', 8, 2)->nullable(false)->change();
        });
    }
};
