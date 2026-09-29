<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bitácora de operaciones subidas por la app móvil.
 *
 * Es el mecanismo de idempotencia: si el dispositivo reenvía una operación
 * (porque se cortó la señal justo después de aplicarla), el servidor devuelve
 * la respuesta guardada en lugar de volver a ejecutar el comando.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sync_operations', function (Blueprint $table) {
            $table->id();
            $table->uuid('client_uuid')->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('device_id', 100)->nullable();
            $table->string('command', 60);
            $table->json('payload');
            $table->json('result')->nullable();
            $table->string('status', 20)->default('aplicada'); // aplicada, rechazada
            $table->text('error_message')->nullable();
            $table->timestamp('applied_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_operations');
    }
};
