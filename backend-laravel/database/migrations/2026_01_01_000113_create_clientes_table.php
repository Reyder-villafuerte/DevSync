<?php

use App\Enums\TipoCliente;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clientes', function (Blueprint $table) {
            $table->idUuid();
            $table->string('nombre');
            $table->string('tipo_cliente')->comment('Determina la lista de precios aplicable');
            $table->string('documento_identidad', 20)->nullable()->comment('RUC o DNI');
            $table->string('direccion')->nullable();
            $table->string('telefono', 20)->nullable();
            $table->boolean('activo')->default(true);
            $table->columnasSincronizacion();

            $table->index('tipo_cliente');
        });

        DB::statement(
            'ALTER TABLE clientes ADD CONSTRAINT clientes_tipo_cliente_chk CHECK (tipo_cliente IN ('
            .collect(TipoCliente::cases())->map(fn ($t) => "'{$t->value}'")->implode(',')
            .'))'
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('clientes');
    }
};
