<?php

use App\Enums\TipoMovimientoStock;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // LIBRO DE EVENTOS de stock (requisito técnico 3). El stock NUNCA es
        // una columna contador: es la suma de estos movimientos. Cada fila es
        // inmutable (append-only); una corrección es otro movimiento de ajuste.
        Schema::create('movimientos_stock', function (Blueprint $table) {
            $table->idUuid();
            $table->fkUuid('producto_id', 'productos');
            $table->string('tipo_movimiento')->comment('Enum TipoMovimientoStock');
            // Cantidad CON SIGNO: + ingreso, - egreso. Nunca cero.
            $table->decimal('cantidad', 12, 2);
            // Referencia polimórfica ligera al origen (sesión, venta, ajuste...).
            $table->string('origen_tipo')->nullable();
            $table->uuid('origen_id')->nullable();
            $table->fkUuid('registrado_por', 'usuarios');
            $table->timestampTz('ocurrido_en');
            $table->string('motivo')->nullable();
            $table->columnasSincronizacion();

            $table->index(['producto_id', 'ocurrido_en']);
            $table->index(['origen_tipo', 'origen_id']);
        });

        DB::statement('ALTER TABLE movimientos_stock ADD CONSTRAINT ms_cantidad_no_cero_chk CHECK (cantidad <> 0)');
        DB::statement(
            'ALTER TABLE movimientos_stock ADD CONSTRAINT ms_tipo_chk CHECK (tipo_movimiento IN ('
            .collect(TipoMovimientoStock::cases())->map(fn ($t) => "'{$t->value}'")->implode(',')
            .'))'
        );
        // Coherencia signo <-> tipo: ingresos positivos, egresos negativos.
        DB::statement("ALTER TABLE movimientos_stock ADD CONSTRAINT ms_signo_coherente_chk CHECK ((tipo_movimiento IN ('produccion_ingreso','ajuste_positivo') AND cantidad > 0) OR (tipo_movimiento IN ('venta_egreso','ajuste_negativo','merma') AND cantidad < 0))");
    }

    public function down(): void
    {
        Schema::dropIfExists('movimientos_stock');
    }
};
