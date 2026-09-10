<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Código corto y estable para el ámbito de sincronización
        // (?ambito=ruta:01 / zona:norte). Se prefiere un código legible al UUID
        // porque viaja en la URL y en la config de la app móvil.
        Schema::table('rutas', function (Blueprint $table) {
            $table->string('codigo', 10)->nullable()->unique()->after('nombre');
        });
        Schema::table('zonas', function (Blueprint $table) {
            $table->string('codigo', 20)->nullable()->unique()->after('nombre');
        });
    }

    public function down(): void
    {
        Schema::table('rutas', fn (Blueprint $t) => $t->dropColumn('codigo'));
        Schema::table('zonas', fn (Blueprint $t) => $t->dropColumn('codigo'));
    }
};
