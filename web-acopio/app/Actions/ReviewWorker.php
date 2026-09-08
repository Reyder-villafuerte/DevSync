<?php

namespace App\Actions;

use App\Models\Acopiador;
use App\Models\Productor;
use App\Models\Role;
use App\Models\User;
use App\Notifications\MilkFlowNotification;
use App\Services\Audit;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ReviewWorker
{
    public function execute(User $worker, string $decision, ?int $roleId, ?string $reason): User
    {
        Gate::authorize('review', $worker);

        return DB::transaction(function () use ($worker, $decision, $roleId, $reason) {
            $worker = User::lockForUpdate()->findOrFail($worker->id);
            $before = $worker->getAttributes();
            if ($worker->status !== 'PENDIENTE') {
                throw ValidationException::withMessages(['estado' => 'Esta solicitud ya fue revisada.']);
            }
            if ($decision === 'aprobar') {
                $role = Role::findOrFail($roleId);
                $worker->roles()->sync([$role->id]);
                $worker->status = 'ACTIVO';
                $worker->approved_at = now();
                $worker->approved_by = auth()->id();
                if ($role->slug === 'acopiador') {
                    Acopiador::firstOrCreate(['user_id' => $worker->id], ['nombre' => $worker->name, 'telefono' => $worker->telefono]);
                }
                if ($role->slug === 'productor') {
                    Productor::where('documento', $worker->documento)->whereNull('user_id')->update(['user_id' => $worker->id]);
                }
            } else {
                if (! trim((string) $reason)) {
                    throw ValidationException::withMessages(['motivo' => 'El motivo del rechazo es obligatorio.']);
                }
                $worker->status = 'RECHAZADO';
                $worker->rejected_at = now();
                $worker->rejected_by = auth()->id();
                $worker->rejection_reason = $reason;
            }
            $worker->save();
            Audit::record('trabajador.'.$decision, $worker, $before);
            $worker->notify(new MilkFlowNotification('Solicitud de registro: '.$worker->status, $decision === 'aprobar' ? 'Tu cuenta fue aprobada. Inicia sesión con el mismo correo y contraseña.' : 'Tu solicitud fue rechazada. Motivo: '.$reason, true));

            return $worker;
        });
    }
}
