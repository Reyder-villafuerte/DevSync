<?php

use App\Enums\RolUsuario;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Identidades del sistema. Tabla en español => se configura el
        // provider 'usuarios' en config/auth.php.
        Schema::create('usuarios', function (Blueprint $table) {
            $table->idUuid();
            $table->string('nombres');
            $table->string('apellidos');
            $table->string('dni', 8)->unique();
            $table->string('email')->nullable()->unique();
            $table->string('telefono', 20)->nullable();
            $table->string('password');
            // Rol único por usuario. Se guarda como string del enum RolUsuario;
            // la autorización fina se resuelve con Gates por rol.
            $table->string('rol');
            $table->boolean('activo')->default(true);
            $table->timestampTz('ultimo_acceso_en')->nullable();
            $table->rememberToken();
            $table->columnasSincronizacion();
        });

        DB::statement("ALTER TABLE usuarios ADD CONSTRAINT usuarios_dni_numerico_chk CHECK (dni ~ '^[0-9]{8}$')");
        DB::statement(
            'ALTER TABLE usuarios ADD CONSTRAINT usuarios_rol_valido_chk CHECK (rol IN ('
            .collect(RolUsuario::cases())->map(fn ($r) => "'{$r->value}'")->implode(',')
            .'))'
        );

        Schema::create('tokens_restablecimiento_password', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestampTz('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignUuid('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('tokens_restablecimiento_password');
        Schema::dropIfExists('usuarios');
    }
};
