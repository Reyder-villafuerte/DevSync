<?php

use App\Enums\EstadoProductor;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('productores', function (Blueprint $table) {
            $table->idUuid();
            // Un productor puede tener acceso a la app (rol=productor) o no.
            $table->fkUuid('usuario_id', 'usuarios', nullable: true);
            $table->string('codigo_padron')->unique();
            $table->string('nombres');
            $table->string('apellidos');
            $table->string('dni', 8)->unique();
            $table->fkUuid('zona_id', 'zonas');
            $table->string('telefono', 20)->nullable();
            $table->string('estado')->default(EstadoProductor::ACTIVO->value);
            $table->date('fecha_ingreso');
            $table->date('fecha_baja')->nullable();
            $table->string('motivo_baja')->nullable();
            $table->columnasSincronizacion();

            $table->index('zona_id');
            $table->index('estado');
        });

        DB::statement("ALTER TABLE productores ADD CONSTRAINT productores_dni_numerico_chk CHECK (dni ~ '^[0-9]{8}$')");
        DB::statement(
            'ALTER TABLE productores ADD CONSTRAINT productores_estado_valido_chk CHECK (estado IN ('
            .collect(EstadoProductor::cases())->map(fn ($e) => "'{$e->value}'")->implode(',')
            .'))'
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('productores');
    }
};
