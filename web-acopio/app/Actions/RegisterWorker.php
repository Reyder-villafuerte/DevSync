<?php

namespace App\Actions;

use App\Models\User;
use App\Notifications\MilkFlowNotification;
use App\Services\Audit;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

class RegisterWorker
{
    public function execute(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $user = new User(collect($data)->only(['nombres', 'apellidos', 'documento', 'telefono', 'email'])->all());
            $user->name = $user->nombres.' '.$user->apellidos;
            $user->password = Hash::make($data['password']);
            $user->status = 'PENDIENTE';
            $user->save();
            Audit::record('trabajador.registrado', $user);
            Notification::send(User::where('status', 'ACTIVO')->whereHas('roles', fn ($q) => $q->where('slug', 'admin'))->get(), new MilkFlowNotification('Nueva solicitud de registro', $user->name.' solicita acceso a MilkFlow.'));

            return $user;
        });
    }
}
