<?php

namespace App\Providers;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->registrarMacrosDeEsquema();

        // El panel usa CSS propio (sin Tailwind); el paginador Bootstrap-4
        // genera <ul class="pagination"> que sí estilamos en public/css/panel.css.
        Paginator::useBootstrapFour();
    }

    /**
     * Macros de Blueprint para no repetir en cada migración las columnas del
     * contrato de sincronización. Decisión: un macro central en vez de una
     * clase base de migración, porque las migraciones de Laravel 11 son
     * anónimas y no admiten herencia limpia.
     */
    private function registrarMacrosDeEsquema(): void
    {
        // Clave primaria UUID (generada en cliente).
        Blueprint::macro('idUuid', function (string $columna = 'id') {
            /** @var Blueprint $this */
            return $this->uuid($columna)->primary();
        });

        // Columnas obligatorias de toda tabla sincronizable + índice de cursor.
        Blueprint::macro('columnasSincronizacion', function () {
            /** @var Blueprint $this */
            $this->unsignedBigInteger('version')->default(1)
                ->comment('Entero monótono; +1 en cada escritura. Detector de conflictos.');
            $this->boolean('deleted')->default(false)
                ->comment('Borrado lógico; nunca DELETE físico en tablas sincronizables.');
            $this->timestampsTz();

            // Índice compuesto que sirve al cursor de bajada (updated_at, id).
            $this->index(['updated_at', 'id'], "{$this->getTable()}_cursor_sync_idx");
        });

        // FK a usuarios (tipo uuid) con nombre corto configurable.
        Blueprint::macro('fkUuid', function (string $columna, string $tabla, bool $nullable = false) {
            /** @var Blueprint $this */
            $col = $this->uuid($columna);
            if ($nullable) {
                $col->nullable();
            }
            $this->foreign($columna)->references('id')->on($tabla)->restrictOnDelete();

            return $col;
        });
    }
}
