<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('system_prices', function (Blueprint $table) {
            $table->id();
            $table->string('season_name')->default('Temporada Huata 2026');
            $table->decimal('price_milk_base', 6, 2)->default(1.40); // S/ 1.40 por litro normal
            $table->decimal('price_milk_water_penalty_low', 6, 2)->default(1.20); // S/ 1.20 si agua <= 5%
            $table->decimal('price_milk_water_penalty_high', 6, 2)->default(0.90); // S/ 0.90 si agua > 5% (Expulsión)
            $table->decimal('price_cheese_provider', 6, 2)->default(18.00); // Tarifa proveedor queso
            $table->decimal('price_cheese_wholesale', 6, 2)->default(19.00); // Tarifa mayorista queso
            $table->decimal('price_cheese_local', 6, 2)->default(20.00); // Tarifa cliente local queso
            $table->boolean('is_active')->default(true);
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_prices');
    }
};
