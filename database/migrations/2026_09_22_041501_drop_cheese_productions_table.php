<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * La quesería deja de ser un módulo aparte.
 *
 * `cheese_productions` era de cuando la planta solo sabía hacer queso y cada
 * molde se llevaba 10 L fijos. Ahora eso es un lote de producción más: el
 * producto trae su receta, consume lo que diga y entrega lo que salga. Mantener
 * las dos puertas dejaba el almacén con dos historias distintas de lo mismo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('cheese_productions');
    }

    public function down(): void
    {
        Schema::create('cheese_productions', function (Blueprint $table) {
            $table->id();
            $table->uuid('client_uuid')->nullable()->unique();
            $table->date('production_date');
            $table->foreignId('supervisor_id')->constrained('users')->cascadeOnDelete();
            $table->integer('cheese_molds_produced');
            $table->decimal('milk_liters_used', 10, 2);
            $table->string('batch_number', 50)->nullable();
            $table->string('status', 30)->default('completado');
            $table->timestamps();
        });
    }
};
