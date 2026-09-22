<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Un tipo de cliente puede reconocer varios roles, no uno solo.
 *
 * Con `auto_role` una tarifa de empleados habría necesitado un tipo por cada
 * puesto: uno para el acopiador, otro para el vendedor, otro para el inspector.
 * Ahora «Empleado» marca todos los roles del personal de una vez.
 *
 * El rol sigue siendo único en todo el sistema: si dos tipos reclamaran el
 * mismo rol, no habría forma de saber cuál cobra.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_type_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_type_id')->constrained('client_types')->cascadeOnDelete();
            $table->string('role', 30);
            $table->timestamps();

            $table->unique('role');
        });

        // Lo que ya estaba en la columna vieja se conserva tal cual.
        $ahora = now();

        foreach (DB::table('client_types')->whereNotNull('auto_role')->get() as $tipo) {
            DB::table('client_type_roles')->insert([
                'client_type_id' => $tipo->id,
                'role' => $tipo->auto_role,
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ]);
        }

        Schema::table('client_types', function (Blueprint $table) {
            $table->dropColumn('auto_role');
        });
    }

    public function down(): void
    {
        Schema::table('client_types', function (Blueprint $table) {
            $table->string('auto_role', 30)->nullable()->after('min_quantity');
        });

        foreach (DB::table('client_type_roles')->get() as $fila) {
            DB::table('client_types')->where('id', $fila->client_type_id)
                ->update(['auto_role' => $fila->role]);
        }

        Schema::dropIfExists('client_type_roles');
    }
};
