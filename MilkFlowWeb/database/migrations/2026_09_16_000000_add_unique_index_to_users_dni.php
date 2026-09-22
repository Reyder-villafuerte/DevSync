<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * El DNI pasa a ser la credencial de acceso en web y en la app móvil,
 * así que debe identificar a un único usuario.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Los DNI repetidos se liberan (quedan en null) conservando al usuario
        // más antiguo; administración debe reasignarlos a mano.
        $repetidos = DB::table('users')
            ->select('dni')
            ->whereNotNull('dni')
            ->groupBy('dni')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('dni');

        foreach ($repetidos as $dni) {
            $conservado = DB::table('users')->where('dni', $dni)->min('id');

            DB::table('users')
                ->where('dni', $dni)
                ->where('id', '!=', $conservado)
                ->update(['dni' => null]);
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_dni_index');
            $table->unique('dni');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique('users_dni_unique');
            $table->index('dni');
        });
    }
};
