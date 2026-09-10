<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Acuse de recibo del pop-up obligatorio, por productor.
        Schema::create('avisos_vistos', function (Blueprint $table) {
            $table->idUuid();
            $table->fkUuid('aviso_id', 'avisos');
            $table->fkUuid('productor_id', 'productores');
            $table->timestampTz('visto_en');
            $table->columnasSincronizacion();

            $table->unique(['aviso_id', 'productor_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('avisos_vistos');
    }
};
