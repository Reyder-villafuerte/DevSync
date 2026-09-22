<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * La categoría se queda con lo que la planta realmente usa: nombre,
 * descripción y si está activa. Dejó de dividirse entre insumo y producto:
 * una misma categoría puede agrupar cualquier cosa del almacén.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_categories', function (Blueprint $table) {
            $table->dropIndex(['scope', 'is_active']);
            $table->dropColumn('scope');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('inventory_categories', function (Blueprint $table) {
            $table->dropIndex(['is_active']);
            $table->string('scope', 20)->default('insumo')->after('slug');
            $table->index(['scope', 'is_active']);
        });
    }
};
