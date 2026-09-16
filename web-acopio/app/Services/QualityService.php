<?php

namespace App\Services;

use App\Models\Capacitacion;
use App\Models\Entrega;
use App\Models\ProblemaLeche;
use App\Models\Productor;
use App\Models\PruebaCalidad;
use App\Models\Sancion;
use App\Models\User;
use App\Notifications\MilkFlowNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class QualityService
{
    public function create(array $data, User $actor): PruebaCalidad
    {
        abort_unless($actor->hasPermission('quality.manage'), 403);

        return DB::transaction(function () use ($data, $actor) {
            $delivery = Entrega::lockForUpdate()->findOrFail($data['entrega_id']);
            $producer = Productor::lockForUpdate()->findOrFail($delivery->productor_id);
            if ($delivery->pruebaCalidad()->exists()) {
                throw ValidationException::withMessages(['entrega_id' => 'Esta entrega ya tiene una prueba de calidad.']);
            }
            [$start,$end] = BusinessWeek::bounds($delivery->fecha_hora);
            if ($producer->liquidaciones()->where('periodo_inicio', $start->toDateString())->exists()) {
                throw ValidationException::withMessages(['entrega_id' => 'El periodo ya está liquidado.']);
            }
            $water = (float) $data['agua_agregada_porcentaje'];
            $acid = (float) $data['ph'] < 6.5;
            $result = $acid || $water >= 5 ? 'RECHAZADA' : ($water > 0 ? 'OBSERVADA' : 'CONFORME');
            $test = PruebaCalidad::create(collect($data)->only(['entrega_id', 'ph', 'temperatura', 'agua_agregada_porcentaje', 'observacion'])->all() + ['resultado' => $result, 'fecha_hora' => now(), 'usuario_id' => $actor->id]);
            $before = $delivery->getAttributes();
            $delivery->update(['estado' => $result, 'updated_by' => $actor->id]);
            Audit::record('entrega.calidad_actualizada', $delivery, $before);
            if ($water > 0) {
                $repeat = Sancion::where('productor_id', $producer->id)->exists();
                Sancion::create(['productor_id' => $producer->id, 'prueba_calidad_id' => $test->id, 'tipo' => $water >= 5 ? 'EXPULSION' : ($repeat ? 'REINCIDENCIA' : 'ADVERTENCIA'), 'periodo_inicio' => $start->toDateString(), 'periodo_fin' => $end->toDateString(), 'tarifa' => Settings::get('tarifa_castigo', '0.65'), 'descuento' => $repeat ? Settings::get('descuento_reincidencia', '10') : 0]);
                if ($water >= 5 || $repeat) {
                    $old = $producer->getAttributes();
                    $producer->update(['activo' => false, 'expulsado_at' => now()]);
                    Audit::record('productor.expulsion_definitiva', $producer, $old);
                }
                ProblemaLeche::create(['entrega_id' => $delivery->id, 'tipo' => 'ADULTERACION', 'descripcion' => 'Lactoscan: '.$water.'% de agua agregada.', 'severidad' => $water >= 5 ? 'ALTA' : 'MEDIA', 'fecha' => now(), 'usuario_id' => $actor->id]);
            }
            if ($acid) {
                Capacitacion::create(['productor_id' => $producer->id, 'entrega_id' => $delivery->id, 'tema' => 'Buenas Prácticas de Ordeño', 'fecha' => today()->addDay(), 'usuario_id' => $actor->id]);
                ProblemaLeche::create(['entrega_id' => $delivery->id, 'tipo' => 'ACIDEZ', 'descripcion' => 'Leche acidificada: pH '.$data['ph'].'. Requiere capacitación BPO.', 'severidad' => 'ALTA', 'fecha' => now(), 'usuario_id' => $actor->id]);
            }
            Audit::record('calidad.registrada', $test);
            $producer->user?->notify(new MilkFlowNotification('Resultado de calidad: '.$result, 'Entrega '.$delivery->uuid.'. pH: '.$data['ph'].', agua agregada: '.$water.'%.'));

            return $test;
        });
    }
}
