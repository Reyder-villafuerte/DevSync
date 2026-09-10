<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Bitácora de cada intercambio de sincronización. No es sincronizable
        // (vive solo en el servidor); sirve para auditar conflictos y depurar
        // el protocolo por cursor.
        Schema::create('sync_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('dispositivo_id')->nullable()->constrained('dispositivos')->nullOnDelete();
            $table->foreignUuid('usuario_id')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->string('direccion', 10)->comment('bajada | subida');
            $table->string('entidad');
            $table->unsignedInteger('recibidos')->default(0);
            $table->unsignedInteger('aceptados')->default(0);
            $table->unsignedInteger('conflictos')->default(0);
            $table->string('cursor_desde')->nullable();
            $table->string('cursor_hasta')->nullable();
            $table->timestampTz('ejecutado_en');
            $table->timestampsTz();

            $table->index(['dispositivo_id', 'entidad', 'ejecutado_en']);
        });

        DB::statement("ALTER TABLE sync_logs ADD CONSTRAINT sync_logs_direccion_chk CHECK (direccion IN ('bajada','subida'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_logs');
    }
};
