<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * El tipo de cliente deja de tener una tarifa propia.
 *
 * Tenía una «tarifa estándar» que se cobraba por unidad de cualquier producto
 * sin precio propio, y eso no puede funcionar: el estándar de Mayorista salió
 * de los S/ 19 del queso, así que una mantequilla nueva se habría vendido a
 * S/ 19 sin que nadie lo decidiera.
 *
 * El precio siempre sale del producto, que es donde se lo pone al crearlo
 * junto con su receta. El tipo de cliente se queda con lo que de verdad le
 * toca: a quién agrupa, desde qué cantidad aplica y a qué rol del padrón se
 * reconoce solo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('client_types', function (Blueprint $table) {
            $table->dropColumn('price_per_unit');
        });
    }

    public function down(): void
    {
        Schema::table('client_types', function (Blueprint $table) {
            $table->decimal('price_per_unit', 10, 2)->default(0)->after('min_quantity');
        });
    }
};
