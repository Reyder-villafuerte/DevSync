<?php

namespace App\Http\Controllers;

use App\Actions\ReviewWorker;
use App\Models\Acopiador;
use App\Models\Role;
use App\Models\User;
use App\Services\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class WorkerController extends Controller
{
    public function index(Request $r)
    {
        $users = User::with('roles')->when($r->is('admin/solicitudes'), fn ($q) => $q->where('status', $r->input('estado', 'PENDIENTE')))->latest()->paginate(20);

        return view('admin.workers', compact('users'));
    }

    public function show(User $user)
    {
        Gate::authorize('review', $user);

        return view('admin.worker', ['worker' => $user, 'roles' => Role::all()]);
    }

    public function review(Request $r, User $user, ReviewWorker $action)
    {
        $data = $r->validate(['decision' => 'required|in:aprobar,rechazar', 'role_id' => 'required_if:decision,aprobar|nullable|exists:roles,id', 'motivo' => 'required_if:decision,rechazar|nullable|string|max:2000']);
        $action->execute($user, $data['decision'], $data['role_id'] ?? null, $data['motivo'] ?? null);

        return redirect('/admin/solicitudes')->with('status', 'Solicitud revisada correctamente.');
    }

    public function update(Request $r, User $user)
    {
        Gate::authorize('review', $user);
        $data = $r->validate(['status' => 'required|in:ACTIVO,INACTIVO', 'role_id' => 'required|exists:roles,id']);
        DB::transaction(function () use ($user, $data) {
            $user = User::lockForUpdate()->findOrFail($user->id);
            abort_if(in_array($user->status, ['PENDIENTE', 'RECHAZADO']), 422, 'Utiliza el flujo de solicitudes.');
            $before = $user->getAttributes();
            $before['roles'] = $user->roles->pluck('name')->all();
            $user->roles()->sync([$data['role_id']]);
            $user->status = $data['status'];
            $user->save();
            $role = Role::findOrFail($data['role_id']);
            if ($role->slug === 'acopiador') {
                Acopiador::updateOrCreate(['user_id' => $user->id], ['nombre' => $user->name, 'telefono' => $user->telefono, 'activo' => $user->status === 'ACTIVO']);
            } else {
                $user->acopiador?->update(['activo' => false]);
            } Audit::record('usuario.rol_estado_actualizado', $user, $before);
        });

        return back()->with('status','Rol y estado actualizados.');
    }
}
