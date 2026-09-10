<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Perfil de acopiador: vincula un usuario con rol=acopiador a la ruta
        // que recorre. Tabla aparte (y no columna en usuarios) porque el resto
        // de roles no tienen ruta y para permitir historial de reasignación.
        Schema::create('acopiadores', function (Blueprint $table) {
            $table->idUuid();
            $table->fkUuid('usuario_id', 'usuarios');
            $table->fkUuid('ruta_id', 'rutas');
            $table->date('vigente_desde');
            $table->date('vigente_hasta')->nullable();
            $table->columnasSincronizacion();

            $table->index('usuario_id');
            $table->index('ruta_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('acopiadores');
    }
};
