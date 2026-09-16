<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Columnas de soporte para la sincronización offline-first con la app móvil.
 *
 * - client_uuid: identificador generado en el dispositivo ANTES de tener conexión.
 *   Permite que un reenvío de la misma operación no duplique la fila (idempotencia).
 * - índice en updated_at: la bajada incremental (pull) filtra por updated_at > cursor.
 */
return new class extends Migration
{
    /** Tablas que la app móvil puede crear o modificar. */
    private array $tablasEscribibles = [
        'collection_routes',
        'collection_records',
        'plant_receptions',
        'cheese_productions',
        'customers',
        'sales',
        'daily_cash_closures',
        'lactoscan_analyses',
        'technical_visits',
        'zone_change_requests',
        'producer_settlements',
        'producer_deductions',
        'operational_expenses',
        'announcements',
        'system_prices',
    ];

    /** Tablas que el móvil solo lee, pero que igual viajan por deltas. */
    private array $tablasSoloLectura = [
        'users',
        'zones',
        'inventory_stocks',
    ];

    public function up(): void
    {
        foreach ($this->tablasEscribibles as $tabla) {
            Schema::table($tabla, function (Blueprint $table) use ($tabla) {
                $table->uuid('client_uuid')->nullable()->unique();
                $table->index('updated_at', "idx_{$tabla}_updated_at");
            });
        }

        foreach ($this->tablasSoloLectura as $tabla) {
            Schema::table($tabla, function (Blueprint $table) use ($tabla) {
                $table->index('updated_at', "idx_{$tabla}_updated_at");
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tablasEscribibles as $tabla) {
            Schema::table($tabla, function (Blueprint $table) use ($tabla) {
                $table->dropIndex("idx_{$tabla}_updated_at");
                $table->dropUnique([$tabla . '_client_uuid_unique']);
                $table->dropColumn('client_uuid');
            });
        }

        foreach ($this->tablasSoloLectura as $tabla) {
            Schema::table($tabla, function (Blueprint $table) use ($tabla) {
                $table->dropIndex("idx_{$tabla}_updated_at");
            });
        }
    }
};
