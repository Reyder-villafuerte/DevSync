<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Lo que la planta le paga al productor deja de estar clavado en el código.
 *
 * Hasta ahora eran tres columnas de `system_prices` —precio base, penalidad
 * leve y penalidad grave— y un `if` en PHP que decidía cuál aplicar según el
 * porcentaje de agua. Para bonificar la leche fría, o para acopiar huevo
 * mañana, había que programar.
 *
 * Ahora cada tarifa es una fila con su condición: «si tal medida de calidad
 * cumple tal cosa, se paga tanto». La regla sin condición es la base.
 *
 * La regla de Huata —si hay agua cualquier día del ciclo la penalidad cae
 * sobre toda la semana— pasa a ser una casilla de la fila, porque puede que
 * para otro producto no aplique.
 *
 * La tarifa cuelga del insumo que se acopia (`supplies`), que es donde la
 * leche ya vive. El día que haya un catálogo de acopiables, apunta al mismo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('collection_price_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supply_id')->constrained('supplies')->cascadeOnDelete();
            $table->string('name', 80);
            // Sin métrica = tarifa base, la que se paga cuando nada más aplica.
            $table->string('metric', 50)->nullable();
            $table->string('operator', 5)->nullable(); // >, >=, <, <=, =
            $table->decimal('threshold', 10, 2)->nullable();
            $table->decimal('price_per_unit', 8, 2);
            // Huata: el castigo de un día se cobra sobre todo el ciclo.
            $table->boolean('applies_to_whole_cycle')->default(false);
            // Etiqueta que las pantallas ya usan: leve_descuento, grave_expulsion.
            $table->string('penalty_type', 30)->nullable();
            $table->unsignedInteger('priority')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['supply_id', 'is_active', 'priority']);
        });

        $this->sembrarLasTarifasDeLeche();
    }

    /** Las tres tarifas que hoy viven en `system_prices`, tal cual. */
    private function sembrarLasTarifasDeLeche(): void
    {
        $leche = DB::table('supplies')->where('item_code', config('huata.codigos.leche'))->first();

        if (! $leche) {
            return;
        }

        $vigentes = DB::table('system_prices')->where('is_active', true)->first();
        $ahora = now();

        $reglas = [
            [
                'name' => 'Tarifa base',
                'metric' => null,
                'operator' => null,
                'threshold' => null,
                'price_per_unit' => $vigentes->price_milk_base ?? 1.40,
                'applies_to_whole_cycle' => false,
                'penalty_type' => null,
                'priority' => 0,
            ],
            [
                'name' => 'Agua detectada hasta 5%',
                'metric' => 'water_addition_percentage',
                'operator' => '>',
                'threshold' => 0,
                'price_per_unit' => $vigentes->price_milk_water_penalty_low ?? 1.20,
                'applies_to_whole_cycle' => true,
                'penalty_type' => 'leve_descuento',
                'priority' => 10,
            ],
            [
                'name' => 'Agua sobre 5% (riesgo de expulsión)',
                'metric' => 'water_addition_percentage',
                'operator' => '>',
                'threshold' => 5,
                'price_per_unit' => $vigentes->price_milk_water_penalty_high ?? 0.90,
                'applies_to_whole_cycle' => true,
                'penalty_type' => 'grave_expulsion',
                'priority' => 20,
            ],
        ];

        foreach ($reglas as $regla) {
            DB::table('collection_price_rules')->insert($regla + [
                'supply_id' => $leche->id,
                'is_active' => true,
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('collection_price_rules');
    }
};
