<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * El poblador de Huata que entrega leche también es un proveedor, pero no se le
 * compra igual que a la distribuidora de envases.
 *
 * Su leche entra al almacén por el caudalímetro de planta y se le paga por
 * liquidación semanal, con su penalidad por agua y sus deducciones. Si además
 * se pudiera registrar esa leche como una compra, entraría al stock dos veces y
 * se pagaría dos veces.
 *
 * Por eso van dos cosas juntas:
 *
 * 1. `suppliers.linked_user_id`: un proveedor puede SER un productor del padrón,
 *    igual que ya lo hace `customers.linked_user_id`. Así no se duplica su
 *    identidad cuando se le compra fruta o se le paga leche.
 * 2. `supplies.entry_mode`: cada insumo declara por dónde entra al almacén. La
 *    leche entra por «acopio» y queda fuera de la pantalla de compras; todo lo
 *    demás entra por «compra».
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->foreignId('linked_user_id')->nullable()->after('document')
                ->constrained('users')->nullOnDelete();
        });

        Schema::table('supplies', function (Blueprint $table) {
            $table->string('entry_mode', 20)->default('compra')->after('unit_cost');
            $table->index('entry_mode');
        });

        DB::table('supplies')
            ->where('item_code', config('huata.codigos.leche'))
            ->update(['entry_mode' => 'acopio']);
    }

    public function down(): void
    {
        Schema::table('supplies', function (Blueprint $table) {
            $table->dropIndex(['entry_mode']);
            $table->dropColumn('entry_mode');
        });

        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropForeign(['linked_user_id']);
            $table->dropColumn('linked_user_id');
        });
    }
};
