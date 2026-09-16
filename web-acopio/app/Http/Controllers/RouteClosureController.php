<?php

namespace App\Http\Controllers;

use App\Models\CierreRuta;
use App\Models\Ruta;
use App\Services\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RouteClosureController extends Controller
{
    public function index()
    {
        $routes = Ruta::with('sectores')->where('acopiador_id', auth()->user()->acopiador?->id ?? 0)->where('activo', true)->get();

        return view('routes.close', compact('routes'));
    }

    public function close(Request $r)
    {
        $r->validate(['ruta_id' => 'required|exists:rutas,id']);
        $closure = DB::transaction(function () use ($r) {
            $route = Ruta::lockForUpdate()->findOrFail($r->ruta_id);
            abort_unless($route->acopiador->user_id === auth()->id(), 403);
            $existing = CierreRuta::where('ruta_id', $route->id)->whereDate('fecha', today())->first();
            if ($existing) {
                return $existing;
            }
            $liters = $route->entregas()->whereDate('fecha_hora', today())->sum('litros');
            $closure = CierreRuta::create(['ruta_id' => $route->id, 'fecha' => today(), 'litros' => $liters, 'usuario_id' => auth()->id()]);
            Audit::record('ruta.cerrada_descargada', $closure);

            return $closure;
        });

        return response()->streamDownload(function () use ($closure) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Ruta', 'Fecha', 'Litros descargados']);
            fputcsv($out, [$closure->ruta_id, $closure->fecha, $closure->litros]);
            fclose($out);
        }, 'cierre-ruta-'.$closure->id.'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
