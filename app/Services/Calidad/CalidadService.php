<?php

namespace App\Services\Calidad;

use App\Exceptions\ReglaNegocioException;
use App\Models\LactoscanAnalysis;
use App\Models\TechnicalVisit;
use App\Models\User;
use App\Services\Acopio\JornadaOperativa;
use Illuminate\Support\Facades\DB;

/**
 * Control de calidad con Lactoscan y agenda de visitas técnicas.
 *
 * Un veredicto de acidez alta agenda automáticamente una visita técnica
 * (a dos días vista si el inspector no fija fecha).
 */
class CalidadService
{
    public const VEREDICTOS = ['conforme', 'acidez_alta', 'adulterada', 'sospechosa'];

    public function registrarAnalisis(User $inspector, array $datos, ?string $clientUuid = null): LactoscanAnalysis
    {
        $veredicto = $datos['verdict'] ?? 'conforme';

        if (! in_array($veredicto, self::VEREDICTOS, true)) {
            throw new ReglaNegocioException("Veredicto no válido: {$veredicto}.", 'verdict');
        }

        return DB::transaction(function () use ($inspector, $datos, $veredicto, $clientUuid) {
            $analisis = LactoscanAnalysis::create([
                'client_uuid' => $clientUuid,
                'producer_id' => $datos['producer_id'],
                'inspector_id' => $inspector->id,
                'analysis_date' => $datos['analysis_date'] ?? app(JornadaOperativa::class)->fecha(),
                'fat_percentage' => $datos['fat_percentage'] ?? null,
                'snf_percentage' => $datos['snf_percentage'] ?? null,
                'density' => $datos['density'] ?? null,
                'protein_percentage' => $datos['protein_percentage'] ?? null,
                'water_addition_percentage' => $datos['water_addition_percentage'] ?? null,
                'temperature' => $datos['temperature'] ?? null,
                'ph_or_acidity' => $datos['ph_or_acidity'] ?? null,
                'verdict' => $veredicto,
                'notes' => $datos['notes'] ?? null,
            ]);

            if (! empty($datos['schedule_visit']) || $veredicto === 'acidez_alta') {
                $ph = $datos['ph_or_acidity'] ?? 'anómala';

                TechnicalVisit::create([
                    'lactoscan_analysis_id' => $analisis->id,
                    'producer_id' => $datos['producer_id'],
                    'inspector_id' => $inspector->id,
                    'scheduled_date' => ! empty($datos['scheduled_date'])
                        ? $datos['scheduled_date']
                        : date('Y-m-d', strtotime('+2 days')),
                    'scheduled_time' => $datos['scheduled_time'] ?? '09:00:00',
                    'status' => 'programada',
                    'reason' => ! empty($datos['visit_reason'])
                        ? $datos['visit_reason']
                        : "Visita técnica programada por detección de acidez alta ({$ph}) en prueba Lactoscan.",
                ]);
            }

            return $analisis;
        });
    }

    public function agendarVisita(
        LactoscanAnalysis $analisis,
        User $inspector,
        string $fecha,
        ?string $hora,
        string $motivo,
        ?string $clientUuid = null
    ): TechnicalVisit {
        return TechnicalVisit::create([
            'client_uuid' => $clientUuid,
            'lactoscan_analysis_id' => $analisis->id,
            'producer_id' => $analisis->producer_id,
            'inspector_id' => $inspector->id,
            'scheduled_date' => $fecha,
            'scheduled_time' => $hora ?: '09:00:00',
            'status' => 'programada',
            'reason' => $motivo,
        ]);
    }

    public function completarVisita(TechnicalVisit $visita, string $informe): TechnicalVisit
    {
        if (trim($informe) === '') {
            throw new ReglaNegocioException('El informe de resolución es obligatorio.', 'resolution_report');
        }

        $visita->update([
            'status' => 'realizada',
            'resolution_report' => $informe,
        ]);

        return $visita;
    }
}
