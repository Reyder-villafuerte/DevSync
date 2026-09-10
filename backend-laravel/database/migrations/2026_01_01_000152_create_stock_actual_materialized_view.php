<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Vista materializada del stock actual = agregación del libro de
        // eventos (requisito técnico 3). Se refresca CONCURRENTLY tras cada
        // movimiento (StockService) y por schedule cada 15 min como respaldo.
        DB::statement(<<<'SQL'
            CREATE MATERIALIZED VIEW stock_actual AS
            SELECT
                p.id                                   AS producto_id,
                p.nombre                               AS producto_nombre,
                COALESCE(SUM(m.cantidad), 0)::numeric(14,2) AS cantidad_actual,
                MAX(m.ocurrido_en)                      AS ultimo_movimiento_en,
                COUNT(m.id)                             AS total_movimientos
            FROM productos p
            LEFT JOIN movimientos_stock m
                   ON m.producto_id = p.id AND m.deleted = false
            WHERE p.deleted = false
            GROUP BY p.id, p.nombre
            WITH NO DATA;
        SQL);

        // Índice único requerido por REFRESH ... CONCURRENTLY.
        DB::statement('CREATE UNIQUE INDEX stock_actual_producto_id_uidx ON stock_actual (producto_id)');

        DB::statement('REFRESH MATERIALIZED VIEW stock_actual');
    }

    public function down(): void
    {
        DB::statement('DROP MATERIALIZED VIEW IF EXISTS stock_actual');
    }
};
