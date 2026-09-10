<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Avisos con imagen y fecha de publicación. Si obligatorio=true, la app
        // del productor lo muestra como pop-up bloqueante hasta que se registra
        // el acuse en avisos_vistos.
        Schema::create('avisos', function (Blueprint $table) {
            $table->idUuid();
            $table->string('titulo');
            $table->text('contenido');
            $table->string('imagen_url')->nullable();
            $table->timestampTz('fecha_publicacion');
            $table->timestampTz('fecha_expiracion')->nullable();
            $table->boolean('obligatorio')->default(false);
            $table->boolean('publicado')->default(false);
            $table->fkUuid('creado_por', 'usuarios');
            $table->columnasSincronizacion();

            $table->index(['publicado', 'fecha_publicacion']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('avisos');
    }
};
