<?php

namespace App\Services\Jornada;

use App\Exceptions\ReglaNegocioException;
use App\Models\Dispositivo;
use App\Models\RutaAcopio;
use App\Models\Usuario;
use App\Services\Sync\ResultadoPush;
use App\Services\Sync\SyncService;
use Illuminate\Support\Facades\DB;

/**
 * Use Case: cierre de la jornada (ruta de acopio) del día.
 *
 * El acopiador termina el recorrido sin cobertura; al llegar a un punto con
 * señal sube TODO el lote del día (registros por productor y controles de
 * calidad tomados) y cierra la ruta.
 *
 * Reutiliza el motor de subida (SyncService::push) para el lote — misma
 * idempotencia y mismas reglas de conflicto — y añade el cambio de estado de
 * la cabecera en su propia transacción.
 */
class CierreJornadaService
{
    public function __construct(private readonly SyncService $sync) {}

    /**
     * @param  array{registros?: array, controlesCalidad?: array}  $lote
     * @return array{resultado: ResultadoPush, jornada: RutaAcopio}
     */
    public function cerrar(RutaAcopio $jornada, array $lote, Usuario $usuario, Dispositivo $dispositivo): array
    {
        if (in_array($jornada->estado, ['conciliada'], true)) {
            throw new ReglaNegocioException("La jornada {$jornada->id} ya fue conciliada en planta.", 'JORNADA_YA_CONCILIADA');
        }

        // Construir las operaciones en el formato de push, forzando el vínculo
        // a esta jornada (el cliente no puede reasignar filas a otra ruta).
        $operaciones = [];
        foreach ($lote['registros'] ?? [] as $fila) {
            $operaciones[] = $this->operacion('registros_acopio', $fila, ['rutaAcopioId' => $jornada->id]);
        }
        foreach ($lote['controlesCalidad'] ?? [] as $fila) {
            $operaciones[] = $this->operacion('controles_calidad', $fila, ['rutaAcopioId' => $jornada->id]);
        }

        $resultado = $this->sync->push($usuario, $operaciones, $dispositivo);

        // Estado de la cabecera: en su propia transacción. Solo se cierra si el
        // lote no dejó conflictos que impidan una conciliación fiable.
        DB::transaction(function () use ($jornada) {
            $litros = (float) $jornada->registros()->where('deleted', false)->sum('litros');

            $jornada->forceFill([
                'litros_declarados' => $litros,
                'hora_cierre' => now(),
                'estado' => 'cerrada',
            ])->save();
        });

        return ['resultado' => $resultado, 'jornada' => $jornada->fresh(['registros'])];
    }

    private function operacion(string $entidad, array $fila, array $forzar): array
    {
        return [
            'entidad' => $entidad,
            'id' => $fila['id'] ?? null,
            'versionBase' => $fila['versionBase'] ?? 0,
            'atributos' => array_merge($fila['atributos'] ?? $fila, $forzar),
        ];
    }
}
