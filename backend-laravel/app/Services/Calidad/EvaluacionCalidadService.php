<?php

namespace App\Services\Calidad;

use App\Enums\DictamenCalidad;
use App\Enums\EstadoProductor;
use App\Enums\TipoSancion;
use App\Models\Capacitacion;
use App\Models\ControlCalidad;
use App\Models\PrecioCompraLeche;
use App\Models\Sancion;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Use Case: evaluación del dictamen de calidad (RN-05 adulteración con agua y
 * RN-06 acidez).
 *
 * El dictamen se PERSISTE junto a las mediciones (requisito técnico 5). Este
 * servicio es la única autoridad que lo escribe; al leer un control nadie
 * recalcula. Si el móvil ya trajo un dictamen tentativo, aquí se revalida y el
 * servidor manda.
 *
 * Toda la evaluación corre en una transacción: el dictamen, la sanción y el
 * cambio de estado del productor son un solo hecho atómico.
 */
class EvaluacionCalidadService
{
    public function evaluar(ControlCalidad $control): ControlCalidad
    {
        return DB::transaction(function () use ($control) {
            $control->loadMissing('productor');
            $productor = $control->productor;

            $agua = $control->agua_anadida_porcentaje !== null
                ? (float) $control->agua_anadida_porcentaje
                : 0.0;
            $ph = $control->ph !== null ? (float) $control->ph : null;

            $umbralExpulsion = (float) config('milkflow.agua.umbral_expulsion_pct');
            $phMinimo = (float) config('milkflow.acidez.ph_minimo');

            // Reincidencia = ya existe OTRA sanción de agua no anulada. Se
            // excluye la de este mismo control para que re-evaluar (p. ej. al
            // reenviar la inspección por sincronización) sea idempotente y no
            // "escale" el dictamen contra sí mismo.
            $reincide = $productor->sanciones()
                ->where('estado', '!=', 'anulada')
                ->whereIn('tipo', ['advertencia_descuento', 'descuento_y_retiro', 'expulsion_tarifa_minima'])
                ->where('control_calidad_id', '!=', $control->id)
                ->exists();

            $dictamen = DictamenCalidad::APROBADO;
            $detalle = 'Dentro de parámetros.';
            $rechazaLote = false;
            $umbralAplicado = null;

            // ---- RN-05: adulteración con agua (evaluada primero: es la más grave) ----
            if ($agua >= $umbralExpulsion) {
                $dictamen = DictamenCalidad::EXPULSION_AGUA;
                $rechazaLote = true;
                $umbralAplicado = "RN-05 >= {$umbralExpulsion}% (expulsión)";
                $detalle = "Agua añadida {$agua}% ≥ {$umbralExpulsion}%: expulsión inmediata y pago degradado a tarifa mínima.";
            } elseif ($agua > 0) {
                if ($reincide) {
                    $dictamen = DictamenCalidad::DESCUENTO_RETIRO_AGUA;
                    $umbralAplicado = "RN-05 < {$umbralExpulsion}% (reincidencia)";
                    $detalle = "Agua añadida {$agua}% con reincidencia: descuento en liquidación y retiro definitivo del padrón.";
                } else {
                    $dictamen = DictamenCalidad::ADVERTENCIA_AGUA;
                    $umbralAplicado = "RN-05 < {$umbralExpulsion}% (primera infracción)";
                    $detalle = "Agua añadida {$agua}%: advertencia y descuento en la liquidación de esta semana.";
                }
            }

            // ---- RN-06: acidez ----
            // Independiente de RN-05, pero si ya hay expulsión por agua el
            // productor sale igual; la capacitación pierde sentido.
            $derivaCapacitacion = false;
            if ($ph !== null && $ph < $phMinimo) {
                $rechazaLote = true;
                if ($dictamen === DictamenCalidad::APROBADO) {
                    $dictamen = DictamenCalidad::RECHAZADO_ACIDEZ;
                    $umbralAplicado = "RN-06 pH < {$phMinimo}";
                    $detalle = "pH {$ph} < {$phMinimo}: lote rechazado y derivación a capacitación obligatoria en Buenas Prácticas de Ordeño (sin expulsión).";
                }
                $derivaCapacitacion = $dictamen !== DictamenCalidad::EXPULSION_AGUA;
            }

            // ---- Persistir el dictamen sobre el mismo control ----
            $control->forceFill([
                'dictamen' => $dictamen->value,
                'dictamen_detalle' => $detalle,
                'rechaza_lote' => $rechazaLote,
                'es_reincidencia' => $reincide && $agua > 0,
                'umbral_agua_aplicado' => $umbralAplicado,
            ])->save();

            // ---- Consecuencias ----
            $this->generarSancion($control, $dictamen, $reincide);

            if ($derivaCapacitacion) {
                Capacitacion::firstOrCreate(
                    ['control_calidad_id' => $control->id],
                    [
                        'productor_id' => $productor->id,
                        'motivo' => 'Buenas Prácticas de Ordeño (RN-06)',
                        'estado' => 'pendiente',
                    ],
                );
            }

            $this->actualizarEstadoProductor($control, $dictamen);

            return $control->fresh(['productor', 'sancion', 'capacitacion']);
        });
    }

    private function generarSancion(ControlCalidad $control, DictamenCalidad $dictamen, bool $reincide): ?Sancion
    {
        [$tipo, $extra] = match ($dictamen) {
            DictamenCalidad::ADVERTENCIA_AGUA => [
                TipoSancion::ADVERTENCIA_DESCUENTO,
                ['porcentaje_descuento' => config('milkflow.agua.descuento_leve_pct')],
            ],
            DictamenCalidad::DESCUENTO_RETIRO_AGUA => [
                TipoSancion::DESCUENTO_Y_RETIRO,
                [
                    'porcentaje_descuento' => config('milkflow.agua.descuento_reincidente_pct'),
                    'retira_del_padron' => true,
                ],
            ],
            DictamenCalidad::EXPULSION_AGUA => [
                TipoSancion::EXPULSION_TARIFA_MINIMA,
                [
                    'expulsa' => true,
                    // Snapshot de la tarifa mínima vigente hoy (S/ 0.60-0.70).
                    'tarifa_degradada_litro' => optional(
                        PrecioCompraLeche::query()->vigenteEn(now()->toDateString())->first()
                    )->precio_litro_minimo,
                ],
            ],
            default => [null, []],
        };

        if ($tipo === null) {
            return null;
        }

        return Sancion::updateOrCreate(
            ['control_calidad_id' => $control->id],
            array_merge([
                'productor_id' => $control->productor_id,
                'tipo' => $tipo->value,
                'estado' => 'vigente',
                'aplicada_en_liquidacion' => false,
                'detalle' => $control->dictamen_detalle,
            ], $extra),
        );
    }

    private function actualizarEstadoProductor(ControlCalidad $control, DictamenCalidad $dictamen): void
    {
        $productor = $control->productor;
        $nuevoEstado = match ($dictamen) {
            DictamenCalidad::EXPULSION_AGUA => EstadoProductor::EXPULSADO,
            DictamenCalidad::DESCUENTO_RETIRO_AGUA => EstadoProductor::RETIRADO,
            default => null,
        };

        if ($nuevoEstado === null) {
            return;
        }

        $productor->forceFill([
            'estado' => $nuevoEstado->value,
            'fecha_baja' => Carbon::now()->toDateString(),
            'motivo_baja' => "RN-05: {$control->dictamen_detalle}",
        ])->save();
    }
}
