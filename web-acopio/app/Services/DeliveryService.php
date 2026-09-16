<?php

namespace App\Services;

use App\Models\CierreRuta;
use App\Models\Entrega;
use App\Models\Productor;
use App\Models\Ruta;
use App\Models\SyncRecord;
use App\Models\User;
use App\Notifications\MilkFlowNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class DeliveryService
{
    public function create(array $data, User $actor): Entrega
    {
        Gate::forUser($actor)->authorize('create', Entrega::class);

        return DB::transaction(function () use ($data, $actor) {
            $existing = Entrega::where('uuid', $data['uuid'])->first();
            if ($existing) {
                abort_unless($existing->created_by === $actor->id, 403);
                if ($existing->productor_id != (int) $data['productor_id'] || (float) $existing->litros != (float) $data['litros']) {
                    throw ValidationException::withMessages(['uuid' => 'El UUID ya identifica otra entrega.']);
                }

return $existing;
            }
            $producer = Productor::lockForUpdate()->findOrFail($data['productor_id']);
            if (! $producer->activo || $producer->expulsado_at) {
                throw ValidationException::withMessages(['productor_id' => 'Solo los productores activos pueden registrar nuevas entregas.']);
            }
            $route = null;
            $collector = null;
            if ($data['tipo'] === 'RECOGIDA') {
                $route = Ruta::lockForUpdate()->findOrFail($data['ruta_id']);
                $collector = $route->acopiador;
                if (! $route->activo || ! $collector->activo || (! $actor->hasRole('admin') && $collector->user_id !== $actor->id) || ! $route->sectores()->where('sectores.id', $producer->sector_id)->exists()) {
                    throw ValidationException::withMessages(['ruta_id' => 'La ruta no está activa, no te pertenece o no incluye el sector del productor.']);
                }
                if (CierreRuta::where('ruta_id', $route->id)->whereDate('fecha', today())->exists()) {
                    throw ValidationException::withMessages(['ruta_id' => 'La ruta ya fue cerrada hoy.']);
                }
            } elseif (! $actor->hasRole('admin')) {
                abort(403);
            }
            $delivery = Entrega::create(['uuid' => $data['uuid'], 'productor_id' => $producer->id, 'acopiador_id' => $collector?->id, 'ruta_id' => $route?->id, 'sector_id' => $producer->sector_id, 'zona_id' => $producer->zona_id, 'tipo' => $data['tipo'], 'litros' => $data['litros'], 'fecha_hora' => now(), 'estado' => 'PENDIENTE', 'observacion' => $data['observacion'] ?? null, 'sync_status' => 'ENVIADO', 'created_by' => $actor->id, 'updated_by' => $actor->id]);
            SyncRecord::create(['uuid' => $delivery->uuid, 'user_id' => $actor->id, 'entrega_id' => $delivery->id, 'estado' => 'ENVIADO']);
            Audit::record('entrega.creada', $delivery);
            $producer->user?->notify(new MilkFlowNotification('Entrega registrada', $delivery->litros.' litros registrados el '.now()->format('d/m/Y H:i').'.'));

            return $delivery;
        });
    }
}
