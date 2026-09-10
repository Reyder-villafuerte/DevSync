<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Un dispositivo físico (tablet/celular) vinculado a un usuario de campo.
        // Es la unidad a la que se le reservan rangos de correlativo de
        // facturación y contra la que se registra el cursor de sincronización.
        Schema::create('dispositivos', function (Blueprint $table) {
            $table->idUuid();
            $table->fkUuid('usuario_id', 'usuarios');
            $table->string('identificador')->unique()->comment('Android ID / UUID de instalación');
            $table->string('nombre')->nullable();
            $table->string('plataforma', 20)->default('android');
            $table->timestampTz('ultima_sincronizacion_en')->nullable();
            $table->boolean('activo')->default(true);
            $table->columnasSincronizacion();

            $table->index('usuario_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dispositivos');
    }
};
