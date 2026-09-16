<?php

namespace App\Http\Controllers;

use App\Models\Auditoria;
use App\Services\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AccountController extends Controller
{
    public function notifications()
    {
        return view('account.notifications', ['notifications' => auth()->user()->notifications()->paginate(20)]);
    }

    public function read(string $id)
    {
        auth()->user()->notifications()->findOrFail($id)->markAsRead();

        return back();
    }

    public function profile()
    {
        return view('account.profile');
    }

    public function update(Request $r)
    {
        $data = $r->validate(['telefono' => ['required', 'regex:/^\+?[0-9 ()-]{9,20}$/']]);
        $u = $r->user();
        $before = $u->getAttributes();
        DB::transaction(function () use ($u, $data, $before) {
            $u->update($data);
            $u->productor?->update($data);
            Audit::record('perfil.actualizado', $u, $before);
        });

        return back()->with('status', 'Teléfono actualizado.');
    }

    public function settings()
    {
        return view('admin.settings', ['settings' => DB::table('configuraciones')->pluck('valor', 'clave')]);
    }

    public function saveSettings(Request $r)
    {
        $data = $r->validate(['hora_inicio' => 'required|date_format:H:i', 'hora_fin' => 'required|date_format:H:i|after:hora_inicio', 'tarifa_normal' => 'required|numeric|min:0.01|max:100', 'tarifa_castigo' => 'required|numeric|min:0|lte:tarifa_normal', 'descuento_reincidencia' => 'required|numeric|min:0|max:10000']);
        DB::transaction(function () use ($data) {
            $old = DB::table('configuraciones')->pluck('valor', 'clave')->all();
            foreach ($data as $k => $v) {
                DB::table('configuraciones')->updateOrInsert(['clave' => $k], ['valor' => $v, 'updated_at' => now(), 'created_at' => now()]);
            } Auditoria::create(['usuario_id' => auth()->id(), 'accion' => 'configuracion.actualizada', 'modelo' => 'configuraciones', 'registro' => 0, 'datos_anteriores' => $old, 'datos_nuevos' => $data, 'fecha' => now()]);
        });

        return back()->with('status', 'Configuración guardada.');
    }
}
