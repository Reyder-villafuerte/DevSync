<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // MySQL no ofrece vistas materializadas. Esta vista normal conserva el
        // mismo contrato de lectura y siempre refleja el libro de eventos.
        DB::statement(<<<'SQL'
            CREATE OR REPLACE VIEW stock_actual AS
            SELECT
                p.id                                   AS producto_id,
                p.nombre                               AS producto_nombre,
                CAST(COALESCE(SUM(m.cantidad), 0) AS DECIMAL(14,2)) AS cantidad_actual,
                MAX(m.ocurrido_en)                      AS ultimo_movimiento_en,
                COUNT(m.id)                             AS total_movimientos
            FROM productos p
            LEFT JOIN movimientos_stock m
                   ON m.producto_id = p.id AND m.deleted = 0
            WHERE p.deleted = 0
            GROUP BY p.id, p.nombre;
        SQL);
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS stock_actual');
    }
};
