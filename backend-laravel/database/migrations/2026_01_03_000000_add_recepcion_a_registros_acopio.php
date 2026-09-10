<?php

use App\Enums\EstadoRecepcion;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Verificación de recepción POR ENTREGA. Cuando el acopiador cierra la
        // ruta, el jefe de producción ve cada entrega declarada y confirma
        // cuántos litros recibió realmente. Si el caudalímetro/medidor de tina
        // da menos, la entrega queda 'faltante' con el detalle "faltó X L".
        //
        // Columnas de servidor: NO están en $fillable de RegistroAcopio, así el
        // móvil nunca las escribe. Solo VerificacionRecepcionService (planta)
        // las toca, y viajan al móvil por la bajada de sync (dirección `ambas`).
        Schema::table('registros_acopio', function (Blueprint $table) {
            $table->string('estado_recepcion')->default(EstadoRecepcion::PENDIENTE->value)
                ->comment('pendiente | conforme | faltante | excedente (persistido)');
            $table->decimal('litros_recibidos', 8, 2)->nullable()
                ->comment('Litros que el jefe de producción confirma haber recibido');
            $table->decimal('litros_faltantes', 8, 2)->default(0)
                ->comment('max(0, litros - litros_recibidos); persistido');
            $table->string('recepcion_observacion')->nullable();
            $table->timestampTz('recepcion_confirmada_en')->nullable();
            $table->uuid('recepcion_confirmada_por')->nullable();

            $table->foreign('recepcion_confirmada_por')->references('id')->on('usuarios')->nullOnDelete();
        });

        DB::statement(
            'ALTER TABLE registros_acopio ADD CONSTRAINT registros_acopio_estado_recepcion_chk CHECK (estado_recepcion IN ('
            .collect(EstadoRecepcion::cases())->map(fn ($e) => "'{$e->value}'")->implode(',')
            .'))'
        );
        DB::statement('ALTER TABLE registros_acopio ADD CONSTRAINT registros_acopio_recibidos_no_neg_chk CHECK (litros_recibidos IS NULL OR litros_recibidos >= 0)');
        DB::statement('ALTER TABLE registros_acopio ADD CONSTRAINT registros_acopio_faltantes_no_neg_chk CHECK (litros_faltantes >= 0)');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE registros_acopio DROP CONSTRAINT IF EXISTS registros_acopio_estado_recepcion_chk');
        DB::statement('ALTER TABLE registros_acopio DROP CONSTRAINT IF EXISTS registros_acopio_recibidos_no_neg_chk');
        DB::statement('ALTER TABLE registros_acopio DROP CONSTRAINT IF EXISTS registros_acopio_faltantes_no_neg_chk');

        Schema::table('registros_acopio', function (Blueprint $table) {
            $table->dropForeign(['recepcion_confirmada_por']);
            $table->dropColumn([
                'estado_recepcion', 'litros_recibidos', 'litros_faltantes',
                'recepcion_observacion', 'recepcion_confirmada_en', 'recepcion_confirmada_por',
            ]);
        });
    }
};
