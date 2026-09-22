<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Las tarifas dejan de estar clavadas en tres columnas.
 *
 * Hasta ahora un producto tenía exactamente tres precios —proveedor, mayorista
 * y local— escritos en su fila. Si la asociación quiere una tarifa para
 * empleados, o un convenio con un colegio, hay que tocar el código.
 *
 * Ahora son dos piezas:
 *
 * 1. `client_types`: el tipo de cliente, con su tarifa ESTÁNDAR —la que se
 *    cobra por unidad de cualquier producto que no tenga una propia—, desde
 *    qué cantidad aplica y, si corresponde, a qué rol del padrón se asigna solo.
 * 2. `product_client_type_prices`: la tarifa concreta de UN producto para UN
 *    tipo. Cuando existe, manda sobre el estándar.
 *
 * Las tres columnas viejas de `products` se conservan: la app móvil las lee en
 * su sincronización. Pasan a ser espejo de los tres tipos sembrados.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_types', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50);
            $table->string('slug', 50)->unique();
            $table->decimal('min_quantity', 10, 2)->default(0);
            $table->decimal('price_per_unit', 10, 2)->default(0);
            // Rol del padrón que cae solo en este tipo (productor, empleado...).
            $table->string('auto_role', 30)->nullable();
            $table->string('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'min_quantity']);
        });

        Schema::create('product_client_type_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('client_type_id')->constrained('client_types')->cascadeOnDelete();
            $table->decimal('price_per_unit', 10, 2);
            $table->timestamps();

            $table->unique(['product_id', 'client_type_id']);
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->foreignId('client_type_id')->nullable()->after('type')
                ->constrained('client_types')->nullOnDelete();
        });

        $this->sembrarLosTiposQueYaExistian();
    }

    /**
     * Los tres tipos de siempre se dan de alta con lo que hoy cobra el queso,
     * y cada producto se lleva sus tres precios a la tabla de tarifas. Nadie
     * pierde su precio en el camino.
     */
    private function sembrarLosTiposQueYaExistian(): void
    {
        $queso = DB::table('products')->where('item_code', config('huata.codigos.queso'))->first();
        $ahora = now();

        $definiciones = [
            [
                'name' => 'Proveedor de leche',
                'slug' => 'proveedor',
                'min_quantity' => 0,
                'price_per_unit' => $queso->price_provider ?? 18.00,
                'auto_role' => 'productor',
                'description' => 'Poblador del padrón que entrega leche. Se le reconoce solo.',
                'columna' => 'price_provider',
                'tipo_cliente' => 'proveedor',
            ],
            [
                'name' => 'Mayorista',
                'slug' => 'mayorista',
                'min_quantity' => 10,
                'price_per_unit' => $queso->price_wholesale ?? 19.00,
                'auto_role' => null,
                'description' => 'Comerciante, o cualquier compra de 10 unidades o más.',
                'columna' => 'price_wholesale',
                'tipo_cliente' => 'mayorista',
            ],
            [
                'name' => 'Cliente local',
                'slug' => 'local',
                'min_quantity' => 0,
                'price_per_unit' => $queso->price_local ?? 20.00,
                'auto_role' => null,
                'description' => 'Público general del distrito.',
                'columna' => 'price_local',
                'tipo_cliente' => 'local',
            ],
        ];

        foreach ($definiciones as $definicion) {
            $tipoId = DB::table('client_types')->insertGetId([
                'name' => $definicion['name'],
                'slug' => $definicion['slug'],
                'min_quantity' => $definicion['min_quantity'],
                'price_per_unit' => $definicion['price_per_unit'],
                'auto_role' => $definicion['auto_role'],
                'description' => $definicion['description'],
                'is_active' => true,
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ]);

            // La tarifa que cada producto ya tenía para este tipo.
            foreach (DB::table('products')->get() as $producto) {
                DB::table('product_client_type_prices')->insert([
                    'product_id' => $producto->id,
                    'client_type_id' => $tipoId,
                    'price_per_unit' => $producto->{$definicion['columna']},
                    'created_at' => $ahora,
                    'updated_at' => $ahora,
                ]);
            }

            DB::table('customers')
                ->where('type', $definicion['tipo_cliente'])
                ->update(['client_type_id' => $tipoId]);
        }
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropForeign(['client_type_id']);
            $table->dropColumn('client_type_id');
        });

        Schema::dropIfExists('product_client_type_prices');
        Schema::dropIfExists('client_types');
    }
};
